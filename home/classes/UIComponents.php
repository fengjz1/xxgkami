<?php
/**
 * UI组件类
 */
class UIComponents {
    
    /**
     * 渲染统计卡片
     */
    public static function renderStatCard($icon, $color, $title, $value, $subtitle = '') {
        $html = '<div class="stat-card">';
        $html .= '<i class="' . $icon . ' fa-2x" style="color: ' . $color . '; margin-bottom: 10px;"></i>';
        $html .= '<h3>' . htmlspecialchars($title) . '</h3>';
        $html .= '<div class="value">' . htmlspecialchars($value) . '</div>';
        if($subtitle) {
            $html .= '<div class="subtitle">' . htmlspecialchars($subtitle) . '</div>';
        }
        $html .= '</div>';
        return $html;
    }
    
    /**
     * 渲染统计网格
     */
    public static function renderStatsGrid($stats) {
        $html = '<div class="stats-grid">';
        foreach($stats as $stat) {
            $html .= self::renderStatCard(
                $stat['icon'],
                $stat['color'],
                $stat['title'],
                $stat['value'],
                $stat['subtitle'] ?? ''
            );
        }
        $html .= '</div>';
        return $html;
    }
    
    /**
     * 渲染表单组
     */
    public static function renderFormGroup($label, $input, $help = '') {
        $html = '<div class="form-group">';
        $html .= '<label>' . htmlspecialchars($label) . '</label>';
        $html .= $input;
        if($help) {
            $html .= '<small class="form-text text-muted">' . htmlspecialchars($help) . '</small>';
        }
        $html .= '</div>';
        return $html;
    }
    
    /**
     * 渲染输入框
     */
    public static function renderInput($type, $name, $value = '', $attributes = []) {
        $attrs = '';
        foreach($attributes as $key => $val) {
            $attrs .= ' ' . $key . '="' . htmlspecialchars($val) . '"';
        }
        
        return '<input type="' . $type . '" name="' . $name . '" value="' . htmlspecialchars($value) . '" class="form-control"' . $attrs . '>';
    }
    
    /**
     * 渲染选择框
     */
    public static function renderSelect($name, $options, $selected = '', $attributes = []) {
        $attrs = '';
        foreach($attributes as $key => $val) {
            $attrs .= ' ' . $key . '="' . htmlspecialchars($val) . '"';
        }
        
        $html = '<select name="' . $name . '" class="form-control"' . $attrs . '>';
        foreach($options as $value => $text) {
            $selectedAttr = ($value == $selected) ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars($value) . '"' . $selectedAttr . '>' . htmlspecialchars($text) . '</option>';
        }
        $html .= '</select>';
        return $html;
    }
    
    /**
     * 渲染按钮
     */
    public static function renderButton($text, $type = 'button', $class = 'btn-primary', $attributes = []) {
        $attrs = '';
        foreach($attributes as $key => $val) {
            $attrs .= ' ' . $key . '="' . htmlspecialchars($val) . '"';
        }
        
        return '<button type="' . $type . '" class="btn ' . $class . '"' . $attrs . '>' . htmlspecialchars($text) . '</button>';
    }
    
    /**
     * 渲染卡片
     */
    public static function renderCard($title, $content, $actions = '') {
        $html = '<div class="card">';
        $html .= '<div class="card-header">';
        $html .= '<h3>' . htmlspecialchars($title) . '</h3>';
        if($actions) {
            $html .= '<div class="card-actions">' . $actions . '</div>';
        }
        $html .= '</div>';
        $html .= '<div class="card-body">' . $content . '</div>';
        $html .= '</div>';
        return $html;
    }
    
    /**
     * 渲染表格
     */
    public static function renderTable($headers, $rows, $table_class = '', $attributes = []) {
        $attrs = '';
        foreach($attributes as $key => $val) {
            $attrs .= ' ' . $key . '="' . htmlspecialchars($val) . '"';
        }
        
        $html = '<div class="table-responsive ' . $table_class . '">';
        $html .= '<table class="table"' . $attrs . '>';
        $html .= '<thead><tr>';
        foreach($headers as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>';
        }
        $html .= '</tr></thead>';
        $html .= '<tbody>';
        foreach($rows as $row) {
            $html .= '<tr>';
            foreach($row as $cell) {
                $html .= '<td>' . $cell . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';
        return $html;
    }
    
    /**
     * 渲染模态框
     */
    public static function renderModal($id, $title, $content, $actions = '') {
        $html = '<div id="' . $id . '" class="modal">';
        $html .= '<div class="modal-content">';
        $html .= '<h3>' . htmlspecialchars($title) . '</h3>';
        $html .= '<div class="modal-body">' . $content . '</div>';
        if($actions) {
            $html .= '<div class="button-group">' . $actions . '</div>';
        }
        $html .= '</div></div>';
        return $html;
    }
    
    /**
     * 渲染开关按钮
     */
    public static function renderToggle($name, $checked = false, $attributes = []) {
        $attrs = '';
        foreach($attributes as $key => $val) {
            $attrs .= ' ' . $key . '="' . htmlspecialchars($val) . '"';
        }
        
        $checkedAttr = $checked ? ' checked' : '';
        return '<label class="toggle-switch">
            <input type="checkbox" name="' . $name . '"' . $checkedAttr . $attrs . '>
            <span class="toggle-slider"></span>
        </label>';
    }
    
    /**
     * 渲染徽章
     */
    public static function renderBadge($text, $class = 'badge-primary') {
        return '<span class="badge ' . $class . '">' . htmlspecialchars($text) . '</span>';
    }
    
    /**
     * 渲染进度条
     */
    public static function renderProgressBar($value, $max = 100, $class = '') {
        $percentage = ($value / $max) * 100;
        return '<div class="progress ' . $class . '">
            <div class="progress-bar" style="width: ' . $percentage . '%">
                ' . $value . '/' . $max . '
            </div>
        </div>';
    }
}
