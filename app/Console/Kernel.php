<?php

namespace App\Console;

use Illuminate\Console\Application as Artisan;
use Illuminate\Contracts\Console\Kernel as KernelContract;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;

class Kernel implements KernelContract
{
    protected $app;
    protected $artisan;
    protected $commands = [
        \App\Console\Commands\StartWebSocketServer::class,
    ];

    public function __construct(Application $app, Dispatcher $events)
    {
        $this->app = $app;
        $this->artisan = new Artisan($app, $events, $app->version());
        $this->artisan->resolveCommands($this->commands);
    }

    public function handle($input, $output = null)
    {
        return $this->artisan->run($input, $output);
    }

    public function terminate($input, $status)
    {
        $this->artisan->terminate($input, $status);
    }

    public function getArtisan()
    {
        return $this->artisan;
    }
} 