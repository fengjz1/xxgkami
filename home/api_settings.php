<?php
session_start();
if(!isset($_SESSION['admin_id'])){
    header("Location: ../admin.php");
    exit;
}

require_once '../config.php';
require_once 'classes/BaseController.php';
require_once 'classes/ApiManager.php';
require_once 'includes/AdminLayout.php';
require_once '../utils/TimeHelper.php';

/**
 * API设置控制器
 */
class ApiSettingsController extends BaseController {
    private $apiManager;
    
    public function __construct($conn) {
        parent::__construct($conn);
        $this->apiManager = new ApiManager($conn);
    }
    
    /**
     * 处理API状态更新
     */
    public function handleApiStatusUpdate() {
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_api'])) {
            try {
                $api_enabled = isset($_POST['api_enabled']) ? '1' : '0';
                $stmt = $this->conn->prepare("UPDATE settings SET value = ? WHERE name = 'api_enabled'");
                $stmt->execute([$api_enabled]);
                $this->setSuccess('API设置更新成功');
            } catch(PDOException $e) {
                $this->setError('系统错误，请稍后再试');
            }
        }
    }
    
    /**
     * 处理添加API密钥
     */
    public function handleAddApiKey() {
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_api_key'])) {
            try {
                $key_name = trim($_POST['key_name']);
                $description = trim($_POST['description']);
                $api_key = bin2hex(random_bytes(16)); // 生成32位随机密钥
                
                if(empty($key_name)) {
                    $this->setError('密钥名称不能为空');
                } else {
                    $stmt = $this->conn->prepare("INSERT INTO api_keys (api_key, key_name, description, status, use_count, create_time) VALUES (?, ?, ?, 1, 0, NOW())");
                    $stmt->execute([$api_key, $key_name, $description]);
                    $this->setSuccess('API密钥添加成功');
                }
            } catch(PDOException $e) {
                $this->setError('系统错误，请稍后再试');
            }
        }
    }
    
    /**
     * 处理删除API密钥
     */
    public function handleDeleteApiKey() {
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_api_key'])) {
            try {
                $key_id = (int)$_POST['key_id'];
                $stmt = $this->conn->prepare("DELETE FROM api_keys WHERE id = ?");
                $stmt->execute([$key_id]);
                $this->setSuccess('API密钥删除成功');
            } catch(PDOException $e) {
                $this->setError('系统错误，请稍后再试');
            }
        }
    }
    
    /**
     * 处理切换API密钥状态
     */
    public function handleToggleApiKeyStatus() {
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_status'])) {
            try {
                $key_id = (int)$_POST['key_id'];
                $new_status = (int)$_POST['new_status'];
                $stmt = $this->conn->prepare("UPDATE api_keys SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $key_id]);
                $this->setSuccess('API密钥状态更新成功');
            } catch(PDOException $e) {
                $this->setError('系统错误，请稍后再试');
            }
        }
    }
    
    /**
     * 渲染页面
     */
    public function index() {
        // 处理各种请求
        $this->handleApiStatusUpdate();
        $this->handleAddApiKey();
        $this->handleDeleteApiKey();
        $this->handleToggleApiKeyStatus();
        
        // 获取数据
        $apiStats = $this->apiManager->getApiStats();
        $apiKeys = $this->apiManager->getApiKeys();
        $apiSettings = $this->apiManager->getApiSettings();
        
        // 设置布局
        $this->layout->setTitle('API接口设置')->setCurrentPage('api_settings');
        
        $content = '<div class="api-settings">' . $this->renderMessages();
        
        // API统计信息
        $content .= $this->renderApiStats($apiStats);
        
        // API设置
        $content .= $this->renderApiSettings($apiSettings);
        
        // API密钥管理
        $content .= $this->renderApiKeysManagement($apiKeys);
        
        // API文档
        $content .= $this->renderApiDocumentation($apiKeys);
        
        $content .= '</div>'; // 关闭 api-settings div
        
        echo $this->layout->render($content);
    }
    
    /**
     * 渲染API统计信息
     */
    private function renderApiStats($stats) {
        return '<div class="card">
            <div class="card-header">
                <h3><i class="fas fa-chart-bar"></i> API调用统计</h3>
            </div>
            <div class="card-body">
                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="fas fa-calendar-day fa-2x" style="color: #3498db; margin-bottom: 10px;"></i>
                        <h3>今日调用</h3>
                        <div class="value">' . number_format($stats['today_calls']) . '</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-chart-line fa-2x" style="color: #2ecc71; margin-bottom: 10px;"></i>
                        <h3>总调用次数</h3>
                        <div class="value">' . number_format($stats['total_calls']) . '</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-key fa-2x" style="color: #f39c12; margin-bottom: 10px;"></i>
                        <h3>活跃密钥</h3>
                        <div class="value">' . $stats['active_keys'] . '</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-clock fa-2x" style="color: #e74c3c; margin-bottom: 10px;"></i>
                        <h3>最后调用</h3>
                        <div class="value">' . TimeHelper::format($stats['last_call']) . '</div>
                    </div>
                </div>
            </div>
        </div>';
    }
    
    /**
     * 渲染API设置
     */
    private function renderApiSettings($settings) {
        $apiEnabled = $settings['api_enabled'] ?? '0';
        
        return '<div class="card">
            <div class="card-header">
                <h3><i class="fas fa-cog"></i> API设置</h3>
            </div>
            <div class="card-body">
                <form method="POST" class="api-settings-form">
                    <input type="hidden" name="update_api" value="1">
                    <div class="form-group">
                        <label class="toggle-switch">
                            <span>启用API接口</span>
                            <div class="toggle-label">
                                <input type="checkbox" name="api_enabled" value="1" ' . ($apiEnabled ? 'checked' : '') . '>
                                <span class="toggle-slider"></span>
                            </div>
                        </label>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">保存设置</button>
                    </div>
                </form>
            </div>
        </div>';
    }
    
    /**
     * 渲染API密钥管理
     */
    private function renderApiKeysManagement($apiKeys) {
        $content = '<div class="card">
            <div class="card-header">
                <h3><i class="fas fa-key"></i> API密钥管理</h3>
            </div>
            <div class="card-body">
                <form method="POST" class="form-group" style="margin-bottom: 20px;">
                    <input type="hidden" name="add_api_key" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label>密钥名称：</label>
                            <input type="text" name="key_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>描述：</label>
                            <input type="text" name="description" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-success">添加密钥</button>
                        </div>
                    </div>
                </form>
                
                <div class="table-responsive api-keys-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>密钥名称</th>
                                <th>API密钥</th>
                                <th>状态</th>
                                <th>调用次数</th>
                                <th>最后使用</th>
                                <th>创建时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>';
        
        foreach($apiKeys as $key) {
            $statusClass = $key['status'] ? 'badge-success' : 'badge-danger';
            $statusText = $key['status'] ? '启用' : '禁用';
            $newStatus = $key['status'] ? 0 : 1;
            $statusAction = $key['status'] ? '禁用' : '启用';
            
            $content .= '<tr>
                <td>' . $key['id'] . '</td>
                <td>' . htmlspecialchars($key['key_name']) . '</td>
                <td>
                    <code title="' . htmlspecialchars($key['api_key']) . '">' . 
                    substr($key['api_key'], 0, 16) . '...</code>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard(\'' . $key['api_key'] . '\')">复制</button>
                </td>
                <td><span class="badge ' . $statusClass . '">' . $statusText . '</span></td>
                <td>' . number_format($key['use_count']) . '</td>
                <td>' . TimeHelper::format($key['last_use_time']) . '</td>
                <td>' . TimeHelper::format($key['create_time']) . '</td>
                <td>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="toggle_status" value="1">
                        <input type="hidden" name="key_id" value="' . $key['id'] . '">
                        <input type="hidden" name="new_status" value="' . $newStatus . '">
                        <button type="submit" class="btn btn-sm btn-warning">' . $statusAction . '</button>
                    </form>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="delete_api_key" value="1">
                        <input type="hidden" name="key_id" value="' . $key['id'] . '">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm(\'确定删除这个API密钥吗？\')">删除</button>
                    </form>
                </td>
            </tr>';
        }
        
        $content .= '</tbody>
                    </table>
                </div>
            </div>
        </div>';
        
        return $content;
    }
    
    /**
     * 渲染API文档
     */
    private function renderApiDocumentation($apiKeys) {
        $exampleKey = !empty($apiKeys) ? $apiKeys[0]['api_key'] : 'your-api-key-here';
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['REQUEST_URI'], 2), '/');
        
        return '<div class="card api-doc">
            <div class="card-header">
                <h3><i class="fas fa-book"></i> API文档</h3>
            </div>
            <div class="card-body">
                <div class="api-endpoint">
                    <h2 class="api-title">验证卡密</h2>
                    <p class="api-description">验证卡密是否有效并获取相关信息。</p>
                
                <h5>请求信息</h5>
                <ul>
                    <li><strong>URL:</strong> <code>' . $baseUrl . '/api/verify.php</code></li>
                    <li><strong>方法:</strong> POST</li>
                    <li><strong>Content-Type:</strong> application/x-www-form-urlencoded</li>
                </ul>
                
                <h5>请求参数</h5>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>参数名</th>
                                <th>类型</th>
                                <th>必填</th>
                                <th>说明</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>api_key</td>
                                <td>string</td>
                                <td>是</td>
                                <td>API密钥</td>
                            </tr>
                            <tr>
                                <td>card_key</td>
                                <td>string</td>
                                <td>是</td>
                                <td>要验证的卡密</td>
                            </tr>
                            <tr>
                                <td>device_id</td>
                                <td>string</td>
                                <td>否</td>
                                <td>设备ID（用于防重复使用）</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <h5>响应示例</h5>
                <pre><code>{
    "code": 200,
    "message": "验证成功",
    "data": {
        "card_id": 123,
        "card_key": "ABC123DEF456",
        "card_type": "time",
        "status": "valid",
        "expire_time": "2024-12-31 23:59:59",
        "remaining_count": 0,
        "device_id": "device123"
    }
}</code></pre>
                
                <h5>cURL示例</h5>
                <pre><code>curl -X POST "' . $baseUrl . '/api/verify.php" \\
  -H "Content-Type: application/x-www-form-urlencoded" \\
  -d "api_key=' . $exampleKey . '&card_key=ABC123DEF456&device_id=device123"</code></pre>
                
                </div>
                
                <div class="api-endpoint">
                    <h2 class="api-title">查询卡密</h2>
                    <p class="api-description">查询卡密的详细信息。</p>
                
                <h5>请求信息</h5>
                <ul>
                    <li><strong>URL:</strong> <code>' . $baseUrl . '/api/query.php</code></li>
                    <li><strong>方法:</strong> POST</li>
                    <li><strong>Content-Type:</strong> application/x-www-form-urlencoded</li>
                </ul>
                
                <h5>请求参数</h5>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>参数名</th>
                                <th>类型</th>
                                <th>必填</th>
                                <th>说明</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>api_key</td>
                                <td>string</td>
                                <td>是</td>
                                <td>API密钥</td>
                            </tr>
                            <tr>
                                <td>card_key</td>
                                <td>string</td>
                                <td>是</td>
                                <td>要查询的卡密</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <h5>响应示例</h5>
                <pre><code>{
    "code": 200,
    "message": "查询成功",
    "data": {
        "card_id": 123,
        "card_key": "ABC123DEF456",
        "card_type": "time",
        "status": "used",
        "expire_time": "2024-12-31 23:59:59",
        "remaining_count": 0,
        "use_time": "2024-01-15 10:30:00",
        "device_id": "device123"
    }
}</code></pre>
                
                <h5>cURL示例</h5>
                <pre><code>curl -X POST "' . $baseUrl . '/api/query.php" \\
  -H "Content-Type: application/x-www-form-urlencoded" \\
  -d "api_key=' . $exampleKey . '&card_key=ABC123DEF456"</code></pre>
                </div>
                
                <div class="api-common-section">
                    <h2 class="api-title">错误代码</h2>
                    <p class="api-description">所有API接口可能返回的错误代码及其说明。</p>
                    
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>代码</th>
                                    <th>说明</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>200</td>
                                    <td>成功</td>
                                </tr>
                                <tr>
                                    <td>400</td>
                                    <td>参数错误</td>
                                </tr>
                                <tr>
                                    <td>401</td>
                                    <td>API密钥无效</td>
                                </tr>
                                <tr>
                                    <td>403</td>
                                    <td>API未启用</td>
                                </tr>
                                <tr>
                                    <td>404</td>
                                    <td>卡密不存在</td>
                                </tr>
                                <tr>
                                    <td>410</td>
                                    <td>卡密已过期</td>
                                </tr>
                                <tr>
                                    <td>500</td>
                                    <td>服务器错误</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>';
    }
}

// 处理请求
try {
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $controller = new ApiSettingsController($conn);
    $controller->index();
    
} catch(PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}
?>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('API密钥已复制到剪贴板');
    }, function(err) {
        console.error('复制失败: ', err);
    });
}
</script>