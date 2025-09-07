<!-- 轮播图 -->
<?php if (!empty($slides)): ?>
<section class="hero-section">
    <div class="container">
        <div class="hero-slider">
        <?php foreach ($slides as $slide): ?>
        <div class="hero-slide">
            <div class="hero-content">
                <h1><?php echo htmlspecialchars($slide['title']); ?></h1>
                <p><?php echo htmlspecialchars($slide['description']); ?></p>
                <a href="verify.php" class="btn btn-primary">
                    <i class="fas fa-key"></i> 立即验证
                </a>
            </div>
            <?php if (!empty($slide['image_url']) && file_exists($slide['image_url'])): ?>
            <div class="hero-image">
                <img src="<?php echo htmlspecialchars($slide['image_url']); ?>" alt="<?php echo htmlspecialchars($slide['title']); ?>">
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>


<!-- 系统特点 -->
<?php if (!empty($features)): ?>
<section class="features-section">
    <div class="container">
        <h2 class="section-title">系统特点</h2>
        <div class="features-grid">
            <?php foreach ($features as $feature): ?>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="<?php echo htmlspecialchars($feature['icon']); ?>"></i>
                </div>
                <div class="feature-content">
                    <h3><?php echo htmlspecialchars($feature['title']); ?></h3>
                    <p><?php echo nl2br(htmlspecialchars($feature['description'])); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- 快速验证 -->
<section class="quick-verify-section">
    <div class="container">
        <div class="quick-verify-card">
            <h2>快速验证卡密</h2>
            <p>输入您的卡密，快速验证其有效性</p>
            <form action="verify.php" method="POST" class="verify-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
                <div class="form-group">
                    <input type="text" name="card_key" placeholder="请输入卡密" required>
                </div>
                <div class="form-group">
                    <input type="text" name="device_id" placeholder="请输入设备ID (可选)">
                </div>
                <button type="submit" class="btn btn-primary btn-large">
                    <i class="fas fa-search"></i> 验证卡密
                </button>
            </form>
            
            <div class="quick-actions">
                <a href="query.php" class="btn btn-secondary">
                    <i class="fas fa-info-circle"></i> 查询卡密信息
                </a>
            </div>
        </div>
    </div>
</section>
