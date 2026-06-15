<?php

declare(strict_types=1);

namespace App\Modules\Idelist\Models;

use Illuminate\Database\Eloquent\Model;

class IdeaStatusDefinition extends Model
{
    protected $table = 'idea_statuses';

    protected $fillable = [
        'slug',
        'name',
        'color',
        'sort_order',
        'requires_completion',
        'default_on_approval',
        'default_on_open',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'requires_completion' => 'boolean',
        'default_on_approval' => 'boolean',
        'default_on_open' => 'boolean',
    ];

    /**
     * @return list<string>
     */
    public static function allSlugs(): array
    {
        return static::query()->orderBy('sort_order')->orderBy('name')->pluck('slug')->all();
    }

    public static function defaultSlugForNewIdea(bool $approvalRequired): string
    {
        $col = $approvalRequired ? 'default_on_approval' : 'default_on_open';
        $row = static::query()->where($col, true)->orderBy('sort_order')->orderBy('id')->first();
        if ($row !== null) {
            return (string) $row->slug;
        }
        $fallback = $approvalRequired ? 'pending' : 'open';
        if (static::query()->where('slug', $fallback)->exists()) {
            return $fallback;
        }
        $any = static::query()->orderBy('sort_order')->orderBy('id')->first();

        return $any !== null ? (string) $any->slug : 'open';
    }

    public static function syncExclusiveDefaults(self $saved): void
    {
        if ($saved->default_on_approval) {
            static::query()->where('id', '!=', $saved->id)->update(['default_on_approval' => false]);
        }
        if ($saved->default_on_open) {
            static::query()->where('id', '!=', $saved->id)->update(['default_on_open' => false]);
        }
    }
}
