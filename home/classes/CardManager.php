<?php
/**
 * 卡密管理业务逻辑类
 */
class CardManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * 生成卡密
     */
    public function generateKey($length = 20) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $key = '';
        for ($i = 0; $i < $length; $i++) {
            $key .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return [
            'original' => $key,
            'encrypted' => $this->encryptCardKey($key)
        ];
    }
    
    /**
     * 加密卡密
     */
    public function encryptCardKey($key) {
        $salt = 'xiaoxiaoguai_card_system_2024';
        return sha1($key . $salt);
    }
    
    /**
     * 获取统计数据
     */
    public function getStatistics() {
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
     * 批量生成卡密
     */
    public function generateCards($data) {
        $count = intval($data['count'] ?? 1);
        $count = min(max($count, 1), 100);
        
        $card_type = $data['card_type'] ?? 'time';
        $allow_reverify = isset($data['allow_reverify']) && $data['allow_reverify'] == '1' ? 1 : 0;
        
        // 处理时长或次数
        if($card_type == 'time') {
            $duration = $data['duration'];
            if($duration === 'custom') {
                $duration = intval($data['custom_duration']);
            } else {
                $duration = intval($duration);
            }
            $total_count = 0;
            $remaining_count = 0;
        } else {
            $duration = 0;
            $total_count = $data['count_value'];
            if($total_count === 'custom') {
                $total_count = intval($data['custom_count']);
            } else {
                $total_count = intval($total_count);
            }
            $remaining_count = $total_count;
        }
        
        $stmt = $this->conn->prepare("INSERT INTO cards (card_key, encrypted_key, duration, allow_reverify, card_type, total_count, remaining_count) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        for($i = 0; $i < $count; $i++){
            do {
                $key = $this->generateKey();
                $check = $this->conn->prepare("SELECT COUNT(*) FROM cards WHERE encrypted_key = ?");
                $check->execute([$key['encrypted']]);
            } while($check->fetchColumn() > 0);
            
            $stmt->execute([$key['original'], $key['encrypted'], $duration, $allow_reverify, $card_type, $total_count, $remaining_count]);
        }
        
        return $count;
    }
    
    /**
     * 获取卡密列表
     */
    public function getCards($limit = 20, $page = 1, $search = '', $status_filter = '', $type_filter = '') {
        $offset = ($page - 1) * $limit;
        
        // 构建搜索条件
        $whereConditions = [];
        $params = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(card_key LIKE ? OR encrypted_key LIKE ? OR device_id LIKE ?)";
            $searchTerm = "%{$search}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if ($status_filter !== '') {
            $whereConditions[] = "status = ?";
            $params[] = $status_filter;
        }
        
        if (!empty($type_filter)) {
            $whereConditions[] = "card_type = ?";
            $params[] = $type_filter;
        }
        
        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = "WHERE " . implode(' AND ', $whereConditions);
        }
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM cards {$whereClause}";
        $countStmt = $this->conn->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        $total_pages = ceil($total / $limit);
        
        // 获取数据
        $sql = "SELECT * FROM cards {$whereClause} ORDER BY id DESC LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($sql);
        
        // 绑定参数
        $paramIndex = 1;
        foreach ($params as $param) {
            $stmt->bindValue($paramIndex++, $param);
        }
        $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($paramIndex, $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'cards' => $cards,
            'total' => $total,
            'total_pages' => $total_pages,
            'current_page' => $page,
            'limit' => $limit,
            'search' => $search,
            'status_filter' => $status_filter,
            'type_filter' => $type_filter
        ];
    }
    
    /**
     * 删除单个卡密
     */
    public function deleteCard($id) {
        $stmt = $this->conn->prepare("DELETE FROM cards WHERE id = ? AND status = 0");
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }
    
    /**
     * 批量删除卡密
     */
    public function deleteCards($ids) {
        if(empty($ids)) return 0;
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $this->conn->prepare("DELETE FROM cards WHERE id IN ($placeholders) AND status = 0");
        $stmt->execute($ids);
        return $stmt->rowCount();
    }
    
    /**
     * 修改卡密有效期
     */
    public function updateExpireTime($card_id, $expire_time) {
        $stmt = $this->conn->prepare("UPDATE cards SET expire_time = ? WHERE id = ?");
        $stmt->execute([$expire_time, $card_id]);
        return $stmt->rowCount();
    }
    
    /**
     * 修改剩余次数
     */
    public function updateRemainingCount($card_id, $remaining_count) {
        $remaining_count = max(0, intval($remaining_count));
        $stmt = $this->conn->prepare("UPDATE cards SET remaining_count = ? WHERE id = ?");
        $stmt->execute([$remaining_count, $card_id]);
        return $stmt->rowCount();
    }
    
    /**
     * 更新卡密状态
     */
    public function updateCardStatus($card_id, $status) {
        $stmt = $this->conn->prepare("UPDATE cards SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $card_id]);
    }
    
    /**
     * 更新重复验证状态
     */
    public function updateReverifyStatus($card_id, $allow_reverify) {
        $stmt = $this->conn->prepare("UPDATE cards SET allow_reverify = ? WHERE id = ?");
        return $stmt->execute([$allow_reverify, $card_id]);
    }
    
    /**
     * 解绑设备
     */
    public function unbindDevice($card_id) {
        $stmt = $this->conn->prepare("UPDATE cards SET device_id = NULL WHERE id = ? AND status IN (1, 2)");
        return $stmt->execute([$card_id]);
    }
    
    /**
     * 根据ID数组获取卡密数据
     */
    public function getCardsByIds($ids) {
        if(empty($ids)) {
            return [];
        }
        
        // 确保所有ID都是整数
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, function($id) { return $id > 0; });
        
        if(empty($ids)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "SELECT * FROM cards WHERE id IN ($placeholders) ORDER BY id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($ids);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 根据筛选条件获取卡密数据（用于导出）
     */
    public function getCardsForExport($ids = [], $search = '', $status_filter = '', $type_filter = '') {
        // 构建搜索条件
        $whereConditions = [];
        $params = [];
        
        if (!empty($ids)) {
            $ids = array_map('intval', $ids);
            $ids = array_filter($ids, function($id) { return $id > 0; });
            if (!empty($ids)) {
                $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                $whereConditions[] = "id IN ($placeholders)";
                $params = array_merge($params, $ids);
            }
        }
        
        if (!empty($search)) {
            $whereConditions[] = "(card_key LIKE ? OR encrypted_key LIKE ? OR device_id LIKE ?)";
            $searchTerm = "%{$search}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if ($status_filter !== '') {
            $whereConditions[] = "status = ?";
            $params[] = $status_filter;
        }
        
        if (!empty($type_filter)) {
            $whereConditions[] = "card_type = ?";
            $params[] = $type_filter;
        }
        
        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = "WHERE " . implode(' AND ', $whereConditions);
        }
        
        $sql = "SELECT * FROM cards {$whereClause} ORDER BY id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
