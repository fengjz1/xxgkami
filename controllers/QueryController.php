<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../services/CardService.php';
require_once __DIR__ . '/../utils/Validator.php';

/**
 * 卡密查询控制器
 */
class QueryController extends BaseController {
    private $cardService;
    
    public function __construct() {
        parent::__construct();
        $this->cardService = new CardService();
    }
    
    /**
     * 显示查询页面
     */
    public function index() {
        $slides = $this->settingsModel->getSlides();
        $features = $this->settingsModel->getFeatures();
        
        $data = [
            'slides' => $slides,
            'features' => $features,
            'csrf_token' => Validator::generateCsrfToken()
        ];
        
        echo $this->renderWithLayout('query', $data);
    }
    
    /**
     * 处理卡密查询
     */
    public function query() {
        if (!$this->isPost()) {
            Response::redirect('query.php');
        }
        
        // 验证CSRF令牌
        $csrfToken = $this->getPostData('csrf_token');
        if (!Validator::validateCsrfToken($csrfToken)) {
            $this->showError('安全验证失败，请重新提交');
            return;
        }
        
        $cardKey = $this->getPostData('card_key');
        
        // 验证输入
        $cardValidation = Validator::validateCardKey($cardKey);
        if (!$cardValidation['valid']) {
            $this->showError($cardValidation['message']);
            return;
        }
        
        // 清理输入
        $cardKey = Validator::sanitize($cardKey);
        
        // 查询卡密
        $result = $this->cardService->queryCard($cardKey);
        
        if ($result['code'] !== 0) {
            $this->showError($result['message']);
            return;
        }
        
        // 显示查询结果
        $this->showSuccess($result['message'], $result['data']);
    }
    
    /**
     * 显示错误信息
     */
    private function showError($message) {
        $slides = $this->settingsModel->getSlides();
        $features = $this->settingsModel->getFeatures();
        
        $data = [
            'slides' => $slides,
            'features' => $features,
            'csrf_token' => Validator::generateCsrfToken(),
            'error' => $message
        ];
        
        echo $this->renderWithLayout('query', $data);
    }
    
    /**
     * 显示成功信息
     */
    private function showSuccess($message, $cardData) {
        $slides = $this->settingsModel->getSlides();
        $features = $this->settingsModel->getFeatures();
        
        $data = [
            'slides' => $slides,
            'features' => $features,
            'csrf_token' => Validator::generateCsrfToken(),
            'success' => $message,
            'card_data' => $cardData
        ];
        
        echo $this->renderWithLayout('query', $data);
    }
}
