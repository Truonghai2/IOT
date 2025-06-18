@echo off
start cmd /k "php websocket-server.php start"
timeout /t 2
start cmd /k "php server.php start" 