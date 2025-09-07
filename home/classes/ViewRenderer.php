<?php
/**
 * 视图渲染类
 */
class ViewRenderer {
    
    /**
     * 渲染统计卡片
     */
    public static function renderStatsCards($stats) {
        $html = '<div class="stats-grid">';
        
        $statsData = [
            ['icon' => 'fas fa-key', 'color' => '#3498db', 'title' => '总卡密数', 'value' => $stats['total']],
            ['icon' => 'fas fa-check-circle', 'color' => '#2ecc71', 'title' => '已使用', 'value' => $stats['used']],
            ['icon' => 'fas fa-clock', 'color' => '#f1c40f', 'title' => '未使用', 'value' => $stats['unused']],
            ['icon' => 'fas fa-percentage', 'color' => '#e74c3c', 'title' => '使用率', 'value' => $stats['usage_rate'] . '%']
        ];
        
        foreach($statsData as $stat) {
            $html .= '<div class="stat-card">';
            $html .= '<i class="' . $stat['icon'] . ' fa-2x" style="color: ' . $stat['color'] . '; margin-bottom: 10px;"></i>';
            $html .= '<h3>' . $stat['title'] . '</h3>';
            $html .= '<div class="value">' . $stat['value'] . '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }
    
    /**
     * 渲染卡密表格行
     */
    public static function renderCardRow($card) {
        $html = '<tr>';
        
        // 复选框
        $html .= '<td><input type="checkbox" class="card-checkbox" value="' . htmlspecialchars($card['card_key']) . '"></td>';
        
        // ID
        $html .= '<td>' . $card['id'] . '</td>';
        
        // 卡密
        $html .= '<td>';
        $html .= '<input type="text" value="' . htmlspecialchars($card['card_key']) . '" readonly style="width: 180px;">';
        $html .= '<button type="button" class="copy-btn" onclick="copyCardKey(this)"><i class="fas fa-copy"></i></button>';
        $html .= '</td>';
        
        // 状态
        $statusClass = $card['status'] == 0 ? 'unused' : ($card['status'] == 1 ? 'used' : 'disabled');
        $statusText = $card['status'] == 0 ? '未使用' : ($card['status'] == 1 ? '已使用' : '已停用');
        $html .= '<td><span class="status-badge ' . $statusClass . '">' . $statusText . '</span></td>';
        
        // 类型
        $typeClass = $card['card_type'];
        $typeText = $card['card_type'] == 'time' ? '时间卡密' : '次数卡密';
        $html .= '<td><span class="type-badge ' . $typeClass . '">' . $typeText . '</span></td>';
        
        // 有效期/剩余次数
        if($card['card_type'] == 'time') {
            $durationText = $card['duration'] > 0 ? $card['duration'] . '天' : '永久';
            $html .= '<td><span class="duration-badge">' . $durationText . '</span></td>';
        } else {
            $countText = $card['remaining_count'] . '/' . $card['total_count'] . '次';
            $html .= '<td><span class="count-badge">' . $countText . '</span></td>';
        }
        
        // 使用时间
        $html .= '<td>' . ($card['use_time'] ?: '-') . '</td>';
        
        // 到期时间
        $html .= '<td>' . ($card['expire_time'] ?: '-') . '</td>';
        
        // 创建时间
        $html .= '<td>' . $card['create_time'] . '</td>';
        
        // 设备ID
        if($card['status'] && $card['device_id']) {
            $deviceId = $card['device_id'];
            $displayId = strlen($deviceId) > 10 ? 
                substr($deviceId, 0, 6) . '...' . substr($deviceId, -4) : 
                $deviceId;
            $html .= '<td><span class="device-id" title="' . htmlspecialchars($deviceId) . '">' . htmlspecialchars($displayId) . '</span></td>';
        } else {
            $html .= '<td>-</td>';
        }
        
        // 允许重复验证
        $toggleClass = $card['allow_reverify'] ? 'fa-toggle-on toggle-btn on' : 'fa-toggle-off toggle-btn off';
        $newState = $card['allow_reverify'] ? 0 : 1;
        $html .= '<td><i class="fas ' . $toggleClass . '" onclick="toggleReverify(' . $card['id'] . ', ' . $newState . ', this)"></i></td>';
        
        // 操作按钮
        $html .= '<td>';
        if($card['status'] == 0) {
            $html .= '<button type="button" class="btn btn-danger btn-sm" onclick="deleteUnusedCard(' . $card['id'] . ')" title="删除未使用卡密"><i class="fas fa-trash"></i></button>';
        } else {
            if($card['status'] == 1) {
                $html .= '<button type="button" class="btn btn-warning btn-sm" onclick="toggleCardStatus(' . $card['id'] . ', \'disable\')" title="停用卡密"><i class="fas fa-ban"></i></button>';
            } elseif($card['status'] == 2) {
                $html .= '<button type="button" class="btn btn-success btn-sm" onclick="toggleCardStatus(' . $card['id'] . ', \'enable\')" title="启用卡密"><i class="fas fa-check"></i></button>';
            }
            
            $html .= '<button type="button" class="btn btn-info btn-sm" onclick="promptExpireTime(' . $card['id'] . ', \'' . $card['expire_time'] . '\')" title="修改时间"><i class="fas fa-clock"></i></button>';
            
            if($card['card_type'] == 'count') {
                $html .= '<button type="button" class="btn btn-info btn-sm" onclick="promptRemainingCount(' . $card['id'] . ', ' . $card['remaining_count'] . ')" title="修改次数"><i class="fas fa-sort-numeric-up"></i></button>';
            }
            
            if(!empty($card['device_id'])) {
                $html .= '<button type="button" class="btn btn-secondary btn-sm" onclick="unbindDevice(' . $card['id'] . ')" title="解绑设备"><i class="fas fa-unlink"></i></button>';
            }
            
            $html .= '<button type="button" class="btn btn-danger btn-sm" onclick="deleteUsedCard(' . $card['id'] . ')" title="删除卡密"><i class="fas fa-trash"></i></button>';
        }
        $html .= '</td>';
        
        $html .= '</tr>';
        return $html;
    }
    
    /**
     * 渲染分页
     */
    public static function renderPagination($pagination, $per_page_options) {
        if($pagination['total_pages'] <= 1 && count($per_page_options) <= 1) {
            return '';
        }
        
        $html = '<div class="pagination-container">';
        
        // 每页显示数量选择
        $html .= '<div class="per-page-select">';
        $html .= '<span>每页显示：</span>';
        foreach($per_page_options as $option) {
            $activeClass = $pagination['limit'] == $option ? 'active' : '';
            $html .= '<a href="?limit=' . $option . '" class="per-page-option ' . $activeClass . '">' . $option . '条</a>';
        }
        $html .= '</div>';
        
        // 分页链接
        if($pagination['total_pages'] > 1) {
            $html .= '<div class="pagination">';
            
            // 首页和上一页
            if($pagination['current_page'] > 1) {
                $html .= '<a href="?page=1&limit=' . $pagination['limit'] . '" title="首页"><i class="fas fa-angle-double-left"></i></a>';
                $html .= '<a href="?page=' . ($pagination['current_page']-1) . '&limit=' . $pagination['limit'] . '" title="上一页"><i class="fas fa-angle-left"></i></a>';
            }
            
            // 页码
            $start = max(1, $pagination['current_page'] - 2);
            $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
            
            if($start > 1) {
                $html .= '<span class="pagination-ellipsis">...</span>';
            }
            
            for($i = $start; $i <= $end; $i++) {
                $activeClass = $i == $pagination['current_page'] ? 'active' : '';
                $html .= '<a href="?page=' . $i . '&limit=' . $pagination['limit'] . '" class="' . $activeClass . '">' . $i . '</a>';
            }
            
            if($end < $pagination['total_pages']) {
                $html .= '<span class="pagination-ellipsis">...</span>';
            }
            
            // 下一页和末页
            if($pagination['current_page'] < $pagination['total_pages']) {
                $html .= '<a href="?page=' . ($pagination['current_page']+1) . '&limit=' . $pagination['limit'] . '" title="下一页"><i class="fas fa-angle-right"></i></a>';
                $html .= '<a href="?page=' . $pagination['total_pages'] . '&limit=' . $pagination['limit'] . '" title="末页"><i class="fas fa-angle-double-right"></i></a>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }
}
