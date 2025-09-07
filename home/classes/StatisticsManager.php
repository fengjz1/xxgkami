<?php
/**
 * 统计数据管理类
 */
class StatisticsManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * 获取基础统计数据
     */
    public function getBasicStats() {
        $total = $this->conn->query("SELECT COUNT(*) FROM cards")->fetchColumn();
        $used = $this->conn->query("SELECT COUNT(*) FROM cards WHERE status = 1")->fetchColumn();
        $unused = $total - $used;
        $usage_rate = $total > 0 ? round(($used / $total) * 100, 1) : 0;
        
        return [
            'total' => $total,
            'used' => $used,
            'unused' => $unused,
            'usage_rate' => $usage_rate
        ];
    }
    
    /**
     * 获取今日数据
     */
    public function getTodayStats() {
        $today_start = date('Y-m-d 00:00:00');
        $today_end = date('Y-m-d 23:59:59');
        
        $today_used = $this->conn->query("SELECT COUNT(*) FROM cards WHERE status = 1 AND use_time BETWEEN '$today_start' AND '$today_end'")->fetchColumn();
        $today_created = $this->conn->query("SELECT COUNT(*) FROM cards WHERE create_time BETWEEN '$today_start' AND '$today_end'")->fetchColumn();
        
        return [
            'used' => $today_used,
            'created' => $today_created
        ];
    }
    
    /**
     * 获取最近7天的使用趋势
     */
    public function getWeeklyTrend() {
        $daily_stats = [];
        for($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $start = $date . ' 00:00:00';
            $end = $date . ' 23:59:59';
            $count = $this->conn->query("SELECT COUNT(*) FROM cards WHERE status = 1 AND use_time BETWEEN '$start' AND '$end'")->fetchColumn();
            $daily_stats[] = [
                'date' => date('m-d', strtotime($date)),
                'count' => $count
            ];
        }
        return $daily_stats;
    }
    
    /**
     * 获取卡密类型统计
     */
    public function getCardTypeStats() {
        $time_cards = $this->conn->query("SELECT COUNT(*) FROM cards WHERE card_type = 'time'")->fetchColumn();
        $count_cards = $this->conn->query("SELECT COUNT(*) FROM cards WHERE card_type = 'count'")->fetchColumn();
        
        return [
            'time' => $time_cards,
            'count' => $count_cards
        ];
    }
    
    /**
     * 获取最近使用的卡密
     */
    public function getRecentUsedCards($limit = 10) {
        $stmt = $this->conn->prepare("SELECT * FROM cards WHERE status = 1 AND use_time IS NOT NULL ORDER BY use_time DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 获取API调用统计
     */
    public function getApiStats() {
        $today_start = date('Y-m-d 00:00:00');
        $today_end = date('Y-m-d 23:59:59');
        
        $today_calls = $this->conn->query("SELECT SUM(use_count) FROM api_keys WHERE last_use_time BETWEEN '$today_start' AND '$today_end'")->fetchColumn() ?: 0;
        $total_calls = $this->conn->query("SELECT SUM(use_count) FROM api_keys")->fetchColumn() ?: 0;
        
        return [
            'today_calls' => $today_calls,
            'total_calls' => $total_calls
        ];
    }
}
