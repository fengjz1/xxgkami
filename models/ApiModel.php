<?php
require_once __DIR__ . '/../utils/Database.php';

/**
 * API模型
 */
class ApiModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * 检查API是否启用
     */
    public function isApiEnabled() {
        $enabled = $this->db->fetchColumn("SELECT value FROM settings WHERE name = 'api_enabled'");
        return $enabled === '1' || $enabled === 1;
    }
    
    /**
     * 验证API密钥
     */
    public function validateApiKey($apiKey) {
        $sql = "SELECT id FROM api_keys WHERE api_key = ? AND status = 1";
        $result = $this->db->fetchOne($sql, [$apiKey]);
        return $result !== false;
    }
    
    /**
     * 记录API调用
     */
    public function recordApiCall($apiKey) {
        $sql = "UPDATE api_keys SET use_count = use_count + 1, last_use_time = NOW() WHERE api_key = ?";
        $this->db->query($sql, [$apiKey]);
    }
    
    /**
     * 获取API统计信息
     */
    public function getApiStats() {
        $today = date('Y-m-d');
        $thisMonth = date('Y-m');
        
        $todayCalls = $this->db->fetchColumn(
            "SELECT SUM(use_count) FROM api_keys WHERE DATE(last_use_time) = ?",
            [$today]
        ) ?: 0;
        
        $monthCalls = $this->db->fetchColumn(
            "SELECT SUM(use_count) FROM api_keys WHERE DATE_FORMAT(last_use_time, '%Y-%m') = ?",
            [$thisMonth]
        ) ?: 0;
        
        $totalCalls = $this->db->fetchColumn("SELECT SUM(use_count) FROM api_keys") ?: 0;
        
        $activeKeys = $this->db->fetchColumn("SELECT COUNT(*) FROM api_keys WHERE status = 1");
        
        return [
            'today_calls' => $todayCalls,
            'month_calls' => $monthCalls,
            'total_calls' => $totalCalls,
            'active_keys' => $activeKeys
        ];
    }
}
