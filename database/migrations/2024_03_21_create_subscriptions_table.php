<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up()
    {
        if (!Capsule::schema()->hasTable('subscriptions')) {
            Capsule::schema()->create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->string('endpoint')->unique()->nullable();
                $table->string('auth_key')->nullable();
                $table->string('p256dh_key')->nullable();
                $table->string('user_agent')->nullable();
                $table->string('user_id')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('subscriptions');
    }
}; 