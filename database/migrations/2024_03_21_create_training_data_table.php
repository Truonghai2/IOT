<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up()
    {
        Capsule::schema()->create('training_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->onDelete('cascade');
            $table->float('temperature');
            $table->float('humidity');
            $table->float('gas_value');
            $table->float('dust_value');
            $table->boolean('fire_sensor_status');
            $table->string('label');
            $table->timestamp('timestamp');
            $table->timestamps();
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('training_data');
    }
}; 