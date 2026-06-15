<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $table = 'roles';
    public $timestamps = false; // No created_at/updated_at in schema.sql for roles table

    protected $fillable = [
        'name',
        'slug',
        'color',
        'is_staff',
        'sort_order',
        'pm_daily_limit',
        'pm_inbox_limit',
        'pm_daily_receive_limit',
        'pm_lifetime_total_quota',
        'daily_topic_limit',
        'bump_per_day',
    ];

    protected $casts = [
        'is_staff' => 'boolean',
        'sort_order' => 'integer',
        'pm_daily_limit' => 'integer',
        'pm_inbox_limit' => 'integer',
        'pm_daily_receive_limit' => 'integer',
        'pm_lifetime_total_quota' => 'integer',
        'daily_topic_limit' => 'integer',
        'bump_per_day' => 'integer',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(GroupPermission::class, 'role_id');
    }
}
