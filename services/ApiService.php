<?php
require_once __DIR__ . '/../models/ApiModel.php';
require_once __DIR__ . '/ApiCardService.php';
require_once __DIR__ . '/../utils/Response.php';

/**
 * API服务
 */
class ApiService {
    private $apiModel;
    private $cardService;
    
    public function __construct() {
        $this->apiModel = new ApiModel();
        $this->cardService = new ApiCardService();
    }
    
    /**
     * 处理API请求
     */
    public function handleRequest() {
        // 设置CORS头
        Response::setCorsHeaders();
        Response::handleOptions();
        
        // 检查API是否启用
        if (!$this->apiModel->isApiEnabled()) {
            Response::error('API接口未启用', 2, 403);
        }
        
        // 获取API密钥
        $apiKey = $this->getApiKey();
        if (!$apiKey) {
            Response::error('缺少API密钥', 4, 401);
        }
        
        // 验证API密钥
        if (!$this->apiModel->validateApiKey($apiKey)) {
            Response::error('API密钥无效或已禁用', 4, 401);
        }
        
        // 记录API调用
        $this->apiModel->recordApiCall($apiKey);
        
        // 根据请求路径处理不同的API
        $requestUri = $_SERVER['REQUEST_URI'];
        
        if (strpos($requestUri, '/api/verify.php') !== false) {
            $this->handleVerifyApi();
        } elseif (strpos($requestUri, '/api/query.php') !== false) {
            $this->handleQueryApi();
        } else {
            Response::error('API接口不存在', 404, 404);
        }
    }
    
    /**
     * 处理验证API
     */
    private function handleVerifyApi() {
        $cardKey = $this->getPostData('card_key');
        $deviceId = $this->getPostData('device_id');
        
        if (empty($cardKey)) {
            Response::error('请提供卡密', 1, 400);
        }
        
        // 如果设备ID为空，则不绑定设备
        if (empty($deviceId)) {
            $deviceId = null;
        }
        
        $this->cardService->verifyCard($cardKey, $deviceId);
    }
    
    /**
     * 处理查询API
     */
    private function handleQueryApi() {
        $cardKey = $this->getPostData('card_key');
        
        if (empty($cardKey)) {
            Response::error('请提供卡密', 1, 400);
        }
        
        $this->cardService->queryCard($cardKey);
    }
    
    /**
     * 获取API密钥
     */
    private function getApiKey() {
        $headers = getallheaders();
        
        // 优先从Header获取API密钥
        if (isset($headers['X-API-KEY'])) {
            return $headers['X-API-KEY'];
        }
        
        // 如果是GET请求且Header中没有API密钥，则从URL参数获取
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['api_key'])) {
            return $_GET['api_key'];
        }
        
        // 从POST数据获取
        return $this->getPostData('api_key');
    }
    
    /**
     * 获取POST数据
     */
    private function getPostData($key) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 处理JSON请求
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input && isset($input[$key])) {
                return $input[$key];
            }
            
            // 处理表单数据
            if (isset($_POST[$key])) {
                return $_POST[$key];
            }
        }
        
        // 处理GET请求
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET[$key])) {
            return $_GET[$key];
        }
        
        return null;
    }
}
