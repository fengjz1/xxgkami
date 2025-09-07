<?php
session_start();
if(!isset($_SESSION['admin_id'])){
    header("Location: ../admin.php");
    exit;
}

require_once '../config.php';
require_once 'classes/CardManager.php';

// 初始化变量
$stats = ['total' => 0, 'used' => 0, 'unused' => 0, 'usage_rate' => 0];

try {
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $cardManager = new CardManager($conn);
    $stats = $cardManager->getStatistics();
    
} catch(PDOException $e) {
    die("捕获到数据库错误，请检查日志或联系管理员。详细信息： " . $e->getMessage());
}
?>

<?php
require_once 'includes/AdminLayout.php';

$layout = new AdminLayout('管理后台', 'index');
echo $layout->renderHeader();
echo '<div class="admin-wrapper">';
echo $layout->renderSidebar();
echo $layout->renderMainContentStart();
echo $layout->renderBreadcrumbs();
echo $layout->renderPageHeader();
?>
        <div class="container">

            <!-- 快速操作卡片 -->
            <div class="quick-actions">
                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div class="action-content">
                        <h3>生成卡密</h3>
                        <p>快速生成新的卡密</p>
                        <a href="generate_cards.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> 立即生成
                        </a>
                    </div>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="action-content">
                        <h3>卡密列表</h3>
                        <p>查看和管理所有卡密</p>
                        <a href="cards.php" class="btn btn-primary">
                            <i class="fas fa-list"></i> 查看列表
                        </a>
                    </div>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="action-content">
                        <h3>数据统计</h3>
                        <p>查看详细的使用统计</p>
                        <a href="stats.php" class="btn btn-primary">
                            <i class="fas fa-chart-bar"></i> 查看统计
                        </a>
                    </div>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="action-content">
                        <h3>系统设置</h3>
                        <p>配置系统参数和轮播图</p>
                        <a href="settings.php" class="btn btn-primary">
                            <i class="fas fa-cog"></i> 系统设置
                        </a>
                    </div>
                </div>
            </div>

            <!-- 统计概览 -->
            <div class="stats-overview">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-key"></i>
                    </div>
                    <div class="stat-content">
                        <h3>总卡密数</h3>
                        <div class="value"><?php echo $stats['total']; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3>已使用</h3>
                        <div class="value"><?php echo $stats['used']; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3>未使用</h3>
                        <div class="value"><?php echo $stats['unused']; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-content">
                        <h3>使用率</h3>
                        <div class="value"><?php echo $stats['usage_rate']; ?>%</div>
                    </div>
                </div>
            </div>

            <!-- 最近活动 -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> 最近活动</h3>
                </div>
                <div class="card-body">
                    <div class="activity-list">
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-plus text-success"></i>
                            </div>
                            <div class="activity-content">
                                <h4>系统启动</h4>
                                <p>管理后台已成功启动</p>
                                <span class="activity-time">刚刚</span>
                            </div>
                        </div>
                        
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-user text-primary"></i>
                            </div>
                            <div class="activity-content">
                                <h4>用户登录</h4>
                                <p>管理员已登录系统</p>
                                <span class="activity-time">刚刚</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .action-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 20px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .action-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }
    
    .action-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
    }
    
    .action-content h3 {
        margin: 0 0 8px 0;
        color: #2c3e50;
        font-size: 18px;
    }
    
    .action-content p {
        margin: 0 0 15px 0;
        color: #7f8c8d;
        font-size: 14px;
    }
    
    .stats-overview {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 20px;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
    }
    
    .stat-content h3 {
        margin: 0 0 5px 0;
        color: #7f8c8d;
        font-size: 14px;
        font-weight: 500;
    }
    
    .stat-content .value {
        font-size: 28px;
        font-weight: 700;
        color: #2c3e50;
    }
    
    .activity-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .activity-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }
    
    .activity-content h4 {
        margin: 0 0 5px 0;
        color: #2c3e50;
        font-size: 16px;
    }
    
    .activity-content p {
        margin: 0;
        color: #7f8c8d;
        font-size: 14px;
    }
    
    .activity-time {
        color: #95a5a6;
        font-size: 12px;
        margin-left: auto;
    }
    
    .text-success { color: #27ae60; }
    .text-primary { color: #3498db; }
    
    @media (max-width: 768px) {
        .quick-actions {
            grid-template-columns: 1fr;
        }
        
        .action-card {
            flex-direction: column;
            text-align: center;
        }
        
        .stats-overview {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    </style>
<?php 
echo $layout->renderMainContentEnd();
echo '</div>';
echo $layout->renderFooter(); 
echo $layout->renderScripts(); 
?>