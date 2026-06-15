<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Güvenlik / canlı trafik log satırlarında IP ile eşleşen kayıtlı oturumları (sessions + users) çözer.
 */
final class SecurityLogUserLookup
{
    /**
     * IPv4 ile IPv4-mapped IPv6 (::ffff:x.x.x.x) gibi eşdeğer biçimleri tek anahtarda toplamak için.
     */
    public static function normalizeIp(string $ip): string
    {
        $ip = trim($ip);
        if ($ip === '') {
            return '';
        }
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return $ip;
        }
        if (strlen($packed) === 16 && strncmp($packed, str_repeat("\0", 10) . "\xff\xff", 12) === 0) {
            $v4 = substr($packed, 12, 4);
            $n = @inet_ntop($v4);

            return $n !== false ? $n : $ip;
        }
        $n = @inet_ntop($packed);

        return $n !== false ? $n : $ip;
    }

    /**
     * @param list<string> $ips
     * @return list<string>
     */
    private static function expandIpsForQuery(array $ips): array
    {
        $set = [];
        foreach ($ips as $ip) {
            $ip = trim((string) $ip);
            if ($ip === '') {
                continue;
            }
            $set[$ip] = true;
            $n = self::normalizeIp($ip);
            if ($n !== '' && $n !== $ip) {
                $set[$n] = true;
            }
        }

        return array_keys($set);
    }

    /**
     * @param list<array<string, mixed>> $entries Güvenlik audit (SecurityLogger) satırları
     * @return list<string>
     */
    public static function collectIpsFromAuditEntries(array $entries): array
    {
        $ips = [];
        foreach ($entries as $e) {
            if (!is_array($e)) {
                continue;
            }
            foreach (['ip', 'client_ip'] as $k) {
                $ip = trim((string) ($e[$k] ?? ''));
                if ($ip !== '') {
                    $ips[] = $ip;
                }
            }
        }

        return array_values(array_unique($ips));
    }

    /**
     * @param list<array<string, mixed>> $entries AnalyticsLogger satırları
     * @return list<string>
     */
    public static function collectIpsFromAnalyticsEntries(array $entries): array
    {
        $ips = [];
        foreach ($entries as $e) {
            if (!is_array($e)) {
                continue;
            }
            $ip = trim((string) ($e['ip'] ?? ''));
            if ($ip !== '') {
                $ips[] = $ip;
            }
        }

        return array_values(array_unique($ips));
    }

    /**
     * @param list<string> $ips
     * @return array<string, string> ip => görünen etiket (birden fazla kullanıcı virgülle)
     */
    public static function labelsForIps(array $ips): array
    {
        $ips = array_values(array_unique(array_filter(array_map(static function ($ip) {
            $ip = trim((string) $ip);

            return $ip !== '' ? $ip : null;
        }, $ips))));
        if ($ips === []) {
            return [];
        }
        $queryIps = self::expandIpsForQuery($ips);
        $grouped = [];
        try {
            $rows = DB::table('sessions')
                ->join('users', 'users.id', '=', 'sessions.user_id')
                ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
                ->whereIn('sessions.ip_address', $queryIps)
                ->select('sessions.ip_address', 'users.username', 'roles.is_staff')
                ->get();
            foreach ($rows as $row) {
                $ip = self::normalizeIp((string) $row->ip_address);
                $uname = (string) $row->username;
                if ($uname === '') {
                    continue;
                }
                $staff = (bool) ($row->is_staff ?? false);
                if (!isset($grouped[$ip])) {
                    $grouped[$ip] = [];
                }
                $grouped[$ip][$uname] = $staff;
            }
        } catch (\Throwable) {
        }
        try {
            $rowsU = DB::table('users')
                ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
                ->whereIn('users.last_ip', $queryIps)
                ->select('users.last_ip', 'users.username', 'roles.is_staff')
                ->get();
            foreach ($rowsU as $row) {
                $ip = self::normalizeIp((string) $row->last_ip);
                $uname = (string) $row->username;
                if ($ip === '' || $uname === '') {
                    continue;
                }
                $staff = (bool) ($row->is_staff ?? false);
                if (!isset($grouped[$ip])) {
                    $grouped[$ip] = [];
                }
                $grouped[$ip][$uname] = $staff;
            }
        } catch (\Throwable) {
        }
        $out = [];
        foreach ($grouped as $ip => $users) {
            $parts = [];
            foreach ($users as $uname => $isStaff) {
                $parts[] = $isStaff
                    ? $uname . ' (' . lang('admin.security_log.ip_match_staff') . ')'
                    : $uname;
            }
            sort($parts, SORT_STRING);
            $out[$ip] = implode(', ', $parts);
        }

        foreach ($ips as $rawIp) {
            $rawIp = trim((string) $rawIp);
            if ($rawIp === '') {
                continue;
            }
            $n = self::normalizeIp($rawIp);
            if ($n !== '' && $n !== $rawIp && isset($out[$n])) {
                $out[$rawIp] = $out[$n];
            }
        }

        return $out;
    }
}
