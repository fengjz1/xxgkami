<?php
session_start();
if(!isset($_SESSION['admin_id'])){
    header("Location: ../admin.php");
    exit;
}

require_once '../config.php';
require_once 'includes/AdminLayout.php';
require_once 'classes/BaseController.php';
require_once 'classes/StatisticsManager.php';
require_once 'classes/UIComponents.php';
require_once '../utils/TimeHelper.php';

class StatsController extends BaseController {
    private $statsManager;
    
    public function __construct($connection) {
        parent::__construct($connection);
        $this->statsManager = new StatisticsManager($connection);
    }
    
    public function index() {
        $this->checkAuth();
        
        // 获取统计数据
        $basicStats = $this->statsManager->getBasicStats();
        $todayStats = $this->statsManager->getTodayStats();
        $weeklyTrend = $this->statsManager->getWeeklyTrend();
        $cardTypeStats = $this->statsManager->getCardTypeStats();
        $recentCards = $this->statsManager->getRecentUsedCards(5);
        
        // 准备统计数据
        $statsData = [
            [
                'icon' => 'fas fa-key',
                'color' => '#3498db',
                'title' => '总卡密数',
                'value' => $basicStats['total'],
                'subtitle' => '系统中所有卡密'
            ],
            [
                'icon' => 'fas fa-check-circle',
                'color' => '#2ecc71',
                'title' => '已使用',
                'value' => $basicStats['used'],
                'subtitle' => '使用率 ' . $basicStats['usage_rate'] . '%'
            ],
            [
                'icon' => 'fas fa-clock',
                'color' => '#f1c40f',
                'title' => '未使用',
                'value' => $basicStats['unused'],
                'subtitle' => '待激活卡密'
            ],
            [
                'icon' => 'fas fa-calendar-day',
                'color' => '#e74c3c',
                'title' => '今日使用',
                'value' => $todayStats['used'],
                'subtitle' => '今日新增 ' . $todayStats['created'] . ' 个'
            ]
        ];
        
        // 准备图表数据
        $chartLabels = array_column($weeklyTrend, 'date');
        $chartData = array_column($weeklyTrend, 'count');
        
        // 将图表数据存储到session中供JavaScript使用
        $_SESSION['chart_labels'] = $chartLabels;
        $_SESSION['chart_data'] = $chartData;
        
        // 准备卡密类型数据
        $typeStatsData = [
            [
                'icon' => 'fas fa-clock',
                'color' => '#3498db',
                'title' => '时间卡密',
                'value' => $cardTypeStats['time'],
                'subtitle' => '按时间计费'
            ],
            [
                'icon' => 'fas fa-sort-numeric-up',
                'color' => '#9b59b6',
                'title' => '次数卡密',
                'value' => $cardTypeStats['count'],
                'subtitle' => '按次数计费'
            ]
        ];
        
        // 渲染页面
        $this->layout->setTitle('数据统计')->setCurrentPage('stats');
        
        $content = $this->renderMessages();
        $content .= UIComponents::renderStatsGrid($statsData);
        
        // 图表区域 - 使用趋势图全宽
        $content .= '<div class="chart-section">';
        $content .= UIComponents::renderCard(
            '使用趋势',
            '<div class="chart-container"><canvas id="usageChart"></canvas></div>',
            UIComponents::renderButton('刷新数据', 'button', 'btn-sm btn-outline-primary', ['onclick' => 'refreshChart()'])
        );
        $content .= '</div>';
        
        // 卡密类型分布 - 全宽显示
        $content .= '<div class="chart-section">';
        $content .= UIComponents::renderCard('卡密类型分布', UIComponents::renderStatsGrid($typeStatsData));
        $content .= '</div>';
        
        // 最近使用记录
        if(!empty($recentCards)) {
            $headers = ['卡密', '类型', '使用时间', '设备ID'];
            $rows = [];
            foreach($recentCards as $card) {
                $rows[] = [
                    '<code title="' . htmlspecialchars($card['card_key']) . '">' . htmlspecialchars($card['card_key']) . '</code>',
                    $card['card_type'] == 'time' ? '时间卡密' : '次数卡密',
                    TimeHelper::format($card['use_time']),
                    $card['device_id'] ? '<span title="' . htmlspecialchars($card['device_id']) . '">' . substr($card['device_id'], 0, 10) . '...</span>' : '-'
                ];
            }
            $content .= UIComponents::renderCard('最近使用记录', UIComponents::renderTable($headers, $rows, 'recent-cards-table'));
        }
        
        // 添加Chart.js脚本
        $this->layout->addScript('../assets/js/chart.min.js');
        
        // 添加内联JavaScript
        $inlineScript = '
// 图表配置
const chartConfig = {
    type: "line",
    data: {
        labels: ' . json_encode($_SESSION['chart_labels'] ?? []) . ',
        datasets: [{
            label: "每日使用量",
            data: ' . json_encode($_SESSION['chart_data'] ?? []) . ',
            borderColor: "#3498db",
            backgroundColor: "rgba(52, 152, 219, 0.1)",
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            intersect: false,
            mode: "index"
        },
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                enabled: true,
                mode: "index",
                intersect: false
            }
        },
        scales: {
            x: {
                display: true,
                title: {
                    display: true,
                    text: "日期"
                }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                },
                title: {
                    display: true,
                    text: "使用次数"
                }
            }
        }
    }
};

// 初始化图表
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById("usageChart");
    if (ctx) {
        window.usageChart = new Chart(ctx.getContext("2d"), chartConfig);
    }
});

// 刷新图表
function refreshChart() {
    location.reload();
}';
        
        $this->layout->addInlineScript($inlineScript);
        
        echo $this->layout->render($content);
    }
}

// 处理请求
try {
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $controller = new StatsController($conn);
    $controller->index();
    
} catch(PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}