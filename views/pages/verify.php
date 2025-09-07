<!-- 验证表单 -->
<section class="verify-section">
    <div class="container">
        <div class="verify-card">
            <div class="card-header">
                <h2><i class="fas fa-key"></i> 卡密验证</h2>
                <p>请输入您的卡密和设备ID进行验证</p>
            </div>
            
            <div class="card-body">
                <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
                
                <?php if (isset($card_data)): ?>
                <div class="card-result">
                    <h3>卡密信息</h3>
                    <div class="result-grid">
                        <div class="result-item">
                            <label>卡密:</label>
                            <span class="card-key"><?php echo htmlspecialchars($card_data['card_key']); ?></span>
                        </div>
                        
                        <div class="result-item">
                            <label>状态:</label>
                            <span class="status status-<?php echo $card_data['status'] == 1 ? 'used' : 'unused'; ?>">
                                <?php echo $card_data['status'] == 1 ? '已使用' : '未使用'; ?>
                            </span>
                        </div>
                        
                        <?php if ($card_data['card_type'] == 'time'): ?>
                        <div class="result-item">
                            <label>类型:</label>
                            <span class="type-badge time">时间卡密</span>
                        </div>
                        
                        <?php if ($card_data['duration'] > 0): ?>
                        <div class="result-item">
                            <label>时长:</label>
                            <span><?php echo $card_data['duration']; ?> 天</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($card_data['expire_time']): ?>
                        <div class="result-item">
                            <label>到期时间:</label>
                            <span><?php echo TimeHelper::format($card_data['expire_time']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php else: ?>
                        <div class="result-item">
                            <label>类型:</label>
                            <span class="type-badge count">次数卡密</span>
                        </div>
                        
                        <div class="result-item">
                            <label>总次数:</label>
                            <span><?php echo $card_data['total_count']; ?></span>
                        </div>
                        
                        <div class="result-item">
                            <label>剩余次数:</label>
                            <span class="remaining-count"><?php echo $card_data['remaining_count']; ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($card_data['use_time']): ?>
                        <div class="result-item">
                            <label>使用时间:</label>
                            <span><?php echo TimeHelper::format($card_data['use_time']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($card_data['device_id']): ?>
                        <div class="result-item">
                            <label>设备ID:</label>
                            <span class="device-id"><?php echo htmlspecialchars($card_data['device_id']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                
                <form action="verify.php" method="POST" class="verify-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    
                    <div class="form-group">
                        <label for="card_key">
                            <i class="fas fa-key"></i> 卡密
                        </label>
                        <input type="text" id="card_key" name="card_key" 
                               placeholder="请输入您的卡密" required
                               value="<?php echo htmlspecialchars($_POST['card_key'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="device_id">
                            <i class="fas fa-mobile-alt"></i> 设备ID (可选)
                        </label>
                        <input type="text" id="device_id" name="device_id" 
                               placeholder="请输入设备ID (可选)"
                               value="<?php echo htmlspecialchars($_POST['device_id'] ?? ''); ?>">
                        <small class="form-help">设备ID用于防重复使用，可以是任意唯一标识。如果不填写，卡密将不绑定设备</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-large">
                        <i class="fas fa-search"></i> 验证卡密
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

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
