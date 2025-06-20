# Ngrok Setup cho IoT Monitoring System

## Bước 1: Cài đặt Ngrok
1. Tải ngrok từ: https://ngrok.com/download
2. Giải nén và thêm vào PATH
3. Đăng ký tài khoản ngrok và lấy authtoken

## Bước 2: Cấu hình Ngrok
1. Thay thế `YOUR_NGROK_AUTH_TOKEN_HERE` trong file `ngrok.yml` bằng authtoken thực của bạn
2. (Tùy chọn) Thay đổi subdomain trong file `ngrok.yml`:
   - `your-iot-app` → subdomain cho HTTP server
   - `your-iot-ws` → subdomain cho WebSocket server

## Bước 3: Chạy hệ thống
### Cách 1: Chạy tất cả cùng lúc
```bash
start-all-servers.bat
```

### Cách 2: Chạy từng server riêng lẻ
```bash
# Terminal 1: HTTP Server
php server.php

# Terminal 2: WebSocket Server  
php websocket-server.php

# Terminal 3: TCP Server
php tcp-broadcast-server.php

# Terminal 4: Ngrok
ngrok start --config ngrok.yml --all
```

## Bước 4: Truy cập ứng dụng
Sau khi chạy thành công, bạn sẽ thấy URLs như:
- **HTTP Server:** `https://your-iot-app.ngrok.io`
- **WebSocket Server:** `https://your-iot-ws.ngrok.io`

## Lưu ý quan trọng:
1. **WebSocket qua ngrok:** Code đã được cập nhật để tự động detect ngrok và sử dụng WSS (secure WebSocket)
2. **Subdomain:** Đảm bảo subdomain trong `ngrok.yml` khớp với code JavaScript
3. **Authtoken:** Cần authtoken hợp lệ để sử dụng subdomain tùy chỉnh
4. **Firewall:** Ngrok sẽ bypass firewall, cho phép truy cập từ internet

## Troubleshooting:
- Nếu WebSocket không kết nối, kiểm tra console browser để xem URL được sử dụng
- Nếu subdomain bị lỗi, sử dụng file `ngrok-simple.yml` thay thế
- Đảm bảo tất cả server (HTTP, WebSocket, TCP) đều đang chạy trước khi start ngrok 