<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * Admin panel AJAX API: arama vb. (sadece staff).
 * Header araması admin panel menü öğelerinde (sayfa linkleri) arama yapar.
 */
class AdminApiController extends AdminController
{
    /** AJAX: Admin panel menüsünde arama — özelliklerin nerede olduğunu bulmak için. GET q= */
    public function search(): string
    {
        $q = mb_strtolower(trim((string) ($_GET['q'] ?? '')));
        $menu = [];
        if (mb_strlen($q) >= 2) {
            $nav = $this->getAdminNavData();
            $groups = $nav['adminNavGroups'] ?? [];
            foreach ($groups as $group) {
                $groupLabel = (string) ($group['label'] ?? '');
                $groupLabelLower = mb_strtolower($groupLabel);
                $children = $group['children'] ?? [];
                if ($children === [] && $groupLabel !== '') {
                    if (mb_strpos($groupLabelLower, $q) !== false) {
                        $url = $group['url'] ?? '';
                        if ($url !== '') {
                            $menu[] = ['title' => $groupLabel, 'url' => $url, 'group' => ''];
                        }
                    }
                    continue;
                }
                foreach ($children as $child) {
                    if (!empty($child['separator'])) {
                        continue;
                    }
                    $label = (string) ($child['label'] ?? '');
                    $labelLower = mb_strtolower($label);
                    $searchText = $groupLabelLower . ' ' . $labelLower;
                    if (mb_strpos($searchText, $q) !== false || mb_strpos($labelLower, $q) !== false) {
                        $url = $child['url'] ?? '';
                        if ($url !== '') {
                            $menu[] = ['title' => $label, 'url' => $url, 'group' => $groupLabel];
                        }
                    }
                }
            }
            $menu = array_slice($menu, 0, 15);
        }
        $this->json(['menu' => $menu]);
        return '';
    }

    protected function json(array $data, int $statusCode = 200): void
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
