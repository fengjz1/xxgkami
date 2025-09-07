<?php
session_start();
if(!isset($_SESSION['admin_id'])){
    header("Location: ../admin.php");
    exit;
}

require_once '../config.php';
require_once 'classes/BaseController.php';
require_once 'classes/SettingsManager.php';
require_once 'includes/AdminLayout.php';

/**
 * 系统设置控制器
 */
class SettingsController extends BaseController {
    private $settingsManager;
    
    public function __construct($conn) {
        parent::__construct($conn);
        $this->settingsManager = new SettingsManager($conn);
    }
    
    /**
     * 处理密码修改
     */
    public function handlePasswordChange() {
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])){
            try {
                $old_password = $_POST['old_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                // 验证旧密码
                $stmt = $this->conn->prepare("SELECT password FROM admins WHERE id = ?");
                $stmt->execute([$_SESSION['admin_id']]);
                $admin = $stmt->fetch();
                
                if(!password_verify($old_password, $admin['password'])){
                    $this->setError('旧密码错误');
                } elseif($new_password !== $confirm_password) {
                    $this->setError('两次输入的新密码不一致');
                } elseif(strlen($new_password) < 6) {
                    $this->setError('密码长度不能小于6位');
                } else {
                    // 更新密码
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $this->conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
                    $stmt->execute([$new_hash, $_SESSION['admin_id']]);
                    $this->setSuccess('密码修改成功');
                }
            } catch(PDOException $e) {
                $this->setError('系统错误，请稍后再试');
            }
        }
    }
    
    /**
     * 处理基础设置修改
     */
    public function handleBasicSettings() {
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_title'])){
            try {
                $settings = [
                    'site_title' => trim($_POST['site_title']),
                    'site_subtitle' => trim($_POST['site_subtitle']),
                    'copyright_text' => trim($_POST['copyright_text'])
                ];
                
                foreach($settings as $name => $value) {
                    $stmt = $this->conn->prepare("UPDATE settings SET value = ? WHERE name = ?");
                    $stmt->execute([$value, $name]);
                }
                
                $this->setSuccess('网站设置修改成功');
            } catch(PDOException $e) {
                $this->setError('系统错误，请稍后再试');
            }
        }
    }
    
    /**
     * 处理联系方式修改
     */
    public function handleContactSettings() {
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_contact'])){
            try {
                $settings = [
                    'contact_qq_group' => trim($_POST['contact_qq_group']),
                    'contact_wechat_qr' => trim($_POST['contact_wechat_qr']),
                    'contact_email' => trim($_POST['contact_email'])
                ];
                
                foreach($settings as $name => $value) {
                    $stmt = $this->conn->prepare("UPDATE settings SET value = ? WHERE name = ?");
                    $stmt->execute([$value, $name]);
                }
                
                $this->setSuccess('联系方式设置修改成功');
            } catch(PDOException $e) {
                $this->setError('系统错误，请稍后再试');
            }
        }
    }
    
    /**
     * 处理轮播图上传
     */
    public function handleSlideUpload() {
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_slide'])) {
            try {
                $title = trim($_POST['slide_title']);
                $description = trim($_POST['slide_description']);
                $image_url = trim($_POST['image_url']);
                $sort_order = (int)$_POST['sort_order'];
                
                // 如果有文件上传
                if(isset($_FILES['slide_image']) && $_FILES['slide_image']['error'] == 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                    $filename = $_FILES['slide_image']['name'];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    
                    if(in_array($ext, $allowed)) {
                        $upload_dir = '../assets/images/slides/';
                        if(!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        $new_filename = uniqid() . '.' . $ext;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if(move_uploaded_file($_FILES['slide_image']['tmp_name'], $upload_path)) {
                            $image_url = 'assets/images/slides/' . $new_filename;
                        }
                    }
                }
                
                if(empty($title) || (empty($image_url) && !isset($_FILES['slide_image']))) {
                    $this->setError('标题和图片不能为空');
                } else {
                    $stmt = $this->conn->prepare("INSERT INTO slides (title, description, image_url, sort_order) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$title, $description, $image_url, $sort_order]);
                    $this->setSuccess('轮播图添加成功');
                }
            } catch(PDOException $e) {
                $this->setError('系统错误，请稍后再试');
            }
        }
    }
    
    /**
     * 处理系统特点
     */
    public function handleFeatureSettings() {
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_feature'])) {
            try {
                $feature_id = isset($_POST['feature_id']) ? (int)$_POST['feature_id'] : 0;
                $icon = trim($_POST['icon']);
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $sort_order = (int)$_POST['sort_order'];
                
                if(empty($title) || empty($description)) {
                    $this->setError('标题和描述不能为空');
                } else {
                    if($feature_id > 0) {
                        // 更新现有特点
                        $stmt = $this->conn->prepare("UPDATE features SET icon = ?, title = ?, description = ?, sort_order = ? WHERE id = ?");
                        $stmt->execute([$icon, $title, $description, $sort_order, $feature_id]);
                        $this->setSuccess('系统特点更新成功');
                    } else {
                        // 添加新特点
                        $stmt = $this->conn->prepare("INSERT INTO features (icon, title, description, sort_order) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$icon, $title, $description, $sort_order]);
                        $this->setSuccess('系统特点添加成功');
                    }
                }
            } catch(PDOException $e) {
                $this->setError('系统错误，请稍后再试');
            }
        }
    }
    
    /**
     * 处理删除操作
     */
    public function handleDeletions() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                // 删除轮播图
                if(isset($_POST['delete_slide'])) {
                    $slide_id = (int)$_POST['slide_id'];
                    $stmt = $this->conn->prepare("DELETE FROM slides WHERE id = ?");
                    $stmt->execute([$slide_id]);
                    $this->setSuccess('轮播图删除成功');
                }
                
                // 删除系统特点
                if(isset($_POST['delete_feature'])) {
                    $feature_id = (int)$_POST['feature_id'];
                    $stmt = $this->conn->prepare("DELETE FROM features WHERE id = ?");
                    $stmt->execute([$feature_id]);
                    $this->setSuccess('系统特点删除成功');
                }
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
        $this->handlePasswordChange();
        $this->handleBasicSettings();
        $this->handleContactSettings();
        $this->handleSlideUpload();
        $this->handleFeatureSettings();
        $this->handleDeletions();
        
        // 获取数据
        $settings = $this->settingsManager->getAllSettings();
        $slides = $this->settingsManager->getSlides();
        $features = $this->settingsManager->getFeatures();
        $systemInfo = $this->settingsManager->getSystemInfo();
        
        // 设置布局
        $this->layout->setTitle('系统设置')->setCurrentPage('settings');
        
        $content = $this->renderMessages();
        
        // 密码修改表单
        $content .= $this->renderPasswordForm();
        
        // 基础设置表单
        $content .= $this->renderBasicSettingsForm($settings);
        
        // 联系方式设置表单
        $content .= $this->renderContactSettingsForm($settings);
        
        // 轮播图管理
        $content .= $this->renderSlidesManagement($slides);
        
        // 系统特点管理
        $content .= $this->renderFeaturesManagement($features);
        
        // 系统信息
        $content .= $this->renderSystemInfo($systemInfo);
        
        echo $this->layout->render($content);
    }
    
    /**
     * 渲染密码修改表单
     */
    private function renderPasswordForm() {
        return '<div class="card">
            <div class="card-header">
                <h3><i class="fas fa-key"></i> 修改密码</h3>
            </div>
            <div class="card-body">
                <form method="POST" class="form-group">
                    <input type="hidden" name="change_password" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label>旧密码：</label>
                            <input type="password" name="old_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>新密码：</label>
                            <input type="password" name="new_password" class="form-control" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label>确认新密码：</label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">修改密码</button>
                </form>
            </div>
        </div>';
    }
    
    /**
     * 渲染基础设置表单
     */
    private function renderBasicSettingsForm($settings) {
        return '<div class="card">
            <div class="card-header">
                <h3><i class="fas fa-cog"></i> 基础设置</h3>
            </div>
            <div class="card-body">
                <form method="POST" class="form-group">
                    <input type="hidden" name="change_title" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label>网站标题：</label>
                            <input type="text" name="site_title" class="form-control" value="' . htmlspecialchars($settings['site_title'] ?? '卡密验证系统') . '" required>
                        </div>
                        <div class="form-group">
                            <label>网站副标题：</label>
                            <input type="text" name="site_subtitle" class="form-control" value="' . htmlspecialchars($settings['site_subtitle'] ?? '专业的卡密验证解决方案') . '">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>版权信息：</label>
                        <input type="text" name="copyright_text" class="form-control" value="' . htmlspecialchars($settings['copyright_text'] ?? '小小怪卡密系统 - All Rights Reserved') . '">
                    </div>
                    <button type="submit" class="btn btn-primary">保存设置</button>
                </form>
            </div>
        </div>';
    }
    
    /**
     * 渲染联系方式设置表单
     */
    private function renderContactSettingsForm($settings) {
        return '<div class="card">
            <div class="card-header">
                <h3><i class="fas fa-phone"></i> 联系方式设置</h3>
            </div>
            <div class="card-body">
                <form method="POST" class="form-group">
                    <input type="hidden" name="change_contact" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label>QQ群号：</label>
                            <input type="text" name="contact_qq_group" class="form-control" value="' . htmlspecialchars($settings['contact_qq_group'] ?? '123456789') . '">
                        </div>
                        <div class="form-group">
                            <label>微信二维码：</label>
                            <input type="text" name="contact_wechat_qr" class="form-control" value="' . htmlspecialchars($settings['contact_wechat_qr'] ?? 'assets/images/wechat-qr.jpg') . '">
                        </div>
                        <div class="form-group">
                            <label>联系邮箱：</label>
                            <input type="email" name="contact_email" class="form-control" value="' . htmlspecialchars($settings['contact_email'] ?? 'support@example.com') . '">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">保存设置</button>
                </form>
            </div>
        </div>';
    }
    
    /**
     * 渲染轮播图管理
     */
    private function renderSlidesManagement($slides) {
        $content = '<div class="card">
            <div class="card-header">
                <h3><i class="fas fa-images"></i> 轮播图管理</h3>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" class="form-group">
                    <input type="hidden" name="upload_slide" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label>标题：</label>
                            <input type="text" name="slide_title" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>描述：</label>
                            <input type="text" name="slide_description" class="form-control">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>图片文件：</label>
                            <input type="file" name="slide_image" class="form-control" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label>或图片URL：</label>
                            <input type="url" name="image_url" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>排序：</label>
                            <input type="number" name="sort_order" class="form-control" value="0">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">添加轮播图</button>
                </form>
                
                <div class="table-responsive slides-table" style="margin-top: 20px;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>标题</th>
                                <th>描述</th>
                                <th>图片</th>
                                <th>排序</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>';
        
        foreach($slides as $slide) {
            $content .= '<tr>
                <td>' . $slide['id'] . '</td>
                <td>' . htmlspecialchars($slide['title']) . '</td>
                <td>' . htmlspecialchars($slide['description']) . '</td>
                <td><img src="../' . htmlspecialchars($slide['image_url']) . '" style="width: 50px; height: 30px; object-fit: cover;"></td>
                <td>' . $slide['sort_order'] . '</td>
                <td>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="delete_slide" value="1">
                        <input type="hidden" name="slide_id" value="' . $slide['id'] . '">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'确定删除这个轮播图吗？\')">删除</button>
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
     * 渲染系统特点管理
     */
    private function renderFeaturesManagement($features) {
        $content = '<div class="card">
            <div class="card-header">
                <h3><i class="fas fa-star"></i> 系统特点管理</h3>
            </div>
            <div class="card-body">
                <form method="POST" class="form-group">
                    <input type="hidden" name="save_feature" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label>图标：</label>
                            <input type="text" name="icon" class="form-control" placeholder="fas fa-check" required>
                        </div>
                        <div class="form-group">
                            <label>标题：</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>排序：</label>
                            <input type="number" name="sort_order" class="form-control" value="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>描述：</label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">添加特点</button>
                </form>
                
                <div class="table-responsive features-table" style="margin-top: 20px;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>图标</th>
                                <th>标题</th>
                                <th>描述</th>
                                <th>排序</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>';
        
        foreach($features as $feature) {
            $content .= '<tr>
                <td>' . $feature['id'] . '</td>
                <td><i class="' . htmlspecialchars($feature['icon']) . '"></i></td>
                <td>' . htmlspecialchars($feature['title']) . '</td>
                <td>' . htmlspecialchars($feature['description']) . '</td>
                <td>' . $feature['sort_order'] . '</td>
                <td>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="delete_feature" value="1">
                        <input type="hidden" name="feature_id" value="' . $feature['id'] . '">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'确定删除这个特点吗？\')">删除</button>
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
     * 渲染系统信息
     */
    private function renderSystemInfo($systemInfo) {
        return '<div class="card">
            <div class="card-header">
                <h3><i class="fas fa-info-circle"></i> 系统信息</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>PHP版本：</strong> ' . $systemInfo['php_version'] . '</p>
                        <p><strong>MySQL版本：</strong> ' . $systemInfo['mysql_version'] . '</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>安装时间：</strong> ' . $systemInfo['install_time'] . '</p>
                        <p><strong>服务器时间：</strong> ' . date('Y-m-d H:i:s') . '</p>
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
    
    $controller = new SettingsController($conn);
    $controller->index();
    
} catch(PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}
?>