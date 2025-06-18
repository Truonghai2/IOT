<?php

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;

$config = require __DIR__ . '/../config/database.php';
$connection = $config['connections'][$config['default']];

$capsule->addConnection($connection);
$capsule->setAsGlobal();
$capsule->bootEloquent(); 