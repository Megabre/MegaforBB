<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

return [
    'up' => function () {
        if (!DB::schema()->hasColumn('posts', 'reply_to_id')) {
            DB::schema()->table('posts', function (Blueprint $table) {
                $table->unsignedInteger('reply_to_id')->nullable()->after('topic_id');
                $table->foreign('reply_to_id')->references('id')->on('posts')->onDelete('set null');
            });
        }
    },
    'down' => function () {
        if (DB::schema()->hasColumn('posts', 'reply_to_id')) {
            DB::schema()->table('posts', function (Blueprint $table) {
                $table->dropForeign(['reply_to_id']);
                $table->dropColumn('reply_to_id');
            });
        }
    }
];
