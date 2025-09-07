<?php
require_once '../utils/TimeHelper.php';

/**
 * 管理后台布局模板类
 */
class AdminLayout {
    private $title;
    private $currentPage;
    private $breadcrumbs;
    private $content;
    private $scripts;
    private $styles;
    
    public function __construct($title = '管理后台', $currentPage = '') {
        $this->title = $title;
        $this->currentPage = $currentPage;
        $this->breadcrumbs = [];
        $this->scripts = [];
        $this->inlineScripts = [];
        $this->styles = [];
    }
    
    /**
     * 设置页面标题
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }
    
    /**
     * 设置当前页面
     */
    public function setCurrentPage($page) {
        $this->currentPage = $page;
        return $this;
    }
    
    /**
     * 添加面包屑导航
     */
    public function addBreadcrumb($text, $url = '') {
        $this->breadcrumbs[] = ['text' => $text, 'url' => $url];
        return $this;
    }
    
    /**
     * 添加自定义脚本
     */
    public function addScript($src) {
        $this->scripts[] = $src;
        return $this;
    }
    
    /**
     * 添加内联脚本
     */
    public function addInlineScript($script) {
        $this->inlineScripts[] = $script;
        return $this;
    }
    
    /**
     * 添加自定义样式
     */
    public function addStyle($href) {
        $this->styles[] = $href;
        return $this;
    }
    
    /**
     * 渲染页面头部
     */
    public function renderHeader() {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($this->title) . ' - 卡密验证系统</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">';
    
        // 添加自定义样式
        foreach($this->styles as $style) {
            $html .= "\n    <link rel=\"stylesheet\" href=\"{$style}\">";
        }
        
        $html .= '
</head>
<body class="' . ($this->currentPage ? $this->currentPage . '-page' : '') . '">';
        
        return $html;
    }
    
    /**
     * 渲染侧边栏
     */
    public function renderSidebar() {
        $menuItems = [
            ['icon' => 'fas fa-tachometer-alt', 'text' => '首页', 'url' => 'index.php', 'page' => 'index'],
            ['icon' => 'fas fa-plus-circle', 'text' => '生成卡密', 'url' => 'generate_cards.php', 'page' => 'generate_cards'],
            ['icon' => 'fas fa-list', 'text' => '卡密列表', 'url' => 'cards.php', 'page' => 'cards'],
            ['icon' => 'fas fa-chart-line', 'text' => '数据统计', 'url' => 'stats.php', 'page' => 'stats'],
            ['icon' => 'fas fa-cog', 'text' => '系统设置', 'url' => 'settings.php', 'page' => 'settings'],
            ['icon' => 'fas fa-code', 'text' => 'API接口', 'url' => 'api_settings.php', 'page' => 'api_settings'],
        ];
        
        $html = '<div class="sidebar">
            <div class="logo">
                <h2>管理系统</h2>
            </div>
            <ul class="menu">';
        
        foreach($menuItems as $item) {
            $activeClass = ($this->currentPage === $item['page']) ? 'active' : '';
            $html .= '<li class="' . $activeClass . '">
                <a href="' . $item['url'] . '">
                    <i class="' . $item['icon'] . '"></i>' . $item['text'] . '
                </a>
            </li>';
        }
        
        $html .= '<li>
                <a href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i>退出登录
                </a>
            </li>
        </ul>
    </div>';
        
        return $html;
    }
    
    /**
     * 渲染页面头部
     */
    public function renderPageHeader() {
        $html = '<div class="header">
            <h2><i class="fas fa-tachometer-alt"></i> ' . htmlspecialchars($this->title) . '</h2>
            <div class="user-info">
                <img src="../assets/images/avatar.svg" alt="avatar" onerror="this.src=\'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiMzNDk4ZGIiLz4KPGNpcmNsZSBjeD0iMjAiIGN5PSIxNiIgcj0iNiIgZmlsbD0id2hpdGUiLz4KPHBhdGggZD0iTTggMzJjMC02LjYyNyA1LjM3My0xMiAxMi0xMnMxMiA1LjM3MyAxMiAxMiIgZmlsbD0id2hpdGUiLz4KPC9zdmc+\';">
                <span>欢迎，' . htmlspecialchars($_SESSION['admin_name'] ?? '管理员') . '</span>
            </div>
        </div>';
        
        return $html;
    }
    
    /**
     * 渲染面包屑导航
     */
    public function renderBreadcrumbs() {
        if(empty($this->breadcrumbs)) {
            return '';
        }
        
        $html = '<div class="breadcrumbs">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">';
        
        foreach($this->breadcrumbs as $index => $crumb) {
            if($index === count($this->breadcrumbs) - 1) {
                // 最后一个面包屑，不显示链接
                $html .= '<li class="breadcrumb-item active" aria-current="page">' . htmlspecialchars($crumb['text']) . '</li>';
            } else {
                $url = $crumb['url'] ?: '#';
                $html .= '<li class="breadcrumb-item">
                    <a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($crumb['text']) . '</a>
                </li>';
            }
        }
        
        $html .= '</ol>
            </nav>
        </div>';
        
        return $html;
    }
    
    /**
     * 渲染主内容区域开始
     */
    public function renderMainContentStart() {
        return '<div class="main-content">';
    }
    
    /**
     * 渲染主内容区域结束
     */
    public function renderMainContentEnd() {
        return '</div>';
    }
    
    /**
     * 渲染页面底部
     */
    public function renderFooter() {
        $html = '<footer class="footer-copyright">
            <div class="footer-content">
                &copy; ' . TimeHelper::now('Y') . ' 小小怪卡密系统 - All Rights Reserved
            </div>
        </footer>';
        
        return $html;
    }
    
    /**
     * 渲染脚本
     */
    public function renderScripts() {
        $html = '<script src="../assets/js/admin.js"></script>';
        foreach($this->scripts as $script) {
            $html .= "\n<script src=\"{$script}\"></script>";
        }
        
        // 添加内联脚本
        foreach($this->inlineScripts as $script) {
            $html .= "\n<script>{$script}</script>";
        }
        
        $html .= '</body>
</html>';
        
        return $html;
    }
    
    /**
     * 渲染完整的页面布局
     */
    public function render($content) {
        $html = $this->renderHeader();
        $html .= '<div class="admin-wrapper">';
        $html .= $this->renderSidebar();
        $html .= $this->renderMainContentStart();
        $html .= $this->renderBreadcrumbs();
        $html .= $this->renderPageHeader();
        $html .= $content;
        $html .= $this->renderMainContentEnd();
        $html .= '</div>';
        $html .= $this->renderFooter();
        $html .= $this->renderScripts();
        
        return $html;
    }
}
