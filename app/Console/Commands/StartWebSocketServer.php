<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\WebSocket\WebSocketServer;

class StartWebSocketServer extends Command
{
    protected $signature = 'websocket:serve';
    protected $description = 'Start the WebSocket server';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Starting WebSocket server...');
        $server = new WebSocketServer();
        $server->start();
    }
} 