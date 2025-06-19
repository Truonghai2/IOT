<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up()
    {
        if (!Capsule::schema()->hasTable('training_data')) {
            Capsule::schema()->create('training_data', function (Blueprint $table) {
                $table->id();
                $table->foreignId('device_id')->constrained('devices')->onDelete('cascade');
                $table->double('temperature', 8, 2);
                $table->double('humidity', 8, 2);
                $table->double('gas_value', 8, 2);
                $table->double('dust_value', 8, 2);
                $table->boolean('fire_sensor_status');
                $table->string('label');
                $table->timestamp('timestamp');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('training_data');
    }
}; 