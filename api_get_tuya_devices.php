<?php
// api_get_tuya_devices.php
header('Content-Type: application/json');
include 'config.php'; // Kết nối MySQL database của khách sạn nếu cần
include 'tuya_config.php';

$token = getTuyaToken();
if (!$token) {
    echo json_encode(["status" => "error", "message" => "Không lấy được Token"]);
    exit();
}

// Giả sử bạn có danh sách ID thiết bị cần quản lý (Bạn có thể nạp từ Database MySQL lên thay vì viết cố định thế này)
$device_list = [
    [
        "id" => 1,
        "device_id" => "eb9530c1bda34fc126kdqn", 
        "device_name" => "Cửa phòng",
        "device_type" => "sensor"
    ],
    [
        "id" => 2,
        "device_id" => "eb5bd98332c838c398ovin",
        "device_name" => "Đèn bếp",
        "device_type" => "switch"
    ]
];

$output_devices = [];

foreach ($device_list as $dev) {
    $timestamp = time() * 1000;
    $reqUrl = "/v1.0/devices/" . $dev['device_id'] . "/status";
    
    // Tạo chữ ký SHA256 cho lệnh gọi trạng thái thiết bị
    $stringToSign = TUYA_CLIENT_ID . $token . $timestamp . "GET\n" . "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855\n" . "\n" . $reqUrl;
    $sign = strtoupper(hash_hmac('sha256', $stringToSign, TUYA_SECRET));

    $ch = curl_init(TUYA_API_URL . $reqUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "client_id: " . TUYA_CLIENT_ID,
        "access_token: " . $token,
        "sign: " . $sign,
        "t: " . $timestamp,
        "sign_method: HMACS256"
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    $statusData = json_decode($response, true);
    
    // Mặc định trạng thái ban đầu
    $current_status = ($dev['device_type'] === 'sensor') ? 'Đóng' : 'Tắt';

    if (isset($statusData['result'])) {
        foreach ($statusData['result'] as $datapoint) {
            // Đối với cảm biến cửa Tuya: code là 'doorcontact_state' (true = mở, false = đóng)
            if ($datapoint['code'] === 'doorcontact_state') {
                $current_status = ($datapoint['value'] == true) ? 'Mở' : 'Đóng';
            }
            // Đối với công tắc Tuya: code thường là 'switch_1' hoặc 'switch' (true = bật, false = tắt)
            if ($datapoint['code'] === 'switch_1' || $datapoint['code'] === 'switch') {
                $current_status = ($datapoint['value'] == true) ? 'Bật' : 'Tắt';
            }
        }
    }

    $output_devices[] = [
        "id" => $dev['id'],
        "device_id" => $dev['device_id'],
        "device_name" => $dev['device_name'],
        "device_type" => $dev['device_type'],
        "status" => $current_status
    ];
}

// Trả về chuỗi JSON chuẩn cho Dashboard đọc
echo json_encode(["devices" => $output_devices]);
