<?php
require_once __DIR__ . '/../models/CardModel.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Response.php';

/**
 * 卡密服务
 */
class CardService {
    private $cardModel;
    
    public function __construct() {
        $this->cardModel = new CardModel();
    }
    
    /**
     * 验证卡密
     */
    public function verifyCard($cardKey, $deviceId = null) {
        // 验证输入
        $cardValidation = Validator::validateCardKey($cardKey);
        if (!$cardValidation['valid']) {
            return ['code' => 1, 'message' => $cardValidation['message'], 'data' => null];
        }
        
        if ($deviceId) {
            $deviceValidation = Validator::validateDeviceId($deviceId);
            if (!$deviceValidation['valid']) {
                return ['code' => 1, 'message' => $deviceValidation['message'], 'data' => null];
            }
        }
        
        // 清理输入
        $cardKey = Validator::sanitize($cardKey);
        $deviceId = $deviceId ? Validator::sanitize($deviceId) : null;
        
        // 验证卡密
        $result = $this->cardModel->verifyCard($cardKey, $deviceId);
        
        if (!$result['valid']) {
            return ['code' => 1, 'message' => $result['message'], 'data' => null];
        }
        
        $card = $result['card'];
        
        // 处理不同状态的卡密
        if ($card['status'] == 0) {
            // 新卡密激活
            $this->cardModel->activateCard($card['id'], $deviceId, 'web');
            
            // 重新获取卡密信息
            $card = $this->cardModel->findByEncryptedKey($this->encryptCardKey($cardKey));
            
            return ['code' => 0, 'message' => '验证成功', 'data' => $this->formatCardData($card)];
        } else {
            // 已使用的卡密
            if (($card['device_id'] === $deviceId) || (empty($card['device_id']) && empty($deviceId))) {
                // 同一设备重复验证
                if ($card['allow_reverify']) {
                    if ($card['card_type'] == 'count' && $card['remaining_count'] > 0) {
                        $result = $this->cardModel->reverifyCard($card['id'], $deviceId);
                        if (is_array($result)) {
                            $card['remaining_count'] = $result['remaining'];
                        }
                        return ['code' => 0, 'message' => '验证成功，剩余次数：' . $card['remaining_count'], 'data' => $this->formatCardData($card)];
                    } else {
                        return ['code' => 0, 'message' => '验证成功(重复验证)', 'data' => $this->formatCardData($card)];
                    }
                } else {
                    return ['code' => 1, 'message' => '此卡密不允许重复验证', 'data' => null];
                }
            } else {
                // 其他设备尝试使用
                if (empty($card['device_id'])) {
                    // 卡密已被解绑，允许重新绑定
                    $this->cardModel->activateCard($card['id'], $deviceId, 'web');
                    $card = $this->cardModel->findByEncryptedKey($this->encryptCardKey($cardKey));
                    return ['code' => 0, 'message' => '验证成功 (重新绑定设备)', 'data' => $this->formatCardData($card)];
                } else {
                    return ['code' => 1, 'message' => '此卡密已被其他设备使用', 'data' => null];
                }
            }
        }
    }
    
    /**
     * 查询卡密信息
     */
    public function queryCard($cardKey) {
        // 验证输入
        $cardValidation = Validator::validateCardKey($cardKey);
        if (!$cardValidation['valid']) {
            return ['code' => 1, 'message' => $cardValidation['message'], 'data' => null];
        }
        
        // 清理输入
        $cardKey = Validator::sanitize($cardKey);
        
        // 查找卡密
        $card = $this->cardModel->findByEncryptedKey($this->encryptCardKey($cardKey));
        
        if (!$card) {
            return ['code' => 1, 'message' => '卡密不存在', 'data' => null];
        }
        
        return ['code' => 0, 'message' => '查询成功', 'data' => $this->formatCardData($card)];
    }
    
    /**
     * 获取统计信息
     */
    public function getStats() {
        return $this->cardModel->getStats();
    }
    
    /**
     * 格式化卡密数据
     */
    private function formatCardData($card) {
        // 将status转换为字符串格式以匹配API文档
        $statusMap = [
            0 => 'valid',    // 未使用
            1 => 'used',     // 已使用
            2 => 'disabled'  // 已停用
        ];
        
        return [
            'card_key' => $card['card_key'],
            'status' => $statusMap[$card['status']] ?? 'unknown',
            'use_time' => $card['use_time'],
            'expire_time' => $card['expire_time'],
            'card_type' => $card['card_type'],
            'duration' => $card['duration'],
            'total_count' => $card['total_count'],
            'remaining_count' => $card['remaining_count'],
            'device_id' => $card['device_id'],
            'allow_reverify' => $card['allow_reverify']
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
