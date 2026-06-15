<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class () {
    public function up(): void
    {
        $schema = Capsule::schema();

        if (!$schema->hasTable('languages')) {
            $schema->create('languages', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code', 10)->unique();
                $table->string('name', 100);
                $table->string('native_name', 100);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->enum('direction', ['ltr', 'rtl'])->default('ltr');
                $table->timestamps();
            });

            Capsule::table('languages')->insert([
                [
                    'code'        => 'tr',
                    'name'        => 'Turkish',
                    'native_name' => 'Türkçe',
                    'is_active'   => true,
                    'is_default'  => true,
                    'direction'   => 'ltr',
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ],
                [
                    'code'        => 'en',
                    'name'        => 'English',
                    'native_name' => 'English',
                    'is_active'   => true,
                    'is_default'  => false,
                    'direction'   => 'ltr',
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ],
            ]);
        }

        if (!$schema->hasTable('language_lines')) {
            $schema->create('language_lines', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('locale', 10)->index();
                $table->string('group', 50)->index();
                $table->string('key', 255);
                $table->text('value');
                $table->timestamps();

                $table->unique(['locale', 'group', 'key'], 'lang_line_unique');
            });
        }
    }

    public function down(): void
    {
        $schema = Capsule::schema();
        $schema->dropIfExists('language_lines');
        $schema->dropIfExists('languages');
    }
};
