<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedEmailDomain extends Model
{
    protected $table = 'blocked_email_domains';

    public $timestamps = false;

    public const UPDATED_AT = null;

    protected $fillable = ['domain'];
}
