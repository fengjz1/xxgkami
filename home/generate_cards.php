<?php
session_start();
if(!isset($_SESSION['admin_id'])){
    header("Location: ../admin.php");
    exit;
}

require_once '../config.php';
require_once 'classes/CardManager.php';

// 初始化变量
$error = null;
$success = null;

try {
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $cardManager = new CardManager($conn);

    // 处理POST请求
    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        // 生成卡密
        if(isset($_POST['generate_card']) && isset($_POST['action']) && $_POST['action'] == 'add') {
            $count = $cardManager->generateCards($_POST);
            $success = "成功生成 {$count} 个卡密";
        }
    }
    
} catch(PDOException $e) {
    die("捕获到数据库错误，请检查日志或联系管理员。详细信息： " . $e->getMessage());
}
?>

<?php
require_once 'includes/AdminLayout.php';

$layout = new AdminLayout('生成卡密', 'generate_cards');
echo $layout->renderHeader();
echo '<div class="admin-wrapper">';
echo $layout->renderSidebar();
echo $layout->renderMainContentStart();
echo $layout->renderBreadcrumbs();
echo $layout->renderPageHeader();
?>
        <div class="container">

            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-key"></i> 生成新卡密</h3>
                </div>
                <form method="POST" class="form-group" style="padding: 20px;">
                    <input type="hidden" name="action" value="add">
                    <div class="generate-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-list-ol"></i> 生成数量：</label>
                                <input type="number" name="count" min="1" max="100" value="1" class="form-control">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-credit-card"></i> 卡密类型：</label>
                                <select name="card_type" id="card_type" class="form-control">
                                    <option value="time">时间卡密</option>
                                    <option value="count">次数卡密</option>
                                </select>
                            </div>
                            <div class="form-group time-duration">
                                <label><i class="fas fa-clock"></i> 使用时长：</label>
                                <select name="duration" class="form-control">
                                    <option value="0">永久</option>
                                    <option value="1">1天</option>
                                    <option value="7">7天</option>
                                    <option value="30">30天</option>
                                    <option value="90">90天</option>
                                    <option value="180">180天</option>
                                    <option value="365">365天</option>
                                    <option value="custom">自定义</option>
                                </select>
                            </div>
                            <div class="form-group custom-duration" style="display: none;">
                                <label><i class="fas fa-edit"></i> 自定义天数：</label>
                                <input type="number" name="custom_duration" min="1" class="form-control">
                            </div>
                            <div class="form-group count-value" style="display: none;">
                                <label><i class="fas fa-sort-numeric-up"></i> 使用次数：</label>
                                <select name="count_value" class="form-control">
                                    <option value="1">1次</option>
                                    <option value="5">5次</option>
                                    <option value="10">10次</option>
                                    <option value="20">20次</option>
                                    <option value="50">50次</option>
                                    <option value="100">100次</option>
                                    <option value="custom">自定义</option>
                                </select>
                            </div>
                            <div class="form-group custom-count" style="display: none;">
                                <label><i class="fas fa-edit"></i> 自定义次数：</label>
                                <input type="number" name="custom_count" min="1" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="allow_reverify" class="checkbox-label">
                                    <input type="checkbox" id="allow_reverify" name="allow_reverify" value="1" checked>
                                    允许同一设备重复验证
                                </label>
                            </div>
                        </div>
                        <button type="submit" name="generate_card" class="btn btn-primary">
                            <i class="fas fa-plus"></i> 生成卡密
                        </button>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-info-circle"></i> 使用说明</h3>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <h4><i class="fas fa-clock"></i> 时间卡密</h4>
                            <p>设置固定的使用时长，从首次验证开始计算有效期。适合按时间收费的服务。</p>
                        </div>
                        <div class="info-item">
                            <h4><i class="fas fa-sort-numeric-up"></i> 次数卡密</h4>
                            <p>设置使用次数限制，每次验证消耗一次。适合按次数收费的服务。</p>
                        </div>
                        <div class="info-item">
                            <h4><i class="fas fa-mobile-alt"></i> 设备绑定</h4>
                            <p>卡密可以绑定到特定设备，防止重复使用。可选择是否允许同一设备重复验证。</p>
                        </div>
                        <div class="info-item">
                            <h4><i class="fas fa-shield-alt"></i> 安全特性</h4>
                            <p>采用加密存储，支持多种加密方式，确保卡密安全性。</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // 卡密类型切换
    document.getElementById('card_type').addEventListener('change', function() {
        const cardType = this.value;
        const timeDuration = document.querySelector('.time-duration');
        const customDuration = document.querySelector('.custom-duration');
        const countValue = document.querySelector('.count-value');
        const customCount = document.querySelector('.custom-count');
        
        if (cardType === 'time') {
            timeDuration.style.display = 'block';
            customDuration.style.display = 'none';
            countValue.style.display = 'none';
            customCount.style.display = 'none';
        } else if (cardType === 'count') {
            timeDuration.style.display = 'none';
            customDuration.style.display = 'none';
            countValue.style.display = 'block';
            customCount.style.display = 'none';
        }
    });

    // 自定义时长切换
    document.querySelector('select[name="duration"]').addEventListener('change', function() {
        const customDuration = document.querySelector('.custom-duration');
        if (this.value === 'custom') {
            customDuration.style.display = 'block';
        } else {
            customDuration.style.display = 'none';
        }
    });

    // 自定义次数切换
    document.querySelector('select[name="count_value"]').addEventListener('change', function() {
        const customCount = document.querySelector('.custom-count');
        if (this.value === 'custom') {
            customCount.style.display = 'block';
        } else {
            customCount.style.display = 'none';
        }
    });
    </script>
<?php 
echo $layout->renderMainContentEnd();
echo '</div>';
echo $layout->renderFooter(); 
echo $layout->renderScripts(); 
?>
