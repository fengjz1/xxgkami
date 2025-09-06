<!-- 登录表单 -->
<section class="login-section">
    <div class="container">
        <div class="login-card">
            <div class="card-header">
                <h2><i class="fas fa-lock"></i> 管理员登录</h2>
                <p>请输入管理员账号和密码</p>
            </div>
            
            <div class="card-body">
                <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <form action="admin.php" method="POST" class="login-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    
                    <div class="form-group">
                        <label for="username">
                            <i class="fas fa-user"></i> 用户名
                        </label>
                        <input type="text" id="username" name="username" 
                               placeholder="请输入用户名" required
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-key"></i> 密码
                        </label>
                        <input type="password" id="password" name="password" 
                               placeholder="请输入密码" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-large">
                        <i class="fas fa-sign-in-alt"></i> 登录
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
