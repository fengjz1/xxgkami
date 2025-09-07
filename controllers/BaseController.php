<?php
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../models/SettingsModel.php';
require_once __DIR__ . '/../utils/TimeHelper.php';

/**
 * 基础控制器
 */
abstract class BaseController {
    protected $settingsModel;
    protected $settings;
    
    public function __construct() {
        $this->checkInstallation();
        $this->settingsModel = new SettingsModel();
        $this->settings = $this->settingsModel->getWebsiteSettings();
    }
    
    /**
     * 检查安装状态
     */
    protected function checkInstallation() {
        if (!file_exists(__DIR__ . '/../install.lock')) {
            Response::redirect('install/index.php');
        }
        
        if (!file_exists(__DIR__ . '/../config.php') || filesize(__DIR__ . '/../config.php') === 0) {
            Response::redirect('install/index.php');
        }
        
        require_once __DIR__ . '/../config.php';
        
        if (!defined('DB_HOST')) {
            Response::redirect('install/index.php');
        }
    }
    
    /**
     * 渲染页面
     */
    protected function render($view, $data = []) {
        extract($data);
        $settings = $this->settings;
        
        ob_start();
        include __DIR__ . '/../views/pages/' . $view . '.php';
        return ob_get_clean();
    }
    
    /**
     * 渲染布局
     */
    protected function renderWithLayout($view, $data = []) {
        $content = $this->render($view, $data);
        $settings = $this->settings;
        
        ob_start();
        include __DIR__ . '/../views/layouts/FrontendLayout.php';
        return ob_get_clean();
    }
    
    /**
     * 获取POST数据
     */
    protected function getPostData($key, $default = null) {
        return $_POST[$key] ?? $default;
    }
    
    /**
     * 获取GET数据
     */
    protected function getGetData($key, $default = null) {
        return $_GET[$key] ?? $default;
    }
    
    /**
     * 检查是否为POST请求
     */
    protected function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    /**
     * 检查是否为GET请求
     */
    protected function isGet() {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
}
