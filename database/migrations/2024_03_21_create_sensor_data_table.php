<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up()
    {
        if (!Capsule::schema()->hasTable('sensor_data')) {
            Capsule::schema()->create('sensor_data', function (Blueprint $table) {
                $table->id();
                $table->foreignId('device_id')->constrained('devices')->onDelete('cascade');
                $table->float('temperature')->nullable();
                $table->float('humidity')->nullable();
                $table->float('gas')->nullable();
                $table->float('smoke')->nullable();
                $table->boolean('fire_detected')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('sensor_data');
    }
}; 