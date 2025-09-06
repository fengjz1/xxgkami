<?php
require_once __DIR__ . '/../utils/Database.php';

/**
 * 设置模型
 */
class SettingsModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * 获取所有设置
     */
    public function getAllSettings() {
        $sql = "SELECT name, value FROM settings";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * 获取设置值
     */
    public function getSetting($name, $default = null) {
        $sql = "SELECT value FROM settings WHERE name = ?";
        $value = $this->db->fetchColumn($sql, [$name]);
        return $value !== false ? $value : $default;
    }
    
    /**
     * 设置值
     */
    public function setSetting($name, $value) {
        $sql = "INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?";
        $this->db->query($sql, [$name, $value, $value]);
    }
    
    /**
     * 获取网站设置
     */
    public function getWebsiteSettings() {
        $settings = $this->getAllSettings();
        $result = [];
        
        foreach ($settings as $setting) {
            $result[$setting['name']] = $setting['value'];
        }
        
        return [
            'site_title' => $result['site_title'] ?? '小小怪卡密验证系统',
            'site_subtitle' => $result['site_subtitle'] ?? '专业的卡密验证解决方案',
            'copyright_text' => $result['copyright_text'] ?? '小小怪卡密系统 - All Rights Reserved',
            'contact_qq_group' => $result['contact_qq_group'] ?? '123456789',
            'contact_wechat_qr' => $result['contact_wechat_qr'] ?? 'assets/images/wechat-qr.jpg',
            'contact_email' => $result['contact_email'] ?? 'support@example.com'
        ];
    }
    
    /**
     * 获取轮播图
     */
    public function getSlides() {
        $sql = "SELECT * FROM slides WHERE status = 1 ORDER BY sort_order ASC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * 获取系统特点
     */
    public function getFeatures() {
        $sql = "SELECT * FROM features WHERE status = 1 ORDER BY sort_order ASC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * 验证管理员登录
     */
    public function validateAdmin($username, $password) {
        $sql = "SELECT id, username, password FROM admins WHERE username = ?";
        $admin = $this->db->fetchOne($sql, [$username]);
        
        if (!$admin) {
            return false;
        }
        
        if (!password_verify($password, $admin['password'])) {
            return false;
        }
        
        // 更新最后登录时间
        $this->db->query("UPDATE admins SET last_login = NOW() WHERE id = ?", [$admin['id']]);
        
        return $admin;
    }
}
