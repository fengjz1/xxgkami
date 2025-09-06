<?php
require_once '../services/ApiService.php';

try {
    $apiService = new ApiService();
    $apiService->handleRequest();
} catch (Exception $e) {
    error_log("ApiService Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'code' => 3,
        'message' => '系统错误',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
}
