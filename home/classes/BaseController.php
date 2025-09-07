<?php
/**
 * 基础控制器类
 */
class BaseController {
    protected $conn;
    protected $layout;
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->layout = new AdminLayout();
    }
    
    /**
     * 检查管理员登录状态
     */
    protected function checkAuth() {
        if(!isset($_SESSION['admin_id'])) {
            header("Location: ../admin.php");
            exit;
        }
    }
    
    /**
     * 处理成功消息
     */
    protected function setSuccess($message) {
        $_SESSION['success_message'] = $message;
    }
    
    /**
     * 处理错误消息
     */
    protected function setError($message) {
        $_SESSION['error_message'] = $message;
    }
    
    /**
     * 获取并清除消息
     */
    protected function getMessages() {
        $messages = [];
        
        if(isset($_SESSION['success_message'])) {
            $messages['success'] = $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        }
        
        if(isset($_SESSION['error_message'])) {
            $messages['error'] = $_SESSION['error_message'];
            unset($_SESSION['error_message']);
        }
        
        return $messages;
    }
    
    /**
     * 渲染消息提示
     */
    protected function renderMessages() {
        $messages = $this->getMessages();
        $html = '';
        
        if(isset($messages['success'])) {
            $html .= '<div class="alert alert-success">' . htmlspecialchars($messages['success']) . '</div>';
        }
        
        if(isset($messages['error'])) {
            $html .= '<div class="alert alert-error">' . htmlspecialchars($messages['error']) . '</div>';
        }
        
        return $html;
    }
    
    /**
     * 重定向
     */
    protected function redirect($url) {
        header("Location: $url");
        exit;
    }
    
    /**
     * 返回JSON响应
     */
    protected function jsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * 验证POST数据
     */
    protected function validatePost($required = []) {
        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return false;
        }
        
        foreach($required as $field) {
            if(!isset($_POST[$field]) || empty($_POST[$field])) {
                $this->setError("缺少必要字段: $field");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 获取POST数据
     */
    protected function getPostData($fields = []) {
        $data = [];
        foreach($fields as $field) {
            $data[$field] = $_POST[$field] ?? '';
        }
        return $data;
    }
    
    /**
     * 安全输出HTML
     */
    protected function escape($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}
