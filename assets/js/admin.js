/**
 * 后台管理页面JavaScript
 * 卡密管理系统前端交互逻辑
 */

// 全局变量
let selectedCards = new Set();

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    initializeFormHandlers();
    initializeTableHandlers();
    initializeModalHandlers();
    initializeMobileMenu();
});

/**
 * 初始化表单处理器
 */
function initializeFormHandlers() {
    // 时长选择逻辑
    const durationSelect = document.querySelector('select[name="duration"]');
    const customDurationField = document.querySelector('.custom-duration');
    
    if(durationSelect) {
        durationSelect.addEventListener('change', function() {
            if(this.value === 'custom') {
                customDurationField.style.display = 'block';
            } else {
                customDurationField.style.display = 'none';
            }
        });
    }
    
    // 次数选择逻辑
    const countSelect = document.querySelector('select[name="count_value"]');
    const customCountField = document.querySelector('.custom-count');
    
    if(countSelect) {
        countSelect.addEventListener('change', function() {
            if(this.value === 'custom') {
                customCountField.style.display = 'block';
            } else {
                customCountField.style.display = 'none';
            }
        });
    }
    
    // 卡密类型切换逻辑
    const cardTypeSelect = document.getElementById('card_type');
    const timeDurationField = document.querySelector('.time-duration');
    const countValueField = document.querySelector('.count-value');
    
    if(cardTypeSelect) {
        cardTypeSelect.addEventListener('change', function() {
            if(this.value === 'time') {
                timeDurationField.style.display = 'block';
                countValueField.style.display = 'none';
                customCountField.style.display = 'none';
            } else {
                timeDurationField.style.display = 'none';
                customDurationField.style.display = 'none';
                countValueField.style.display = 'block';
            }
        });
    }
}

/**
 * 初始化表格处理器
 */
function initializeTableHandlers() {
    // 全选功能
    const selectAllCheckbox = document.getElementById('selectAll');
    if(selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', toggleSelectAll);
    }
    
    // 监听单个复选框的变化
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('card-checkbox')) {
            updateSelectAllState();
        }
    });
}

/**
 * 初始化模态框处理器
 */
function initializeModalHandlers() {
    // 修改到期时间表单提交
    const editExpireTimeForm = document.getElementById('editExpireTimeForm');
    if(editExpireTimeForm) {
        editExpireTimeForm.addEventListener('submit', handleEditExpireTimeSubmit);
    }
    
    // 修改次数表单提交
    const editCountForm = document.getElementById('editCountForm');
    if(editCountForm) {
        editCountForm.addEventListener('submit', handleEditCountSubmit);
    }
    
    // 点击模态框外关闭模态框
    window.addEventListener('click', function(event) {
        const expireTimeModal = document.getElementById('editExpireTimeModal');
        const countModal = document.getElementById('editCountModal');
        if (event.target === expireTimeModal) {
            closeModal();
        } else if (event.target === countModal) {
            closeCountModal();
        }
    });
}

/**
 * 初始化移动端菜单
 */
function initializeMobileMenu() {
    // 创建移动端菜单切换按钮
    const menuToggle = document.createElement('button');
    menuToggle.className = 'mobile-menu-toggle';
    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
    menuToggle.onclick = toggleMobileMenu;
    document.body.appendChild(menuToggle);
    
    // 点击侧边栏外关闭菜单
    document.addEventListener('click', function(event) {
        const sidebar = document.querySelector('.sidebar');
        const menuToggle = document.querySelector('.mobile-menu-toggle');
        
        if (window.innerWidth <= 768 && 
            !sidebar.contains(event.target) && 
            !menuToggle.contains(event.target)) {
            sidebar.classList.remove('open');
        }
    });
    
    // 窗口大小改变时处理菜单状态
    window.addEventListener('resize', function() {
        const sidebar = document.querySelector('.sidebar');
        if (window.innerWidth > 768) {
            sidebar.classList.remove('open');
        }
    });
}

/**
 * 切换移动端菜单
 */
function toggleMobileMenu() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('open');
}

/**
 * 复制卡密
 */
function copyCardKey(btn) {
    const input = btn.previousElementSibling;
    input.select();
    document.execCommand('copy');
    
    // 更新按钮状态
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i> 已复制';
    btn.style.background = '#2ecc71';
    
    setTimeout(() => {
        btn.innerHTML = originalHtml;
        btn.style.background = '#3498db';
    }, 2000);
}

/**
 * 全选/取消全选
 */
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.card-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
        if(selectAll.checked) {
            selectedCards.add(checkbox.value);
        } else {
            selectedCards.delete(checkbox.value);
        }
    });
}

/**
 * 更新全选状态
 */
function updateSelectAllState() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.card-checkbox');
    const checkedBoxes = document.querySelectorAll('.card-checkbox:checked');
    
    selectAll.checked = checkboxes.length === checkedBoxes.length;
    
    // 更新选中卡密集合
    selectedCards.clear();
    checkedBoxes.forEach(checkbox => {
        selectedCards.add(checkbox.value);
    });
}

/**
 * 导出为Excel
 */
function exportSelected() {
    const checkboxes = document.querySelectorAll('.card-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('请至少选择一个卡密');
        return;
    }

    try {
        // 收集选中的卡密信息
        const selectedCards = Array.from(checkboxes).map(checkbox => {
            const row = checkbox.closest('tr');
            return {
                'ID': row.cells[1].textContent,
                '卡密': checkbox.value,
                '状态': row.querySelector('.status-badge').textContent.trim(),
                '类型': row.querySelector('.type-badge').textContent.trim(),
                '有效期/剩余次数': row.querySelector('.duration-badge, .count-badge').textContent.trim(),
                '使用时间': row.cells[6].textContent.trim(),
                '到期时间': row.cells[7].textContent.trim(),
                '创建时间': row.cells[8].textContent.trim(),
                '设备ID': row.cells[9].textContent.trim()
            };
        });
        
        // 获取文件名
        let fileName = document.getElementById('exportFileName').value.trim() || '卡密列表';
        if (!fileName.toLowerCase().endsWith('.xlsx')) {
            fileName += '.xlsx';
        }

        // 创建工作簿
        const wb = XLSX.utils.book_new();
        
        // 添加标题行
        const ws = XLSX.utils.json_to_sheet(selectedCards, {
            header: ['ID', '卡密', '状态', '类型', '有效期/剩余次数', '使用时间', '到期时间', '创建时间', '设备ID']
        });

        // 设置列宽
        const colWidths = [
            { wch: 8 },   // ID
            { wch: 25 },  // 卡密
            { wch: 10 },  // 状态
            { wch: 10 },  // 类型
            { wch: 15 },  // 有效期/剩余次数
            { wch: 20 },  // 使用时间
            { wch: 20 },  // 到期时间
            { wch: 20 },  // 创建时间
            { wch: 30 }   // 设备ID
        ];
        ws['!cols'] = colWidths;

        // 添加工作表到工作簿
        XLSX.utils.book_append_sheet(wb, ws, '卡密列表');

        // 导出Excel文件
        XLSX.writeFile(wb, fileName);
    } catch (error) {
        console.error('导出失败:', error);
        alert('导出失败，请稍后重试');
    }
}

/**
 * 批量删除
 */
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.card-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('请至少选择一个卡密');
        return;
    }

    if(!confirm(`确定要删除选中的 ${checkboxes.length} 个卡密吗？此操作不可恢复！`)) {
        return;
    }

    // 收集选中的卡密ID
    const cardIds = Array.from(checkboxes).map(checkbox => {
        const row = checkbox.closest('tr');
        return row.cells[1].textContent; // ID列
    });

    // 创建表单并提交
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';

    // 添加操作标识
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'delete_cards';
    actionInput.value = '1';
    form.appendChild(actionInput);

    // 添加卡密ID
    cardIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'card_ids[]';
        input.value = id;
        form.appendChild(input);
    });

    // 提交表单
    document.body.appendChild(form);
    form.submit();
}

/**
 * 删除未使用卡密
 */
function deleteUnusedCard(id) {
    if(confirm('确定要删除这个未使用的卡密吗？此操作不可恢复！')) {
        window.location.href = '?delete=' + id;
    }
}

/**
 * 删除已使用卡密
 */
async function deleteUsedCard(cardId) {
    if(confirm('确定要删除这个已使用的卡密吗？此操作不可恢复！')) {
        const result = await sendActionRequest('delete_used', { card_id: cardId });
        alert(result.message);
        if (result.success) {
            window.location.reload();
        }
    }
}

/**
 * 切换卡密状态（启用/停用）
 */
async function toggleCardStatus(cardId, statusAction) {
    const actionText = statusAction === 'disable' ? '停用' : '启用';
    if (!confirm(`确定要${actionText}这个卡密吗？`)) return;

    const result = await sendActionRequest(statusAction, { card_id: cardId });
    alert(result.message);
    if (result.success) {
        window.location.reload();
    }
}

/**
 * 切换允许重复验证状态
 */
async function toggleReverify(cardId, newState, iconElement) {
    // 乐观更新 UI
    iconElement.classList.toggle('on', newState === 1);
    iconElement.classList.toggle('off', newState === 0);
    iconElement.classList.toggle('fa-toggle-on', newState === 1);
    iconElement.classList.toggle('fa-toggle-off', newState === 0);
    iconElement.setAttribute('onclick', `toggleReverify(${cardId}, ${newState === 1 ? 0 : 1}, this)`);

    const result = await sendActionRequest('toggle_reverify', { card_id: cardId, allow_reverify: newState });
    
    if (!result.success) {
        alert(result.message);
        // 如果失败，则恢复 UI
        const oldState = newState === 1 ? 0 : 1;
        iconElement.classList.toggle('on', oldState === 1);
        iconElement.classList.toggle('off', oldState === 0);
        iconElement.classList.toggle('fa-toggle-on', oldState === 1);
        iconElement.classList.toggle('fa-toggle-off', oldState === 0);
        iconElement.setAttribute('onclick', `toggleReverify(${cardId}, ${oldState === 1 ? 0 : 1}, this)`);
    }
}

/**
 * 解绑设备
 */
async function unbindDevice(cardId) {
    if (!confirm('确定要解绑此卡密的设备吗？解绑后，任何设备都可以使用此卡密重新验证并绑定。')) return;

    const result = await sendActionRequest('unbind_device', { card_id: cardId });
    alert(result.message);
    if (result.success) {
        window.location.reload();
    }
}

/**
 * 弹出修改到期时间模态框
 */
function promptExpireTime(id, expireTime) {
    const modal = document.getElementById('editExpireTimeModal');
    const editCardId = document.getElementById('editCardId');
    const editExpireTime = document.getElementById('editExpireTime');
    
    editCardId.value = id;
    
    // 如果有到期时间，则格式化为datetime-local格式
    if(expireTime && expireTime !== '-') {
        const date = new Date(expireTime);
        const formattedDate = date.toISOString().slice(0, 16);
        editExpireTime.value = formattedDate;
    } else {
        // 如果没有到期时间，则默认设置为一个月后
        const date = new Date();
        date.setMonth(date.getMonth() + 1);
        editExpireTime.value = date.toISOString().slice(0, 16);
    }
    
    modal.style.display = 'block';
}

/**
 * 关闭修改到期时间模态框
 */
function closeModal() {
    document.getElementById('editExpireTimeModal').style.display = 'none';
}

/**
 * 弹出修改次数模态框
 */
function promptRemainingCount(id, remainingCount) {
    const modal = document.getElementById('editCountModal');
    const editCountCardId = document.getElementById('editCountCardId');
    const editRemainingCount = document.getElementById('editRemainingCount');
    
    editCountCardId.value = id;
    editRemainingCount.value = remainingCount;
    
    modal.style.display = 'block';
}

/**
 * 关闭修改次数模态框
 */
function closeCountModal() {
    document.getElementById('editCountModal').style.display = 'none';
}

/**
 * 处理修改到期时间表单提交
 */
function handleEditExpireTimeSubmit(e) {
    e.preventDefault();
    const cardId = document.getElementById('editCardId').value;
    const expireTime = document.getElementById('editExpireTime').value;
    
    const form = document.createElement('form');
    form.method = 'POST';
    
    const cardIdInput = document.createElement('input');
    cardIdInput.type = 'hidden';
    cardIdInput.name = 'card_id';
    cardIdInput.value = cardId;
    form.appendChild(cardIdInput);
    
    const expireTimeInput = document.createElement('input');
    expireTimeInput.type = 'hidden';
    expireTimeInput.name = 'expire_time';
    expireTimeInput.value = expireTime;
    form.appendChild(expireTimeInput);
    
    const submitInput = document.createElement('input');
    submitInput.type = 'hidden';
    submitInput.name = 'edit_expire_time';
    submitInput.value = '1';
    form.appendChild(submitInput);
    
    document.body.appendChild(form);
    form.submit();
}

/**
 * 处理修改次数表单提交
 */
function handleEditCountSubmit(e) {
    e.preventDefault();
    const cardId = document.getElementById('editCountCardId').value;
    const remainingCount = document.getElementById('editRemainingCount').value;
    
    const form = document.createElement('form');
    form.method = 'POST';
    
    const cardIdInput = document.createElement('input');
    cardIdInput.type = 'hidden';
    cardIdInput.name = 'card_id';
    cardIdInput.value = cardId;
    form.appendChild(cardIdInput);
    
    const remainingCountInput = document.createElement('input');
    remainingCountInput.type = 'hidden';
    remainingCountInput.name = 'remaining_count';
    remainingCountInput.value = remainingCount;
    form.appendChild(remainingCountInput);
    
    const submitInput = document.createElement('input');
    submitInput.type = 'hidden';
    submitInput.name = 'edit_count';
    submitInput.value = '1';
    form.appendChild(submitInput);
    
    document.body.appendChild(form);
    form.submit();
}

/**
 * 通用AJAX请求函数
 */
async function sendActionRequest(action, data) {
    try {
        const response = await fetch('card_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ action, ...data })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('请求失败:', error);
        alert('操作失败，请检查网络或联系管理员');
        return { success: false, message: '请求失败' };
    }
}

/**
 * 工具函数：显示加载状态
 */
function showLoading(element, text = '处理中...') {
    const originalText = element.innerHTML;
    element.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${text}`;
    element.disabled = true;
    return originalText;
}

/**
 * 工具函数：隐藏加载状态
 */
function hideLoading(element, originalText) {
    element.innerHTML = originalText;
    element.disabled = false;
}

/**
 * 工具函数：显示通知
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.textContent = message;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
