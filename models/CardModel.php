<?php
require_once __DIR__ . '/../utils/Database.php';

/**
 * 卡密模型
 */
class CardModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * 根据加密后的卡密查找卡密
     */
    public function findByEncryptedKey($encryptedKey) {
        $sql = "SELECT * FROM cards WHERE encrypted_key = ?";
        return $this->db->fetchOne($sql, [$encryptedKey]);
    }
    
    /**
     * 验证卡密
     */
    public function verifyCard($cardKey, $deviceId = null) {
        $encryptedKey = $this->encryptCardKey($cardKey);
        $card = $this->findByEncryptedKey($encryptedKey);
        
        if (!$card) {
            return ['valid' => false, 'message' => '无效的卡密'];
        }
        
        // 检查卡密状态
        if ($card['status'] == 2) {
            return ['valid' => false, 'message' => '此卡密已被管理员禁用'];
        }
        
        // 检查次数卡密的剩余次数
        if ($card['card_type'] == 'count' && $card['status'] == 1 && $card['remaining_count'] <= 0) {
            return ['valid' => false, 'message' => '此卡密使用次数已用完'];
        }
        
        // 检查设备绑定
        if ($card['status'] == 1 && !empty($card['device_id']) && $deviceId !== null && $card['device_id'] !== $deviceId) {
            return ['valid' => false, 'message' => '此卡密已被其他设备使用'];
        }
        
        return ['valid' => true, 'card' => $card];
    }
    
    /**
     * 激活卡密
     */
    public function activateCard($cardId, $deviceId, $verifyMethod = 'web') {
        $card = $this->db->fetchOne("SELECT * FROM cards WHERE id = ?", [$cardId]);
        
        if (!$card) {
            return false;
        }
        
        if ($card['card_type'] == 'time') {
            // 时间卡密处理
            $expireTime = null;
            if ($card['duration'] > 0) {
                $expireTime = date('Y-m-d H:i:s', strtotime("+{$card['duration']} days"));
            }
            
            $sql = "UPDATE cards SET status = 1, use_time = NOW(), expire_time = ?, verify_method = ?, device_id = ? WHERE id = ?";
            $this->db->query($sql, [$expireTime, $verifyMethod, $deviceId, $cardId]);
        } else {
            // 次数卡密处理，首次验证也消耗一次
            $remaining = $card['total_count'] - 1;
            
            $sql = "UPDATE cards SET status = 1, use_time = NOW(), verify_method = ?, device_id = ?, remaining_count = ? WHERE id = ?";
            $this->db->query($sql, [$verifyMethod, $deviceId, $remaining, $cardId]);
        }
        
        return true;
    }
    
    /**
     * 重复验证卡密
     */
    public function reverifyCard($cardId, $deviceId) {
        $card = $this->db->fetchOne("SELECT * FROM cards WHERE id = ?", [$cardId]);
        
        if (!$card || ($deviceId !== null && $card['device_id'] !== $deviceId)) {
            return false;
        }
        
        if ($card['card_type'] == 'count' && $card['remaining_count'] > 0) {
            // 次数卡密减少次数
            $remaining = $card['remaining_count'] - 1;
            $sql = "UPDATE cards SET remaining_count = ? WHERE id = ?";
            $this->db->query($sql, [$remaining, $cardId]);
            return ['remaining' => $remaining];
        }
        
        return true;
    }
    
    /**
     * 解绑设备
     */
    public function unbindDevice($cardId) {
        $sql = "UPDATE cards SET device_id = NULL WHERE id = ?";
        return $this->db->query($sql, [$cardId])->rowCount() > 0;
    }
    
    /**
     * 获取卡密统计信息
     */
    public function getStats() {
        $total = $this->db->fetchColumn("SELECT COUNT(*) FROM cards");
        $used = $this->db->fetchColumn("SELECT COUNT(*) FROM cards WHERE status = 1");
        $unused = $total - $used;
        $usageRate = $total > 0 ? round(($used / $total) * 100, 1) : 0;
        
        return [
            'total' => $total,
            'used' => $used,
            'unused' => $unused,
            'usage_rate' => $usageRate
        ];
    }
    
    /**
     * 加密卡密
     */
    private function encryptCardKey($key) {
        $salt = 'xiaoxiaoguai_card_system_2024';
        return sha1($key . $salt);
    }
}
