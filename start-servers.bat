@echo off
start cmd /k "nodemon --exec php --ext php --watch app --watch websocket-server.php websocket-server.php start"
timeout /t 2
start cmd /k "nodemon --exec php --ext php --watch app --watch tcp-broadcast-server.php tcp-broadcast-server.php start"
timeout /t 2
start cmd /k "nodemon --exec php --ext php --watch app --watch server.php server.php start"
timeout /t 2
start cmd /k "php -S 0.0.0.0:8000 -t public" 