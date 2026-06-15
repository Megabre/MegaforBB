<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Forum;
use App\Models\Prefix;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;

/**
 * Konu ön eklerinin forum / kategori kapsamı ve sıralı listesi.
 *
 * Öncelik: foruma özel liste → kategoriye özel liste → eski topic_prefixes.category_id mantığı.
 */
final class TopicPrefixScopeService
{
    private static function pivotTablesExist(): bool
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        try {
            $cache = DB::schema()->hasTable('forum_topic_prefix')
                && DB::schema()->hasTable('category_topic_prefix');
        } catch (\Throwable $e) {
            $cache = false;
        }

        return $cache;
    }

    /**
     * @return list<int>
     */
    public static function allowedPrefixIdsForForum(int $forumId, int $categoryId): array
    {
        if (!self::pivotTablesExist()) {
            return self::legacyAllowedPrefixIds($categoryId);
        }
        $rows = DB::table('forum_topic_prefix')
            ->where('forum_id', $forumId)
            ->orderBy('sort_order')
            ->orderBy('prefix_id')
            ->pluck('prefix_id')
            ->all();
        if ($rows !== []) {
            return array_values(array_map('intval', $rows));
        }

        if ($categoryId > 0) {
            $rows = DB::table('category_topic_prefix')
                ->where('category_id', $categoryId)
                ->orderBy('sort_order')
                ->orderBy('prefix_id')
                ->pluck('prefix_id')
                ->all();
            if ($rows !== []) {
                return array_values(array_map('intval', $rows));
            }
        }

        $q = Prefix::query()->orderBy('sort_order')->orderBy('id');
        if ($categoryId > 0) {
            $q->where(function ($q2) use ($categoryId): void {
                $q2->whereNull('category_id')->orWhere('category_id', $categoryId);
            });
        } else {
            $q->whereNull('category_id');
        }

        return $q->pluck('id')->map(static fn ($id): int => (int) $id)->all();
    }

    /**
     * @return list<int>
     */
    private static function legacyAllowedPrefixIds(int $categoryId): array
    {
        $q = Prefix::query()->orderBy('sort_order')->orderBy('id');
        if ($categoryId > 0) {
            $q->where(function ($q2) use ($categoryId): void {
                $q2->whereNull('category_id')->orWhere('category_id', $categoryId);
            });
        } else {
            $q->whereNull('category_id');
        }

        return $q->pluck('id')->map(static fn ($id): int => (int) $id)->all();
    }

    /**
     * @return Collection<int, Prefix>
     */
    public static function prefixesForForum(Forum $forum): Collection
    {
        $ids = self::allowedPrefixIdsForForum((int) $forum->id, (int) ($forum->category_id ?? 0));
        if ($ids === []) {
            return collect();
        }
        $byId = Prefix::whereIn('id', $ids)->get()->keyBy('id');
        $ordered = collect();
        foreach ($ids as $id) {
            if ($byId->has($id)) {
                $ordered->push($byId->get($id));
            }
        }

        return $ordered;
    }

    public static function isPrefixAllowedForForum(Forum $forum, int $prefixId): bool
    {
        if ($prefixId <= 0) {
            return true;
        }

        return in_array($prefixId, self::allowedPrefixIdsForForum((int) $forum->id, (int) ($forum->category_id ?? 0)), true);
    }

    /**
     * @param list<int> $prefixIds
     */
    public static function syncForumPrefixes(int $forumId, array $prefixIds): void
    {
        if (!self::pivotTablesExist()) {
            return;
        }
        DB::table('forum_topic_prefix')->where('forum_id', $forumId)->delete();
        $sort = 0;
        foreach (array_unique(array_filter(array_map('intval', $prefixIds))) as $pid) {
            if ($pid > 0 && Prefix::where('id', $pid)->exists()) {
                DB::table('forum_topic_prefix')->insert([
                    'forum_id' => $forumId,
                    'prefix_id' => $pid,
                    'sort_order' => $sort,
                ]);
                ++$sort;
            }
        }
    }

    /**
     * @param list<int> $prefixIds
     */
    public static function syncCategoryPrefixes(int $categoryId, array $prefixIds): void
    {
        if (!self::pivotTablesExist()) {
            return;
        }
        DB::table('category_topic_prefix')->where('category_id', $categoryId)->delete();
        $sort = 0;
        foreach (array_unique(array_filter(array_map('intval', $prefixIds))) as $pid) {
            if ($pid > 0 && Prefix::where('id', $pid)->exists()) {
                DB::table('category_topic_prefix')->insert([
                    'category_id' => $categoryId,
                    'prefix_id' => $pid,
                    'sort_order' => $sort,
                ]);
                ++$sort;
            }
        }
    }

    /**
     * @return list<int>
     */
    public static function forumPrefixIds(int $forumId): array
    {
        if (!self::pivotTablesExist()) {
            return [];
        }

        return DB::table('forum_topic_prefix')
            ->where('forum_id', $forumId)
            ->orderBy('sort_order')
            ->orderBy('prefix_id')
            ->pluck('prefix_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    public static function categoryPrefixIds(int $categoryId): array
    {
        if (!self::pivotTablesExist()) {
            return [];
        }

        return DB::table('category_topic_prefix')
            ->where('category_id', $categoryId)
            ->orderBy('sort_order')
            ->orderBy('prefix_id')
            ->pluck('prefix_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * Forumda henüz foruma özel satır yokken geçerli olacak ön ek id listesi (kategori ataması + legacy).
     *
     * @return list<int>
     */
    public static function inheritedPrefixIdsForForum(int $categoryId): array
    {
        if (!self::pivotTablesExist()) {
            return self::legacyAllowedPrefixIds($categoryId);
        }
        if ($categoryId > 0) {
            $rows = DB::table('category_topic_prefix')
                ->where('category_id', $categoryId)
                ->orderBy('sort_order')
                ->orderBy('prefix_id')
                ->pluck('prefix_id')
                ->all();
            if ($rows !== []) {
                return array_values(array_map('intval', $rows));
            }
        }

        return self::legacyAllowedPrefixIds($categoryId);
    }

    /**
     * Bu ön eki belirtilen forumların listesine ekler (gerekirse miras listesini foruma özel kayda çevirir).
     */
    public static function ensurePrefixOnForum(int $forumId, int $prefixId): void
    {
        if (!self::pivotTablesExist() || $prefixId <= 0 || !Prefix::where('id', $prefixId)->exists()) {
            return;
        }
        $forum = Forum::find($forumId);
        if ($forum === null) {
            return;
        }
        $rows = DB::table('forum_topic_prefix')
            ->where('forum_id', $forumId)
            ->orderBy('sort_order')
            ->pluck('prefix_id')
            ->map(static fn ($x): int => (int) $x)
            ->all();
        if (in_array($prefixId, $rows, true)) {
            return;
        }
        $catId = (int) ($forum->category_id ?? 0);
        if ($rows === []) {
            $ids = self::inheritedPrefixIdsForForum($catId);
            if (!in_array($prefixId, $ids, true)) {
                $ids[] = $prefixId;
            }
            self::syncForumPrefixes($forumId, $ids);

            return;
        }
        $rows[] = $prefixId;
        self::syncForumPrefixes($forumId, array_values(array_unique($rows)));
    }

    public static function removePrefixFromForum(int $forumId, int $prefixId): void
    {
        if (!self::pivotTablesExist()) {
            return;
        }
        DB::table('forum_topic_prefix')->where('forum_id', $forumId)->where('prefix_id', $prefixId)->delete();
    }

    /**
     * Ön ek düzenleme: işaretlenen forumlarda göster, işaretsizlerde bu önekle eşleşen satırı sil.
     *
     * @param list<int> $forumIds
     */
    public static function syncPrefixForumAssignments(int $prefixId, array $forumIds): void
    {
        if (!self::pivotTablesExist() || $prefixId <= 0) {
            return;
        }
        $wanted = array_values(array_unique(array_filter(array_map('intval', $forumIds))));
        $current = DB::table('forum_topic_prefix')
            ->where('prefix_id', $prefixId)
            ->pluck('forum_id')
            ->map(static fn ($x): int => (int) $x)
            ->all();
        foreach (array_diff($wanted, $current) as $fid) {
            self::ensurePrefixOnForum($fid, $prefixId);
        }
        foreach (array_diff($current, $wanted) as $fid) {
            self::removePrefixFromForum($fid, $prefixId);
        }
    }

    public static function forumIdsHavingPrefixExplicit(int $prefixId): array
    {
        if (!self::pivotTablesExist()) {
            return [];
        }

        return DB::table('forum_topic_prefix')
            ->where('prefix_id', $prefixId)
            ->pluck('forum_id')
            ->map(static fn ($x): int => (int) $x)
            ->all();
    }

    public static function deletePrefixFromScopeTables(int $prefixId): void
    {
        if (!self::pivotTablesExist()) {
            return;
        }
        DB::table('forum_topic_prefix')->where('prefix_id', $prefixId)->delete();
        DB::table('category_topic_prefix')->where('prefix_id', $prefixId)->delete();
    }

    /**
     * Yalnızca belirtilen kategorideki forumlar için bu öneki günceller; diğer kategorilerdeki atamalara dokunmaz.
     *
     * @param list<int> $forumIds İşaretli forum id'leri (yalnızca bu kategoridekiler uygulanır)
     */
    public static function syncPrefixForumAssignmentsInCategory(int $prefixId, int $categoryId, array $forumIds): void
    {
        if (!self::pivotTablesExist() || $prefixId <= 0 || $categoryId <= 0) {
            return;
        }
        $inCategory = Forum::query()
            ->where('category_id', $categoryId)
            ->pluck('id')
            ->map(static fn ($x): int => (int) $x)
            ->all();
        if ($inCategory === []) {
            return;
        }
        $wanted = array_values(array_intersect(
            array_unique(array_filter(array_map('intval', $forumIds))),
            $inCategory
        ));
        foreach ($inCategory as $fid) {
            if (!in_array($fid, $wanted, true)) {
                self::removePrefixFromForum($fid, $prefixId);
            }
        }
        foreach ($wanted as $fid) {
            self::ensurePrefixOnForum($fid, $prefixId);
        }
    }
}
