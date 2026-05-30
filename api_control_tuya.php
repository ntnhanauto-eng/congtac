<?php
// api_control_tuya.php
header('Content-Type: application/json');
include 'tuya_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['device_id'])) {
    $device_id = $_POST['device_id'];
    $command_status = $_POST['command_status']; // 'Bật' hoặc 'Tắt'
    
    $token = getTuyaToken();
    if (!$token) {
        echo json_encode(["status" => "error", "message" => "Lỗi token"]);
        exit();
    }

    // Quy đổi trạng thái chữ sang giá trị Boolean của Tuya Cloud
    $value_boolean = ($command_status === 'Bật') ? true : false;
    
    // Gói lệnh gửi đi (Bật/Tắt switch_1)
    $payload = json_encode([
        "commands" => [
            [
                "code" => "switch_1", // Tùy thiết bị, có thể là 'switch' hoặc 'switch_1'
                "value" => $value_boolean
            ]
        ]
    ]);

    $timestamp = time() * 1000;
    $reqUrl = "/v1.0/devices/" . $device_id . "/commands";
    $bodyHash = hash('sha256', $payload);
    
    // Tạo chữ ký gửi lệnh POST
    $stringToSign = TUYA_CLIENT_ID . $token . $timestamp . "POST\n" . $bodyHash . "\n" . "\n" . $reqUrl;
    $sign = strtoupper(hash_hmac('sha256', $stringToSign, TUYA_SECRET));

    $ch = curl_init(TUYA_API_URL . $reqUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "client_id: " . TUYA_CLIENT_ID,
        "access_token: " . $token,
        "sign: " . $sign,
        "t: " . $timestamp,
        "sign_method: HMACS256",
        "Content-Type: application/json"
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    echo json_encode(["status" => "success", "raw" => json_decode($response, true)]);
    exit();
}
