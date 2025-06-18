<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up()
    {
        Capsule::schema()->create('sensor_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->float('temperature');
            $table->float('humidity');
            $table->float('gas_value');
            $table->float('dust_value');
            $table->boolean('fire_sensor_status');
            $table->timestamps();
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('sensor_data');
    }
}; 