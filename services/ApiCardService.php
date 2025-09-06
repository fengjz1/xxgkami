<?php
require_once __DIR__ . '/CardService.php';
require_once __DIR__ . '/../utils/Response.php';

/**
 * API卡密服务 - 专门用于API接口，直接输出JSON
 */
class ApiCardService extends CardService {
    
    /**
     * 验证卡密 (API版本)
     */
    public function verifyCard($cardKey, $deviceId = null) {
        $result = parent::verifyCard($cardKey, $deviceId);
        
        if ($result['code'] === 0) {
            Response::success($result['message'], $result['data']);
        } else {
            Response::error($result['message'], $result['code']);
        }
    }
    
    /**
     * 查询卡密信息 (API版本)
     */
    public function queryCard($cardKey) {
        $result = parent::queryCard($cardKey);
        
        if ($result['code'] === 0) {
            Response::success($result['message'], $result['data']);
        } else {
            Response::error($result['message'], $result['code']);
        }
    }
}
