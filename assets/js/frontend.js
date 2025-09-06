// 前端JavaScript功能
document.addEventListener('DOMContentLoaded', function() {
    // 初始化轮播图
    initHeroSlider();
    
    // 初始化表单验证
    initFormValidation();
    
    // 初始化动画效果
    initAnimations();
});

// 轮播图功能
function initHeroSlider() {
    const slides = document.querySelectorAll('.hero-slide');
    if (slides.length <= 1) return;
    
    let currentSlide = 0;
    
    // 显示指定幻灯片
    function showSlide(index) {
        slides.forEach((slide, i) => {
            slide.style.opacity = i === index ? '1' : '0';
            slide.style.zIndex = i === index ? '2' : '1';
        });
    }
    
    // 下一张幻灯片
    function nextSlide() {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    }
    
    // 自动轮播
    setInterval(nextSlide, 5000);
    
    // 初始化显示第一张
    showSlide(0);
}

// 表单验证
function initFormValidation() {
    const forms = document.querySelectorAll('.verify-form, .login-form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
}

// 验证表单
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required]');
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            showFieldError(input, '此字段为必填项');
            isValid = false;
        } else {
            clearFieldError(input);
        }
    });
    
    // 验证卡密格式
    const cardKeyInput = form.querySelector('input[name="card_key"]');
    if (cardKeyInput && cardKeyInput.value.trim()) {
        if (!isValidCardKey(cardKeyInput.value.trim())) {
            showFieldError(cardKeyInput, '卡密格式不正确');
            isValid = false;
        }
    }
    
    // 验证设备ID (可选)
    const deviceIdInput = form.querySelector('input[name="device_id"]');
    if (deviceIdInput && deviceIdInput.value.trim()) {
        if (deviceIdInput.value.trim().length < 3) {
            showFieldError(deviceIdInput, '设备ID至少需要3个字符');
            isValid = false;
        }
    }
    
    return isValid;
}

// 验证卡密格式
function isValidCardKey(key) {
    // 卡密应该是8-32位的字母数字组合
    const pattern = /^[A-Za-z0-9]{8,32}$/;
    return pattern.test(key);
}

// 显示字段错误
function showFieldError(input, message) {
    clearFieldError(input);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    errorDiv.style.color = '#dc3545';
    errorDiv.style.fontSize = '14px';
    errorDiv.style.marginTop = '5px';
    
    input.parentNode.appendChild(errorDiv);
    input.style.borderColor = '#dc3545';
}

// 清除字段错误
function clearFieldError(input) {
    const existingError = input.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    input.style.borderColor = '#e1e5e9';
}

// 动画效果
function initAnimations() {
    // 统计卡片动画
    const statCards = document.querySelectorAll('.stat-card');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'fadeInUp 0.6s ease forwards';
            }
        });
    });
    
    statCards.forEach(card => {
        observer.observe(card);
    });
    
    // 特点卡片动画
    const featureCards = document.querySelectorAll('.feature-card');
    featureCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        observer.observe(card);
    });
}

// 添加CSS动画
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .stat-card,
    .feature-card {
        opacity: 0;
    }
`;
document.head.appendChild(style);

// 工具函数
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i>
        ${message}
    `;
    
    // 插入到页面顶部
    const container = document.querySelector('.main-content');
    container.insertBefore(alertDiv, container.firstChild);
    
    // 3秒后自动移除
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

// 复制到剪贴板
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showAlert('已复制到剪贴板', 'success');
    }).catch(() => {
        showAlert('复制失败，请手动复制', 'error');
    });
}

// 为卡密和设备ID添加复制功能
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('card-key') || e.target.classList.contains('device-id')) {
        copyToClipboard(e.target.textContent);
    }
});
