<?php
session_start();
if(!isset($_SESSION['admin_id'])){
    die(json_encode(['success' => false, 'message' => '未登录']));
}

require_once '../config.php';
require_once 'classes/CardManager.php';

/**
 * 卡密操作API控制器
 */
class CardActionsController {
    private $cardManager;
    
    public function __construct($conn) {
        $this->cardManager = new CardManager($conn);
    }
    
    /**
     * 处理请求
     */
    public function handleRequest() {
        // 获取POST数据
        $input = json_decode(file_get_contents('php://input'), true);
        if(!isset($input['action'])) {
            $this->sendResponse(false, '无效的请求');
            return;
        }
        
        $action = $input['action'];
        $card_id = intval($input['card_id'] ?? 0);
        
        if($card_id <= 0) {
            $this->sendResponse(false, '无效的卡密ID');
            return;
        }
        
        switch($action) {
            case 'disable':
                $this->handleDisable($card_id);
                break;
                
            case 'enable':
                $this->handleEnable($card_id);
                break;
                
            case 'update_expire_time':
                $this->handleUpdateExpireTime($card_id, $input['expire_time'] ?? '');
                break;
                
            case 'update_remaining_count':
                $this->handleUpdateRemainingCount($card_id, $input['remaining_count'] ?? 0);
                break;
                
            case 'delete_used':
                $this->handleDeleteUsed($card_id);
                break;
                
            case 'toggle_reverify':
                $this->handleToggleReverify($card_id, $input['allow_reverify'] ?? 0);
                break;
                
            case 'unbind_device':
                $this->handleUnbindDevice($card_id);
                break;
                
            default:
                $this->sendResponse(false, '未知的操作类型');
        }
    }
    
    /**
     * 停用卡密
     */
    private function handleDisable($card_id) {
        try {
            $success = $this->cardManager->updateCardStatus($card_id, 2);
            $this->sendResponse($success, $success ? '卡密已停用' : '停用失败');
        } catch(Exception $e) {
            $this->sendResponse(false, '停用失败：' . $e->getMessage());
        }
    }
    
    /**
     * 启用卡密
     */
    private function handleEnable($card_id) {
        try {
            $success = $this->cardManager->updateCardStatus($card_id, 1);
            $this->sendResponse($success, $success ? '卡密已启用' : '启用失败');
        } catch(Exception $e) {
            $this->sendResponse(false, '启用失败：' . $e->getMessage());
        }
    }
    
    /**
     * 修改到期时间
     */
    private function handleUpdateExpireTime($card_id, $expire_time) {
        if(empty($expire_time)) {
            $this->sendResponse(false, '到期时间不能为空');
            return;
        }
        
        try {
            $success = $this->cardManager->updateExpireTime($card_id, $expire_time);
            $this->sendResponse($success, $success ? '到期时间已更新' : '更新失败');
        } catch(Exception $e) {
            $this->sendResponse(false, '更新失败：' . $e->getMessage());
        }
    }
    
    /**
     * 修改剩余次数
     */
    private function handleUpdateRemainingCount($card_id, $remaining_count) {
        $remaining_count = intval($remaining_count);
        if($remaining_count < 0) {
            $this->sendResponse(false, '剩余次数不能为负数');
            return;
        }
        
        try {
            $success = $this->cardManager->updateRemainingCount($card_id, $remaining_count);
            $this->sendResponse($success, $success ? '剩余次数已更新' : '更新失败');
        } catch(Exception $e) {
            $this->sendResponse(false, '更新失败：' . $e->getMessage());
        }
    }
    
    /**
     * 删除已使用的卡密
     */
    private function handleDeleteUsed($card_id) {
        try {
            $success = $this->cardManager->deleteCard($card_id);
            $this->sendResponse($success, $success ? '卡密已删除' : '删除失败');
        } catch(Exception $e) {
            $this->sendResponse(false, '删除失败：' . $e->getMessage());
        }
    }
    
    /**
     * 切换允许重复验证状态
     */
    private function handleToggleReverify($card_id, $allow_reverify) {
        $allow_reverify = intval($allow_reverify);
        $allow_reverify = ($allow_reverify === 1) ? 1 : 0;
        
        try {
            $success = $this->cardManager->updateReverifyStatus($card_id, $allow_reverify);
            $message = $success ? 
                ('设置成功，现在' . ($allow_reverify ? '允许' : '禁止') . '重复验证') : 
                '设置失败';
            $this->sendResponse($success, $message);
        } catch(Exception $e) {
            $this->sendResponse(false, '设置失败：' . $e->getMessage());
        }
    }
    
    /**
     * 解绑设备
     */
    private function handleUnbindDevice($card_id) {
        try {
            $success = $this->cardManager->unbindDevice($card_id);
            $this->sendResponse($success, $success ? '设备已成功解绑' : '解绑失败或卡密状态不允许解绑');
        } catch(Exception $e) {
            $this->sendResponse(false, '解绑失败：' . $e->getMessage());
        }
    }
    
    /**
     * 发送响应
     */
    private function sendResponse($success, $message) {
        echo json_encode([
            'success' => $success,
            'message' => $message
        ]);
    }
}

// 处理请求
try {
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $controller = new CardActionsController($conn);
    $controller->handleRequest();
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '系统错误：' . $e->getMessage()
    ]);
}
?>