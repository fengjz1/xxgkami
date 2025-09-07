<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../utils/Validator.php';

/**
 * 首页控制器
 */
class HomeController extends BaseController {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * 显示首页
     */
    public function index() {
        $slides = $this->settingsModel->getSlides();
        $features = $this->settingsModel->getFeatures();
        
        $data = [
            'slides' => $slides,
            'features' => $features,
            'csrf_token' => Validator::generateCsrfToken()
        ];
        
        echo $this->renderWithLayout('home', $data);
    }
}
