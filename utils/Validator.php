<?php
/**
 * 数据验证工具类
 */
class Validator {
    
    /**
     * 验证卡密格式
     */
    public static function validateCardKey($cardKey) {
        if (empty($cardKey)) {
            return ['valid' => false, 'message' => '卡密不能为空'];
        }
        
        if (strlen($cardKey) < 6) {
            return ['valid' => false, 'message' => '卡密长度不能少于6位'];
        }
        
        if (strlen($cardKey) > 50) {
            return ['valid' => false, 'message' => '卡密长度不能超过50位'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * 验证设备ID
     */
    public static function validateDeviceId($deviceId) {
        if (empty($deviceId)) {
            return ['valid' => false, 'message' => '设备ID不能为空'];
        }
        
        if (strlen($deviceId) > 100) {
            return ['valid' => false, 'message' => '设备ID长度不能超过100位'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * 验证API密钥
     */
    public static function validateApiKey($apiKey) {
        if (empty($apiKey)) {
            return ['valid' => false, 'message' => 'API密钥不能为空'];
        }
        
        if (strlen($apiKey) !== 32) {
            return ['valid' => false, 'message' => 'API密钥格式不正确'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * 验证管理员用户名
     */
    public static function validateUsername($username) {
        if (empty($username)) {
            return ['valid' => false, 'message' => '用户名不能为空'];
        }
        
        if (strlen($username) < 3) {
            return ['valid' => false, 'message' => '用户名长度不能少于3位'];
        }
        
        if (strlen($username) > 20) {
            return ['valid' => false, 'message' => '用户名长度不能超过20位'];
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['valid' => false, 'message' => '用户名只能包含字母、数字和下划线'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * 验证密码
     */
    public static function validatePassword($password) {
        if (empty($password)) {
            return ['valid' => false, 'message' => '密码不能为空'];
        }
        
        if (strlen($password) < 6) {
            return ['valid' => false, 'message' => '密码长度不能少于6位'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * 清理输入数据
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * 验证CSRF令牌
     */
    public static function validateCsrfToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * 生成CSRF令牌
     */
    public static function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
