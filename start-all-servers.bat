@echo off
title IoT Monitoring System Launcher
echo ================================
echo  Starting IoT Monitoring System
echo ================================

REM Start HTTP Server (port 8000)
echo Starting HTTP Server on port 8000...
start "HTTP Server" cmd /k "php server.php"

REM Wait a moment for HTTP server to start
timeout /t 3 /nobreak > nul

REM Start WebSocket Server (port 8080)
echo Starting WebSocket Server on port 8080...
start "WebSocket Server" cmd /k "php websocket-server.php"

timeout /t 3 /nobreak > nul

REM Start TCP Broadcast Server (port 12345)
echo Starting TCP Server on port 12345...
start "TCP Server" cmd /k "php tcp-broadcast-server.php"

timeout /t 3 /nobreak > nul

REM Start Ngrok with YAML config
echo Starting Ngrok tunnels...
start "Ngrok" cmd /k "ngrok start --config C:\Users\HAI\AppData\Local\ngrok\ngrok.yml --all"

echo ================================
echo  All services started successfully!
echo ================================
pause
