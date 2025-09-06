<?php
/**
 * 响应处理工具类
 */
class Response {
    
    /**
     * 返回JSON响应
     */
    public static function json($data, $httpCode = 200) {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * 返回成功响应
     */
    public static function success($message, $data = null, $httpCode = 200) {
        self::json([
            'code' => 0,
            'message' => $message,
            'data' => $data
        ], $httpCode);
    }
    
    /**
     * 返回错误响应
     */
    public static function error($message, $code = 1, $httpCode = 400) {
        self::json([
            'code' => $code,
            'message' => $message,
            'data' => null
        ], $httpCode);
    }
    
    /**
     * 重定向
     */
    public static function redirect($url) {
        header("Location: {$url}");
        exit;
    }
    
    /**
     * 设置CORS头
     */
    public static function setCorsHeaders() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-API-KEY');
        header('X-Powered-By: 小小怪卡密系统');
    }
    
    /**
     * 处理OPTIONS请求
     */
    public static function handleOptions() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            self::setCorsHeaders();
            exit;
        }
    }
}
