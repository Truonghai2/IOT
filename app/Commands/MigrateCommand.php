<?php

namespace App\Commands;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

class MigrateCommand
{
    protected $capsule;

    public function __construct()
    {
        $this->capsule = new Capsule;
        
        // Database configuration
        $this->capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => $_ENV['DB_HOST'] ?? 'localhost',
            'database'  => $_ENV['DB_DATABASE'] ?? 'iot_monitoring',
            'username'  => $_ENV['DB_USERNAME'] ?? 'root',
            'password'  => $_ENV['DB_PASSWORD'] ?? '',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ]);

        // Create a new Container instance
        $container = new Container;
        
        // Create a new Dispatcher instance
        $dispatcher = new Dispatcher($container);
        
        // Set the event dispatcher
        $this->capsule->setEventDispatcher($dispatcher);
        
        // Make this Capsule instance available globally
        $this->capsule->setAsGlobal();
        
        // Setup the Eloquent ORM
        $this->capsule->bootEloquent();
    }

    public function handle()
    {
        echo "Starting database migration...\n";

        // Get all migration files
        $migrations = glob(__DIR__ . '/../../database/migrations/*.php');
        sort($migrations);

        foreach ($migrations as $migration) {
            $migrationName = basename($migration);
            echo "Migrating: {$migrationName}\n";

            // Include and run migration
            $migration = require $migration;
            $migration->up();
        }

        echo "Migration completed successfully!\n";
    }
} 