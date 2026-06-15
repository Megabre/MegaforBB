<?php

declare(strict_types=1);

namespace App\Services;

use Forecor\Core\Application;

/**
 * SMTP ile e-posta gönderir. Ayarlar veritabanı settings tablosundan okunur (Admin → Sistem Ayarları → Mail).
 */
class MailService
{
    private Application $app;

    /** Son SMTP/bağlantı hata mesajı (send false döndüğünde). */
    private string $lastError = '';

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    private function getSetting(string $key, string $default = ''): string
    {
        return (string) $this->app->getSetting($key, $default);
    }

    /**
     * Tek alıcıya HTML/düz metin mail gönderir.
     * Başarı: true, hata: false (log yazılabilir).
     */
    public function send(string $to, string $subject, string $bodyHtml, string $bodyText = ''): bool
    {
        $this->lastError = '';
        $fromAddress = $this->getSetting('mail_from_address');
        $fromName = $this->getSetting('mail_from_name', 'MegaforBB');
        if ($fromAddress === '') {
            $this->lastError = lang('mail.from_empty');
            return false;
        }

        $driver = $this->getSetting('mail_driver', 'smtp');
        if ($driver === 'smtp') {
            return $this->sendViaSmtp($to, $subject, $bodyHtml, $bodyText, $fromAddress, $fromName);
        }
        return $this->sendViaMail($to, $subject, $bodyHtml, $bodyText, $fromAddress, $fromName);
    }

    private function sendViaMail(string $to, string $subject, string $bodyHtml, string $bodyText, string $fromAddress, string $fromName): bool
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->encodeAddress($fromName, $fromAddress),
            'Reply-To: ' . $fromAddress,
        ];
        $message = $bodyHtml ?: $bodyText;
        return @mail($to, $subject, $message, implode("\r\n", $headers));
    }

    private function sendViaSmtp(string $to, string $subject, string $bodyHtml, string $bodyText, string $fromAddress, string $fromName): bool
    {
        $host = $this->getSetting('smtp_host');
        $port = (int) $this->getSetting('smtp_port', '587');
        $encryption = $this->getSetting('smtp_encryption', 'tls');
        $user = $this->getSetting('smtp_username');
        $pass = $this->getSetting('smtp_password');

        if ($host === '') {
            $this->lastError = lang('mail.smtp_host_empty');
            return false;
        }

        $context = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        $target = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $errno = 0;
        $errstr = '';
        $sock = @stream_socket_client($target, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $context);
        if (!$sock) {
            $this->lastError = lang('mail.connection_failed', ['detail' => $errstr ?: "Error #{$errno}"]);
            return false;
        }

        stream_set_timeout($sock, 15);

        $read = function () use ($sock): string {
            $line = @fgets($sock);
            return $line === false ? '' : rtrim($line, "\r\n");
        };
        $write = function (string $cmd) use ($sock): void {
            @fwrite($sock, $cmd . "\r\n");
        };

        /** Read multiline SMTP response (250- continuation, 250 final). */
        $readEhlo = function () use ($read): void {
            while (($line = $read()) !== '') {
                $t = trim($line);
                if (strlen($t) >= 4 && substr($t, 0, 4) === '250-') {
                    continue;
                }
                if (strlen($t) >= 4 && (substr($t, 0, 4) === '250 ' || substr($t, 0, 3) === '250')) {
                    break;
                }
                break;
            }
        };

        $greet = $read(); // 220
        $write('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        $readEhlo();

        if ($encryption === 'tls' && $port !== 465) {
            $write('STARTTLS');
            $startLine = $read();
            if (strpos($startLine, '220') !== 0) {
                $this->lastError = 'STARTTLS rejected: ' . $startLine;
                fclose($sock);
                return false;
            }
            if (!@stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                $this->lastError = lang('mail.tls_failed');
                fclose($sock);
                return false;
            }
            $write('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
            $readEhlo();
        }

        if ($user !== '' && $pass !== '') {
            $write('AUTH LOGIN');
            $read(); // 334 VXNlcm5hbWU6
            $write(base64_encode($user));
            $read(); // 334 UGFzc3dvcmQ6
            $write(base64_encode($pass));
            $line = $read();
            if (strpos($line, '235') !== 0) {
                $this->lastError = lang('mail.auth_failed', ['line' => trim($line)]);
                fclose($sock);
                return false;
            }
        }

        $write('MAIL FROM:<' . $fromAddress . '>');
        $mailFromLine = $read();
        if (strpos($mailFromLine, '250') !== 0) {
            $this->lastError = 'MAIL FROM rejected: ' . trim($mailFromLine);
            fclose($sock);
            return false;
        }
        $write('RCPT TO:<' . $to . '>');
        $rcptLine = $read();
        if (strpos($rcptLine, '250') !== 0) {
            $this->lastError = 'RCPT TO rejected: ' . trim($rcptLine);
            fclose($sock);
            return false;
        }
        $write('DATA');
        $read(); // 354
        $headers = "From: " . $this->encodeAddress($fromName, $fromAddress) . "\r\n";
        $headers .= "To: " . $to . "\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "\r\n";
        $body = str_replace("\r\n.", "\r\n..", $bodyHtml);
        $message = $headers . $body . "\r\n.\r\n";
        $write($message);
        $line = $read();
        $write('QUIT');
        fclose($sock);
        if (strpos($line, '250') !== 0) {
            $this->lastError = lang('mail.recipient_rejected', ['line' => trim($line)]);
            return false;
        }
        return true;
    }

    private function encodeAddress(string $name, string $email): string
    {
        if ($name === '') {
            return $email;
        }
        return '=?UTF-8?B?' . base64_encode($name) . '?= <' . $email . '>';
    }
}
