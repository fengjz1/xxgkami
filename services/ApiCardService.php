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
            // 业务逻辑错误返回200状态码，通过JSON中的code字段区分
            Response::json([
                'code' => $result['code'],
                'message' => $result['message'],
                'data' => $result['data']
            ], 200);
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
            // 业务逻辑错误返回200状态码，通过JSON中的code字段区分
            Response::json([
                'code' => $result['code'],
                'message' => $result['message'],
                'data' => $result['data']
            ], 200);
        }
    }
}
