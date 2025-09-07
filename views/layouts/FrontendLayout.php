<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['site_title']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($settings['site_subtitle']); ?>">
    <link rel="stylesheet" href="assets/css/frontend.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <h2><?php echo htmlspecialchars($settings['site_title']); ?></h2>
            </div>
            <div class="nav-menu">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-home"></i> 首页
                </a>
                <a href="verify.php" class="nav-link">
                    <i class="fas fa-key"></i> 卡密验证
                </a>
                <a href="query.php" class="nav-link">
                    <i class="fas fa-search"></i> 卡密查询
                </a>
                <a href="admin.php" class="nav-link">
                    <i class="fas fa-cog"></i> 管理后台
                </a>
            </div>
        </div>
    </nav>

    <!-- 主要内容 -->
    <main class="main-content">
        <?php echo $content; ?>
    </main>

    <!-- 页脚 -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3><?php echo htmlspecialchars($settings['site_title']); ?></h3>
                <p><?php echo htmlspecialchars($settings['site_subtitle']); ?></p>
            </div>
            
            <div class="footer-section">
                <h4>联系我们</h4>
                <div class="contact-info">
                    <?php if (!empty($settings['contact_qq_group'])): ?>
                    <p><i class="fab fa-qq"></i> QQ群: <?php echo htmlspecialchars($settings['contact_qq_group']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($settings['contact_email'])): ?>
                    <p><i class="fas fa-envelope"></i> 邮箱: <?php echo htmlspecialchars($settings['contact_email']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($settings['contact_wechat_qr']) && file_exists($settings['contact_wechat_qr'])): ?>
                    <div class="wechat-qr">
                        <img src="<?php echo htmlspecialchars($settings['contact_wechat_qr']); ?>" alt="微信二维码">
                        <p>扫码加群</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>系统特点</h4>
                <ul>
                    <li><i class="fas fa-shield-alt"></i> 安全可靠</li>
                    <li><i class="fas fa-code"></i> API接口</li>
                    <li><i class="fas fa-tachometer-alt"></i> 高效稳定</li>
                    <li><i class="fas fa-chart-line"></i> 数据统计</li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo TimeHelper::now('Y'); ?> <?php echo htmlspecialchars($settings['copyright_text']); ?></p>
        </div>
    </footer>

    <script src="assets/js/frontend.js"></script>
</body>
</html>
