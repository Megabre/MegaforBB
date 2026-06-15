<?php declare(strict_types=1);

/**
 * Laravel Illuminate Database (Capsule) bootstrap.
 * Eloquent ORM ve Query Builder kullanılabilir; mevcut PDO ile birlikte çalışır.
 */

(function () {
    if (!class_exists(\Illuminate\Database\Capsule\Manager::class)) {
        return;
    }

    $config = core_config('database.connections.mysql');
    if (!$config || empty($config['database'])) {
        return;
    }

    $capsule = new \Illuminate\Database\Capsule\Manager();
    $capsule->addConnection([
        'driver'    => $config['driver'] ?? 'mysql',
        'host'      => $config['host'] ?? '127.0.0.1',
        'port'      => $config['port'] ?? 3306,
        'database'  => $config['database'],
        'username'  => $config['username'],
        'password'  => $config['password'],
        'charset'   => $config['charset'] ?? 'utf8mb4',
        'collation' => $config['collation'] ?? 'utf8mb4_unicode_ci',
        'prefix'    => '',
        'options'   => $config['options'] ?? [],
    ]);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
})();
