<?php
/**
 * 设置管理类
 */
class SettingsManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * 获取设置值
     */
    public function getSetting($name, $default = '') {
        $stmt = $this->conn->prepare("SELECT value FROM settings WHERE name = ?");
        $stmt->execute([$name]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : $default;
    }
    
    /**
     * 设置值
     */
    public function setSetting($name, $value) {
        $stmt = $this->conn->prepare("INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
        return $stmt->execute([$name, $value, $value]);
    }
    
    /**
     * 获取所有设置
     */
    public function getAllSettings() {
        $stmt = $this->conn->query("SELECT name, value FROM settings");
        $settings = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['name']] = $row['value'];
        }
        return $settings;
    }
    
    /**
     * 更新密码
     */
    public function updatePassword($admin_id, $old_password, $new_password) {
        // 验证旧密码
        $stmt = $this->conn->prepare("SELECT password FROM admins WHERE id = ?");
        $stmt->execute([$admin_id]);
        $admin = $stmt->fetch();
        
        if(!password_verify($old_password, $admin['password'])) {
            return ['success' => false, 'message' => '旧密码错误'];
        }
        
        if(strlen($new_password) < 6) {
            return ['success' => false, 'message' => '密码长度不能小于6位'];
        }
        
        // 更新密码
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
        $success = $stmt->execute([$new_hash, $admin_id]);
        
        return [
            'success' => $success,
            'message' => $success ? '密码修改成功' : '密码修改失败'
        ];
    }
    
    /**
     * 更新基础设置
     */
    public function updateBasicSettings($data) {
        $settings = [
            'site_title' => $data['site_title'] ?? '',
            'site_subtitle' => $data['site_subtitle'] ?? '',
            'contact_qq' => $data['contact_qq'] ?? '',
            'contact_wechat' => $data['contact_wechat'] ?? '',
            'contact_email' => $data['contact_email'] ?? ''
        ];
        
        $success = true;
        foreach($settings as $name => $value) {
            if(!$this->setSetting($name, $value)) {
                $success = false;
            }
        }
        
        return [
            'success' => $success,
            'message' => $success ? '基础设置更新成功' : '基础设置更新失败'
        ];
    }
    
    /**
     * 更新API设置
     */
    public function updateApiSettings($data) {
        $settings = [
            'api_enabled' => $data['api_enabled'] ?? '0',
            'api_rate_limit' => $data['api_rate_limit'] ?? '100',
            'api_timeout' => $data['api_timeout'] ?? '30'
        ];
        
        $success = true;
        foreach($settings as $name => $value) {
            if(!$this->setSetting($name, $value)) {
                $success = false;
            }
        }
        
        return [
            'success' => $success,
            'message' => $success ? 'API设置更新成功' : 'API设置更新失败'
        ];
    }
    
    /**
     * 获取轮播图列表
     */
    public function getSlides() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM slides ORDER BY sort_order ASC, id ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }
    
    /**
     * 获取系统特点列表
     */
    public function getFeatures() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM features ORDER BY sort_order ASC, id ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }
    
    /**
     * 获取系统信息
     */
    public function getSystemInfo() {
        $info = [
            'php_version' => PHP_VERSION,
            'mysql_version' => '未知',
            'install_time' => file_exists("../install.lock") ? date('Y-m-d H:i:s', filemtime("../install.lock")) : "未知"
        ];
        
        try {
            $info['mysql_version'] = $this->conn->getAttribute(PDO::ATTR_SERVER_VERSION);
        } catch(PDOException $e) {
            // 保持默认值
        }
        
        return $info;
    }
}
