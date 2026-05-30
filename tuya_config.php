<?php
// tuya_config.php
define('TUYA_CLIENT_ID', 'qap98nweqkmufpdp5d3r');
define('TUYA_SECRET', 'cb7684adc56045bdb5f77c1d7a541d48');
define('TUYA_API_URL', 'https://openapi.tuyaus.com'); // Thay đổi vùng tùy tài khoản Tuya của bạn (us/eu/cn)

function getTuyaToken() {
    $timestamp = time() * 1000;
    $signUrl = '/v1.0/token?grant_type=1';
    
    // Tạo chữ ký bảo mật theo thuật toán của Tuya
    $stringToSign = TUYA_CLIENT_ID . $timestamp . "GET\n" . "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855\n" . "\n" . $signUrl;
    $sign = strtoupper(hash_hmac('sha256', $stringToSign, TUYA_SECRET));

    $ch = curl_init(TUYA_API_URL . $signUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "client_id: " . TUYA_CLIENT_ID,
        "sign: " . $sign,
        "t: " . $timestamp,
        "sign_method: HMACS256"
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    $resData = json_decode($response, true);
    
    return $resData['result']['access_token'] ?? null;
}
?>
