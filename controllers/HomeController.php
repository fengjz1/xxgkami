<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../services/CardService.php';
require_once __DIR__ . '/../utils/Validator.php';

/**
 * 首页控制器
 */
class HomeController extends BaseController {
    private $cardService;
    
    public function __construct() {
        parent::__construct();
        $this->cardService = new CardService();
    }
    
    /**
     * 显示首页
     */
    public function index() {
        $stats = $this->cardService->getStats();
        $slides = $this->settingsModel->getSlides();
        $features = $this->settingsModel->getFeatures();
        
        $data = [
            'stats' => $stats,
            'slides' => $slides,
            'features' => $features,
            'csrf_token' => Validator::generateCsrfToken()
        ];
        
        echo $this->renderWithLayout('home', $data);
    }
}
