<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up()
    {
        Capsule::schema()->create('sensor_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->onDelete('cascade');
            $table->float('temperature')->nullable();
            $table->float('humidity')->nullable();
            $table->float('pressure')->nullable();
            $table->float('gas')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('sensor_data');
    }
}; 