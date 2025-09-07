<?php
/**
 * API管理类
 */
class ApiManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * 生成API密钥
     */
    public function generateApiKey() {
        return bin2hex(random_bytes(16)); // 生成32位随机密钥
    }
    
    /**
     * 添加API密钥
     */
    public function addApiKey($name, $description = '') {
        $api_key = $this->generateApiKey();
        $stmt = $this->conn->prepare("INSERT INTO api_keys (api_key, name, description, status, use_count, last_use_time) VALUES (?, ?, ?, 1, 0, NULL)");
        $success = $stmt->execute([$api_key, $name, $description]);
        
        return [
            'success' => $success,
            'api_key' => $success ? $api_key : null,
            'message' => $success ? 'API密钥添加成功' : 'API密钥添加失败'
        ];
    }
    
    /**
     * 获取所有API密钥
     */
    public function getAllApiKeys() {
        $stmt = $this->conn->query("SELECT * FROM api_keys ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 更新API密钥状态
     */
    public function updateApiKeyStatus($id, $status) {
        $stmt = $this->conn->prepare("UPDATE api_keys SET status = ? WHERE id = ?");
        $success = $stmt->execute([$status, $id]);
        
        return [
            'success' => $success,
            'message' => $success ? 'API密钥状态更新成功' : 'API密钥状态更新失败'
        ];
    }
    
    /**
     * 删除API密钥
     */
    public function deleteApiKey($id) {
        $stmt = $this->conn->prepare("DELETE FROM api_keys WHERE id = ?");
        $success = $stmt->execute([$id]);
        
        return [
            'success' => $success,
            'message' => $success ? 'API密钥删除成功' : 'API密钥删除失败'
        ];
    }
    
    
    /**
     * 验证API密钥
     */
    public function validateApiKey($api_key) {
        $stmt = $this->conn->prepare("SELECT * FROM api_keys WHERE api_key = ? AND status = 1");
        $stmt->execute([$api_key]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 记录API调用
     */
    public function recordApiCall($api_key) {
        $stmt = $this->conn->prepare("UPDATE api_keys SET use_count = use_count + 1, last_use_time = NOW() WHERE api_key = ?");
        return $stmt->execute([$api_key]);
    }
    
    /**
     * 获取API密钥列表（用于管理页面）
     */
    public function getApiKeys() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM api_keys ORDER BY create_time DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }
    
    /**
     * 获取API设置
     */
    public function getApiSettings() {
        try {
            $stmt = $this->conn->prepare("SELECT name, value FROM settings WHERE name IN ('api_enabled', 'api_rate_limit', 'api_timeout')");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch(PDOException $e) {
            return [];
        }
    }
    
    /**
     * 更新API统计信息（添加最后调用时间）
     */
    public function getApiStats() {
        try {
            // 今日调用次数
            $today_start = date('Y-m-d 00:00:00');
            $today_end = date('Y-m-d 23:59:59');
            $stmt = $this->conn->prepare("SELECT SUM(use_count) FROM api_keys WHERE last_use_time BETWEEN ? AND ?");
            $stmt->execute([$today_start, $today_end]);
            $today_calls = (int)$stmt->fetchColumn() ?: 0;

            // 总调用次数
            $stmt = $this->conn->prepare("SELECT SUM(use_count) FROM api_keys");
            $stmt->execute();
            $total_calls = (int)$stmt->fetchColumn() ?: 0;

            // 活跃密钥数量
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM api_keys WHERE status = 1");
            $stmt->execute();
            $active_keys = (int)$stmt->fetchColumn();

            // 最后调用时间
            $stmt = $this->conn->prepare("SELECT MAX(last_use_time) FROM api_keys WHERE last_use_time IS NOT NULL");
            $stmt->execute();
            $last_call = $stmt->fetchColumn();
            
            return [
                'today_calls' => $today_calls,
                'total_calls' => $total_calls,
                'active_keys' => $active_keys,
                'last_call' => $last_call
            ];
        } catch(PDOException $e) {
            return [
                'today_calls' => 0,
                'total_calls' => 0,
                'active_keys' => 0,
                'last_call' => null
            ];
        }
    }
}
