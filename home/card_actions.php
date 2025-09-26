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
        // 检查是否是POST请求的导出功能
        if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'export') {
            $this->handleExport();
            return;
        }
        
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
     * 处理导出功能
     */
    private function handleExport() {
        try {
            // 获取选中的卡密ID
            $card_ids = $_POST['card_ids'] ?? [];
            
            // 获取筛选条件
            $search_filter = $_POST['search_filter'] ?? '';
            $status_filter = $_POST['status_filter'] ?? '';
            $type_filter = $_POST['type_filter'] ?? '';
            
            // 获取文件名
            $file_name = $_POST['file_name'] ?? '卡密列表';
            if(!preg_match('/^[a-zA-Z0-9_\-\x{4e00}-\x{9fa5}]+$/u', $file_name)) {
                $file_name = '卡密列表';
            }
            
            // 根据筛选条件获取卡密数据
            $cards = $this->cardManager->getCardsForExport($card_ids, $search_filter, $status_filter, $type_filter);
            if(empty($cards)) {
                $this->showError('没有找到符合筛选条件的卡密数据');
                return;
            }
            
            // 生成Excel文件
            $this->generateExcel($cards, $file_name);
            
        } catch(Exception $e) {
            error_log('导出失败: ' . $e->getMessage());
            $this->showError('导出失败，请稍后重试');
        }
    }
    
    /**
     * 生成Excel文件
     */
    private function generateExcel($cards, $file_name) {
        // 设置响应头 - 使用CSV格式
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $file_name . '.csv"');
        header('Cache-Control: max-age=0');
        
        // 创建临时文件
        $temp_file = tempnam(sys_get_temp_dir(), 'export_');
        
        try {
            // 创建CSV内容
            $csv_content = $this->generateCSV($cards);
            
            // 写入临时文件
            file_put_contents($temp_file, $csv_content);
            
            // 输出文件内容
            readfile($temp_file);
            
        } finally {
            // 清理临时文件
            if(file_exists($temp_file)) {
                unlink($temp_file);
            }
        }
    }
    
    /**
     * 生成CSV内容
     */
    private function generateCSV($cards) {
        $csv = "\xEF\xBB\xBF"; // UTF-8 BOM
        $csv .= "ID,卡密,状态,类型,有效期/剩余次数,使用时间,到期时间,创建时间,设备ID,允许重复验证\n";
        
        foreach($cards as $card) {
            $status = $this->getStatusText($card['status']);
            $type = $this->getTypeText($card['card_type']);
            $duration = $this->getDurationText($card);
            $used_time = $card['use_time'] ? date('Y-m-d H:i:s', strtotime($card['use_time'])) : '';
            $expire_time = $card['expire_time'] ? date('Y-m-d H:i:s', strtotime($card['expire_time'])) : '';
            $created_time = date('Y-m-d H:i:s', strtotime($card['create_time']));
            $device_id = $card['device_id'] ?: '';
            $allow_reverify = $card['allow_reverify'] ? '是' : '否';
            
            $csv .= sprintf("%d,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $card['id'],
                $card['card_key'],
                $status,
                $type,
                $duration,
                $used_time,
                $expire_time,
                $created_time,
                $device_id,
                $allow_reverify
            );
        }
        
        return $csv;
    }
    
    /**
     * 获取状态文本
     */
    private function getStatusText($status) {
        switch($status) {
            case 0: return '未使用';
            case 1: return '已使用';
            case 2: return '已停用';
            default: return '未知';
        }
    }
    
    /**
     * 获取类型文本
     */
    private function getTypeText($type) {
        switch($type) {
            case 'time': return '时间卡';
            case 'count': return '次数卡';
            default: return '未知';
        }
    }
    
    /**
     * 获取有效期/剩余次数文本
     */
    private function getDurationText($card) {
        if($card['card_type'] == 'time') {
            // 时间卡
            $days = $card['duration'] ?? 0;
            return $days . '天';
        } else {
            // 次数卡
            $count = $card['remaining_count'] ?? 0;
            return $count . '次';
        }
    }
    
    /**
     * 显示错误页面
     */
    private function showError($message) {
        echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>导出失败</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error { color: #d32f2f; font-size: 18px; }
        .back-btn { margin-top: 20px; }
        .back-btn a { color: #1976d2; text-decoration: none; }
    </style>
</head>
<body>
    <div class="error">' . htmlspecialchars($message) . '</div>
    <div class="back-btn">
        <a href="javascript:history.back()">返回上一页</a>
    </div>
</body>
</html>';
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