<?php
session_start();
if(!isset($_SESSION['admin_id'])){
    header("Location: ../admin.php");
    exit;
}

require_once '../config.php';
require_once 'classes/CardManager.php';
require_once 'classes/ViewRenderer.php';

// 初始化变量
$cards = [];
$per_page_options = [20, 50, 100, 200];
$error = null;
$success = null;
$stats = ['total' => 0, 'used' => 0, 'unused' => 0, 'usage_rate' => 0];
$pagination = ['total_pages' => 0, 'current_page' => 1, 'limit' => 20];

try {
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $cardManager = new CardManager($conn);
    
    // 获取统计数据
    $stats = $cardManager->getStatistics();

    // 处理POST请求
    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        // 生成卡密
        if(isset($_POST['generate_card']) && isset($_POST['action']) && $_POST['action'] == 'add') {
            $count = $cardManager->generateCards($_POST);
            $success = "成功生成 {$count} 个卡密";
            $stats = $cardManager->getStatistics(); // 更新统计数据
        }
        
        // 批量删除卡密
        if(isset($_POST['delete_cards']) && isset($_POST['card_ids'])) {
            $ids = array_map('intval', $_POST['card_ids']);
            $deleted = $cardManager->deleteCards($ids);
            if($deleted > 0) {
                $success = "成功删除 {$deleted} 个卡密";
            } else {
                $error = "没有卡密被删除，可能卡密不存在或已被使用";
            }
        }
        
        // 修改卡密有效期
        if(isset($_POST['edit_expire_time'])) {
            $card_id = intval($_POST['card_id']);
            $expire_time = $_POST['expire_time'];
            $cardManager->updateExpireTime($card_id, $expire_time);
            $success = "卡密有效期修改成功";
        }
        
        // 修改次数卡密剩余次数
        if(isset($_POST['edit_count'])) {
            $card_id = intval($_POST['card_id']);
            $remaining_count = intval($_POST['remaining_count']);
            $cardManager->updateRemainingCount($card_id, $remaining_count);
            $success = "卡密剩余次数修改成功";
        }
    }

    // 处理GET请求
    if(isset($_GET['delete'])) {
        $deleted = $cardManager->deleteCard($_GET['delete']);
        if($deleted > 0) {
            $success = "删除成功";
        } else {
            $error = "删除失败，卡密不存在或已被使用";
        }
    }

    // 获取卡密列表
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    if (!in_array($limit, $per_page_options)) {
        $limit = 20;
    }

    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    
    $result = $cardManager->getCards($limit, $page);
    $cards = $result['cards'];
    $pagination = [
        'total_pages' => $result['total_pages'],
        'current_page' => $result['current_page'],
        'limit' => $result['limit']
    ];
    
} catch(PDOException $e) {
    die("捕获到数据库错误，请检查日志或联系管理员。详细信息： " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>卡密管理 - 卡密验证系统</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="../assets/js/xlsx.full.min.js"></script>
    <script src="../assets/js/admin.js"></script>
</head>
<body>
    <div class="admin-wrapper">
        <div class="sidebar">
            <div class="logo">
                <h2>管理系统</h2>
            </div>
            <ul class="menu">
                <li class="active"><a href="index.php"><i class="fas fa-key"></i>卡密管理</a></li>
                <li><a href="stats.php"><i class="fas fa-chart-line"></i>数据统计</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i>系统设置</a></li>
                <li><a href="api_settings.php"><i class="fas fa-code"></i>API接口</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i>退出登录</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h2><i class="fas fa-key"></i> 卡密管理</h2>
                <div class="user-info">
                    <img src="../assets/images/avatar.svg" alt="avatar" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiMzNDk4ZGIiLz4KPGNpcmNsZSBjeD0iMjAiIGN5PSIxNiIgcj0iNiIgZmlsbD0id2hpdGUiLz4KPHBhdGggZD0iTTggMzJjMC02LjYyNyA1LjM3My0xMiAxMi0xMnMxMiA1LjM3MyAxMiAxMiIgZmlsbD0id2hpdGUiLz4KPC9zdmc+';">
                    <span>欢迎，<?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                </div>
            </div>
            
            <?php 
            if(isset($success)) echo "<div class='alert alert-success'>$success</div>";
            if(isset($error)) echo "<div class='alert alert-error'>$error</div>";
            ?>
            
            <?php echo ViewRenderer::renderStatsCards($stats); ?>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-plus-circle"></i> 生成卡密</h3>
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
                    <h3>卡密列表</h3>
                    <div class="export-controls">
                        <input type="text" id="exportFileName" placeholder="文件名称" value="卡密列表">
                        <button type="button" class="btn btn-primary" onclick="exportSelected()">
                            <i class="fas fa-file-excel"></i> 导出Excel
                        </button>
                        <button type="button" class="btn btn-danger" onclick="deleteSelected()">
                            <i class="fas fa-trash"></i> 批量删除
                        </button>
                        <label class="select-all-container">
                            <input type="checkbox" id="selectAll" onclick="toggleSelectAll()">
                            <span>全选</span>
                        </label>
                    </div>
                </div>
                <div class="table-responsive card-list-table">
                    <table>
                        <thead>
                            <tr>
                                <th width="20">
                                    <input type="checkbox" id="selectAll" onclick="toggleSelectAll()">
                                </th>
                                <th>ID</th>
                                <th>卡密</th>
                                <th>状态</th>
                                <th>类型</th>
                                <th>有效期/剩余次数</th>
                                <th>使用时间</th>
                                <th>到期时间</th>
                                <th>创建时间</th>
                                <th>设备ID</th>
                                <th>允许重复验证</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($cards as $card): ?>
                                <?php echo ViewRenderer::renderCardRow($card); ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php echo ViewRenderer::renderPagination($pagination, $per_page_options); ?>
            </div>
        </div>
    </div>

    <footer class="footer-copyright">
        <div class="container">
            &copy; <?php echo date('Y'); ?> 小小怪卡密系统 - All Rights Reserved
        </div>
    </footer>

    <!-- 添加模态框 -->
    <div id="editExpireTimeModal" class="modal">
        <div class="modal-content">
            <h3>修改到期时间</h3>
            <form id="editExpireTimeForm">
                <input type="hidden" name="card_id" id="editCardId">
                <div class="form-group">
                    <label>到期时间：</label>
                    <input type="datetime-local" name="expire_time" id="editExpireTime" required>
                </div>
                <div class="button-group">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 修改次数模态框 -->
    <div id="editCountModal" class="modal">
        <div class="modal-content">
            <h3>修改剩余次数</h3>
            <form id="editCountForm">
                <input type="hidden" name="card_id" id="editCountCardId">
                <div class="form-group">
                    <label>剩余次数：</label>
                    <input type="number" name="remaining_count" id="editRemainingCount" min="0" required>
                </div>
                <div class="button-group">
                    <button type="button" class="btn btn-secondary" onclick="closeCountModal()">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html> 