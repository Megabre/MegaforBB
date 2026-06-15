<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RewardLevel extends Model
{
    protected $table = 'reward_levels';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'min_posts',
        'min_reputation',
        'min_likes',
        'badge_label',
        'badge_icon',
        'badge_css',
        'sort_order',
    ];

    protected $casts = [
        'min_posts' => 'integer',
        'min_reputation' => 'integer',
        'min_likes' => 'integer',
        'sort_order' => 'integer',
    ];

    /** Kullanıcının post sayısı, net rep ve beğeni ile eşleşen en yüksek seviye. */
    public static function forUser(int $postCount, int $netReputation, int $likeCount): ?self
    {
        try {
            return static::query()
                ->where('min_posts', '<=', $postCount)
                ->where('min_reputation', '<=', $netReputation)
                ->where('min_likes', '<=', $likeCount)
                ->orderByDesc('sort_order')
                ->orderByDesc('id')
                ->first();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
