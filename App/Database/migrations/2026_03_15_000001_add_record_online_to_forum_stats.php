<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

/**
 * phpBB tarzı rekor çevrimiçi kullanıcı sayısı ve tarihi.
 * index.php / obtain_users_online sonrası record_online_users güncellemesine benzer.
 */
return [
    'up' => function () {
        if (!DB::schema()->hasColumn('forum_stats', 'record_online_users')) {
            DB::schema()->table('forum_stats', function (Blueprint $table) {
                $table->unsignedInteger('record_online_users')->default(0)->after('last_member_username');
                $table->dateTime('record_online_date')->nullable()->after('record_online_users');
            });
        }
    },
    'down' => function () {
        if (DB::schema()->hasColumn('forum_stats', 'record_online_users')) {
            DB::schema()->table('forum_stats', function (Blueprint $table) {
                $table->dropColumn(['record_online_users', 'record_online_date']);
            });
        }
    },
];
