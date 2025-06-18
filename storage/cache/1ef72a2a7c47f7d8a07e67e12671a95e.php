<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giám sát ESP32</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .device-card { margin-bottom: 32px; }
        .toggle-switch { position: relative; width: 50px; height: 25px; }
        .toggle-switch input { display: none; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: 0.4s; border-radius: 25px; }
        .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 2px; background-color: white; transition: 0.4s; border-radius: 50%; }
        input:checked + .slider { background-color: #4CAF50; }
        input:checked + .slider:before { transform: translateX(25px); }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <h1 class="mb-4 text-center">Hệ thống giám sát ESP32</h1>
    <div id="alerts-container"></div>
    <div class="row">
        <?php $__currentLoopData = $devices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $device): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col-md-6">
            <div class="card device-card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <strong>Board: <?php echo e($device->name); ?></strong>
                    <span class="float-end badge <?php echo e($device->status ? 'bg-success' : 'bg-danger'); ?>">
                        <?php echo e($device->status ? 'Online' : 'Offline'); ?>

                    </span>
                </div>
                <div class="card-body">
                    <?php 
                        $sensorData = $device->sensorData();
                        $sensor = !empty($sensorData) ? $sensorData[0] : null;
                    ?>
                    <div class="row">
                        <div class="col-6">
                            <h5>Nhiệt độ & Độ ẩm</h5>
                            <p>Nhiệt độ: <span><?php echo e($sensor->temperature ?? '--'); ?></span> °C</p>
                            <p>Độ ẩm: <span><?php echo e($sensor->humidity ?? '--'); ?></span> %</p>
                            <canvas id="tempHumidityChart-<?php echo e($device->id); ?>"></canvas>
                        </div>
                        <div class="col-6">
                            <h5>Chuyển động</h5>
                            <canvas id="pirChart-<?php echo e($device->id); ?>"></canvas>
                        </div>
                        <div class="col-12 mt-3">
                            <h5>Ánh sáng</h5>
                            <p>Cường độ ánh sáng: <span><?php echo e($sensor->light_intensity ?? '--'); ?></span> lux</p>
                            <canvas id="LightChart-<?php echo e($device->id); ?>"></canvas>
                        </div>
                    </div>
                    <h5 class="mt-4">Chức năng</h5>
                    <div class="d-flex gap-3">
                        <?php $__currentLoopData = ['led' => 'Đèn', 'fan' => 'Quạt', 'buzzer' => 'Còi']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dev => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div>
                            <span><?php echo e($label); ?></span>
                            <label class="toggle-switch ms-2">
                                <input type="checkbox" class="send-command" data-device="<?php echo e($dev); ?>" data-esp-ip="<?php echo e($device->esp_ip); ?>">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<script>
    // Gắn lại toàn bộ JS dashboard cũ, cần sửa selector id cho từng device nếu dùng nhiều thiết bị
    $(document).on("change", ".send-command", function() {
        let esp_ip = $(this).data("esp-ip");
        let device = $(this).data("device");
        let status = $(this).is(":checked") ? "1" : "0";
        $.ajax({
            url: "/api/device-control", // Đổi endpoint cho phù hợp API mới
            method: "POST",
            data: {
                "select": device,
                "esp_ip": esp_ip,
                "status": status,
            },
            success: function(response) {
                console.log("ESP Response:", response);
            }
        });
    });
    var ws;
    function connectWebSocket() {
        let protocol = location.protocol === "https:" ? "wss://" : "ws://";
        ws = new WebSocket(protocol + location.hostname + ":8080");
        ws.onerror = function(err) { console.log(err); }
        ws.onopen = function() { console.log("ok"); }
        ws.onmessage = function(event) {
            console.log("Dữ liệu nhận được:", event.data);
            // Xử lý dữ liệu realtime cho từng device nếu cần
        };
        ws.onclose = function() {
            console.log('Mất kết nối, thử kết nối lại sau 5 giây...');
            setTimeout(connectWebSocket, 5000);
        };
    }
    // Bắt đầu kết nối WebSocket
    connectWebSocket();
    // TODO: Thêm code Chart.js cho từng device nếu muốn realtime
</script>
</body>
</html>
<?php /**PATH E:\temp\IOT\app\Views/monitoring.blade.php ENDPATH**/ ?>