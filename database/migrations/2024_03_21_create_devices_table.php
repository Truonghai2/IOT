<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up()
    {
        if (!Capsule::schema()->hasTable('devices')) {
            Capsule::schema()->create('devices', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type');
                $table->string('esp_ip')->unique();
                $table->string('status')->default('offline');
                $table->string('location')->nullable();
                $table->text('description')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('devices');
    }
}; 