<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../utils/Validator.php';

/**
 * 管理员登录控制器
 */
class AdminLoginController extends BaseController {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * 显示登录页面
     */
    public function index() {
        // 如果已经登录，重定向到管理后台
        if (isset($_SESSION['admin_id'])) {
            Response::redirect('home/index.php');
        }
        
        $data = [
            'csrf_token' => Validator::generateCsrfToken()
        ];
        
        echo $this->renderWithLayout('admin_login', $data);
    }
    
    /**
     * 处理登录
     */
    public function login() {
        if (!$this->isPost()) {
            Response::redirect('admin.php');
        }
        
        // 验证CSRF令牌
        $csrfToken = $this->getPostData('csrf_token');
        if (!Validator::validateCsrfToken($csrfToken)) {
            $this->showError('安全验证失败，请重新提交');
            return;
        }
        
        $username = $this->getPostData('username');
        $password = $this->getPostData('password');
        
        // 验证输入
        $usernameValidation = Validator::validateUsername($username);
        if (!$usernameValidation['valid']) {
            $this->showError($usernameValidation['message']);
            return;
        }
        
        $passwordValidation = Validator::validatePassword($password);
        if (!$passwordValidation['valid']) {
            $this->showError($passwordValidation['message']);
            return;
        }
        
        // 清理输入
        $username = Validator::sanitize($username);
        
        // 验证管理员登录
        $admin = $this->settingsModel->validateAdmin($username, $password);
        
        if (!$admin) {
            $this->showError('用户名或密码错误');
            return;
        }
        
        // 设置会话
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['username'];
        
        // 重定向到管理后台
        Response::redirect('home/index.php');
    }
    
    /**
     * 显示错误信息
     */
    private function showError($message) {
        $data = [
            'csrf_token' => Validator::generateCsrfToken(),
            'error' => $message
        ];
        
        echo $this->renderWithLayout('admin_login', $data);
    }
}
