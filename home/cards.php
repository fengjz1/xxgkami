<?php
session_start();
if(!isset($_SESSION['admin_id'])){
    header("Location: ../admin.php");
    exit;
}

require_once '../config.php';
require_once 'classes/CardManager.php';
require_once 'classes/ViewRenderer.php';

// 初始化变量
$cards = [];
$per_page_options = [20, 50, 100, 200];
$error = null;
$success = null;
$pagination = ['total_pages' => 0, 'current_page' => 1, 'limit' => 20];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';

try {
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $cardManager = new CardManager($conn);

    // 处理POST请求
    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        // 批量删除卡密
        if(isset($_POST['delete_cards']) && isset($_POST['card_ids'])) {
            $ids = array_map('intval', $_POST['card_ids']);
            $deleted = $cardManager->deleteCards($ids);
            if($deleted > 0) {
                $success = "成功删除 {$deleted} 个卡密";
            } else {
                $error = "没有卡密被删除，可能卡密不存在或已被使用";
            }
        }

        // 修改卡密有效期
        if(isset($_POST['edit_expire_time'])) {
            $card_id = intval($_POST['card_id']);
            $expire_time = $_POST['expire_time'];
            if($cardManager->updateExpireTime($card_id, $expire_time)) {
                $success = "到期时间修改成功";
            } else {
                $error = "修改失败，请检查输入";
            }
        }

        // 修改剩余次数
        if(isset($_POST['edit_remaining_count'])) {
            $card_id = intval($_POST['card_id']);
            $remaining_count = intval($_POST['remaining_count']);
            if($cardManager->updateRemainingCount($card_id, $remaining_count)) {
                $success = "剩余次数修改成功";
            } else {
                $error = "修改失败，请检查输入";
            }
        }

        // 更新卡密状态
        if(isset($_POST['update_status'])) {
            $card_id = intval($_POST['card_id']);
            $status = intval($_POST['status']);
            if($cardManager->updateCardStatus($card_id, $status)) {
                $success = "状态更新成功";
            } else {
                $error = "状态更新失败";
            }
        }

        // 更新重复验证设置
        if(isset($_POST['update_reverify'])) {
            $card_id = intval($_POST['card_id']);
            $allow_reverify = isset($_POST['allow_reverify']) ? 1 : 0;
            if($cardManager->updateReverifyStatus($card_id, $allow_reverify)) {
                $success = "重复验证设置更新成功";
            } else {
                $error = "设置更新失败";
            }
        }

        // 解绑设备
        if(isset($_POST['unbind_device'])) {
            $card_id = intval($_POST['card_id']);
            if($cardManager->unbindDevice($card_id)) {
                $success = "设备解绑成功";
            } else {
                $error = "设备解绑失败";
            }
        }
    }

    // 获取卡密列表
    $limit = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;
    if(!in_array($limit, $per_page_options)) {
        $limit = 20;
    }

    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    
    $result = $cardManager->getCards($limit, $page, $search, $status_filter, $type_filter);
    $cards = $result['cards'];
    $pagination = [
        'total_pages' => $result['total_pages'],
        'current_page' => $result['current_page'],
        'limit' => $result['limit']
    ];
    
} catch(PDOException $e) {
    die("捕获到数据库错误，请检查日志或联系管理员。详细信息： " . $e->getMessage());
}
?>

<?php
require_once 'includes/AdminLayout.php';

$layout = new AdminLayout('卡密列表', 'cards');
echo $layout->renderHeader();
echo '<div class="admin-wrapper">';
echo $layout->renderSidebar();
echo $layout->renderMainContentStart();
echo $layout->renderBreadcrumbs();
echo $layout->renderPageHeader();
?>
        <div class="container">

            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <!-- 搜索区域 -->
            <div class="card search-card">
                <div class="card-body">
                    <div class="search-container">
                        <div class="search-input-group">
                            <input type="text" id="searchInput" placeholder="搜索卡密、设备ID..." class="form-control search-input" value="<?php echo htmlspecialchars($search); ?>">
                            <button type="button" class="btn btn-primary search-btn" onclick="searchCards()">
                                <i class="fas fa-search"></i> 搜索
                            </button>
                            <button type="button" class="btn btn-secondary clear-btn" onclick="clearSearch()">
                                <i class="fas fa-times"></i> 清除
                            </button>
                        </div>
                        <div class="filter-group">
                            <div class="filter-item">
                                <label for="statusFilter">状态筛选：</label>
                                <select id="statusFilter" class="form-control filter-select" onchange="applyFilters()">
                                    <option value="">全部状态</option>
                                    <option value="0" <?php echo (isset($_GET['status']) && $_GET['status'] === '0') ? 'selected' : ''; ?>>未使用</option>
                                    <option value="1" <?php echo (isset($_GET['status']) && $_GET['status'] === '1') ? 'selected' : ''; ?>>已使用</option>
                                    <option value="2" <?php echo (isset($_GET['status']) && $_GET['status'] === '2') ? 'selected' : ''; ?>>已停用</option>
                                </select>
                            </div>
                            <div class="filter-item">
                                <label for="typeFilter">类型筛选：</label>
                                <select id="typeFilter" class="form-control filter-select" onchange="applyFilters()">
                                    <option value="">全部类型</option>
                                    <option value="time" <?php echo (isset($_GET['type']) && $_GET['type'] === 'time') ? 'selected' : ''; ?>>时间卡</option>
                                    <option value="count" <?php echo (isset($_GET['type']) && $_GET['type'] === 'count') ? 'selected' : ''; ?>>次数卡</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 主表格区域 -->
            <div class="card">
                <div class="card-header">
                    <h3>卡密列表</h3>
                    <div class="action-controls">
                        <button type="button" class="btn btn-success" onclick="exportSelected()">
                            <i class="fas fa-file-csv"></i> 导出CSV
                        </button>
                        <button type="button" class="btn btn-danger" onclick="deleteSelected()">
                            <i class="fas fa-trash"></i> 批量删除
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="toggleColumnControls()">
                            <i class="fas fa-columns"></i> 列控制
                        </button>
                        <label class="select-all-container">
                            <input type="checkbox" id="selectAllHeader" onclick="toggleSelectAll()">
                            <span>全选</span>
                        </label>
                    </div>
                </div>
                <div class="table-responsive card-list-table">
                    <table>
                        <thead>
                            <tr>
                                <th width="20">
                                    <input type="checkbox" id="selectAllTable" onclick="toggleSelectAll()">
                                </th>
                                <th>ID</th>
                                <th>卡密</th>
                                <th>状态</th>
                                <th>类型</th>
                                <th>有效期/剩余次数</th>
                                <th>使用时间</th>
                                <th>到期时间</th>
                                <th>创建时间</th>
                                <th>设备ID</th>
                                <th>允许重复验证</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($cards as $card): ?>
                                <?php echo ViewRenderer::renderCardRow($card); ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- 列控制面板 -->
                <div id="columnControls" class="column-controls" style="display: none;">
                    <div class="column-controls-header">
                        <h4>显示列控制</h4>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="toggleColumnControls()">
                            <i class="fas fa-times"></i> 关闭
                        </button>
                    </div>
                    <div class="column-controls-body">
                        <div class="column-checkboxes">
                            <label><input type="checkbox" data-column="1" checked disabled> 选择</label>
                            <label><input type="checkbox" data-column="2" checked> ID</label>
                            <label><input type="checkbox" data-column="3" checked> 卡密</label>
                            <label><input type="checkbox" data-column="4" checked> 状态</label>
                            <label><input type="checkbox" data-column="5" checked> 类型</label>
                            <label><input type="checkbox" data-column="6" checked> 有效期/剩余次数</label>
                            <label><input type="checkbox" data-column="7" checked> 使用时间</label>
                            <label><input type="checkbox" data-column="8" checked> 到期时间</label>
                            <label><input type="checkbox" data-column="9" checked> 创建时间</label>
                            <label><input type="checkbox" data-column="10" checked> 设备ID</label>
                            <label><input type="checkbox" data-column="11" checked> 允许重复验证</label>
                            <label><input type="checkbox" data-column="12" checked disabled> 操作</label>
                        </div>
                        <div class="column-controls-actions">
                            <button type="button" class="btn btn-sm btn-primary" onclick="showAllColumns()">显示全部</button>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="hideOptionalColumns()">隐藏次要列</button>
                        </div>
                    </div>
                </div>
                
                <?php echo ViewRenderer::renderPagination($pagination, $per_page_options); ?>
            </div>
        </div>
    </div>

    <!-- 添加模态框 -->
    <div id="editExpireTimeModal" class="modal">
        <div class="modal-content">
            <h3>修改到期时间</h3>
            <form id="editExpireTimeForm">
                <input type="hidden" name="card_id" id="editCardId">
                <div class="form-group">
                    <label>到期时间：</label>
                    <input type="datetime-local" name="expire_time" id="editExpireTime" required>
                </div>
                <div class="button-group">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editCountModal" class="modal">
        <div class="modal-content">
            <h3>修改剩余次数</h3>
            <form id="editCountForm">
                <input type="hidden" name="card_id" id="editCountCardId">
                <div class="form-group">
                    <label>剩余次数：</label>
                    <input type="number" name="remaining_count" id="editRemainingCount" min="0" required>
                </div>
                <div class="button-group">
                    <button type="button" class="btn btn-secondary" onclick="closeCountModal()">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // 搜索功能
    function searchCards() {
        const searchInput = document.getElementById('searchInput');
        const searchTerm = searchInput.value.trim();
        
        // 构建URL参数
        const url = new URL(window.location);
        if (searchTerm) {
            url.searchParams.set('search', searchTerm);
        } else {
            url.searchParams.delete('search');
        }
        url.searchParams.delete('page'); // 重置到第一页
        
        // 跳转到搜索页面
        window.location.href = url.toString();
    }
    
    function clearSearch() {
        document.getElementById('searchInput').value = '';
        document.getElementById('statusFilter').value = '';
        document.getElementById('typeFilter').value = '';
        searchCards();
    }
    
    // 应用筛选条件
    function applyFilters() {
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const typeFilter = document.getElementById('typeFilter');
        
        // 构建URL参数
        const url = new URL(window.location);
        
        // 搜索条件
        const searchTerm = searchInput.value.trim();
        if (searchTerm) {
            url.searchParams.set('search', searchTerm);
        } else {
            url.searchParams.delete('search');
        }
        
        // 状态筛选
        const statusValue = statusFilter.value;
        if (statusValue) {
            url.searchParams.set('status', statusValue);
        } else {
            url.searchParams.delete('status');
        }
        
        // 类型筛选
        const typeValue = typeFilter.value;
        if (typeValue) {
            url.searchParams.set('type', typeValue);
        } else {
            url.searchParams.delete('type');
        }
        
        // 重置到第一页
        url.searchParams.delete('page');
        
        // 跳转到筛选页面
        window.location.href = url.toString();
    }
    
    // 导出CSV功能（使用默认文件名）
    function exportSelected() {
        const selectedCards = getSelectedCards();
        if (selectedCards.length === 0) {
            alert('请先选择要导出的卡密');
            return;
        }
        
        // 生成默认文件名
        const now = new Date();
        const dateStr = now.getFullYear() + 
                       String(now.getMonth() + 1).padStart(2, '0') + 
                       String(now.getDate()).padStart(2, '0') + '_' +
                       String(now.getHours()).padStart(2, '0') + 
                       String(now.getMinutes()).padStart(2, '0');
        const fileName = `卡密列表_${dateStr}`;
        
        // 创建表单并提交
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'card_actions.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'export';
        form.appendChild(actionInput);
        
        const fileNameInput = document.createElement('input');
        fileNameInput.type = 'hidden';
        fileNameInput.name = 'file_name';
        fileNameInput.value = fileName;
        form.appendChild(fileNameInput);
        
        // 添加筛选条件
        const statusFilter = document.getElementById('statusFilter');
        const typeFilter = document.getElementById('typeFilter');
        const searchInput = document.getElementById('searchInput');
        
        if (statusFilter.value) {
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status_filter';
            statusInput.value = statusFilter.value;
            form.appendChild(statusInput);
        }
        
        if (typeFilter.value) {
            const typeInput = document.createElement('input');
            typeInput.type = 'hidden';
            typeInput.name = 'type_filter';
            typeInput.value = typeFilter.value;
            form.appendChild(typeInput);
        }
        
        if (searchInput.value.trim()) {
            const searchInputHidden = document.createElement('input');
            searchInputHidden.type = 'hidden';
            searchInputHidden.name = 'search_filter';
            searchInputHidden.value = searchInput.value.trim();
            form.appendChild(searchInputHidden);
        }
        
        selectedCards.forEach(cardId => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'card_ids[]';
            input.value = cardId;
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }
    
    // 获取选中的卡密ID
    function getSelectedCards() {
        const checkboxes = document.querySelectorAll('input[name="card_ids[]"]:checked');
        return Array.from(checkboxes).map(cb => cb.value);
    }
    
    // 全选/取消全选
    function toggleSelectAll() {
        const selectAllCheckbox = document.getElementById('selectAllHeader') || document.getElementById('selectAllTable');
        const cardCheckboxes = document.querySelectorAll('input[name="card_ids[]"]');
        
        cardCheckboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });
    }
    
    // 列控制功能
    function toggleColumnControls() {
        const controls = document.getElementById('columnControls');
        controls.style.display = controls.style.display === 'none' ? 'block' : 'none';
    }
    
    function showAllColumns() {
        const checkboxes = document.querySelectorAll('.column-checkboxes input[type="checkbox"]:not([disabled])');
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
        });
        updateColumnVisibility();
    }
    
    function hideOptionalColumns() {
        const optionalColumns = [7, 8, 9, 10, 11]; // 使用时间、到期时间、创建时间、设备ID、允许重复验证
        const checkboxes = document.querySelectorAll('.column-checkboxes input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            const column = parseInt(checkbox.dataset.column);
            if (optionalColumns.includes(column)) {
                checkbox.checked = false;
            }
        });
        updateColumnVisibility();
    }
    
    function updateColumnVisibility() {
        const checkboxes = document.querySelectorAll('.column-checkboxes input[type="checkbox"]');
        let visibleColumns = 0;
        
        checkboxes.forEach(checkbox => {
            const column = parseInt(checkbox.dataset.column);
            const isVisible = checkbox.checked;
            
            // 更新表头和表格单元格的显示状态
            const headerCells = document.querySelectorAll(`.card-list-table th:nth-child(${column})`);
            const dataCells = document.querySelectorAll(`.card-list-table td:nth-child(${column})`);
            
            headerCells.forEach(cell => {
                cell.style.display = isVisible ? '' : 'none';
            });
            
            dataCells.forEach(cell => {
                cell.style.display = isVisible ? '' : 'none';
            });
            
            if (isVisible) {
                visibleColumns++;
            }
        });
        
        // 动态调整表格宽度
        const table = document.querySelector('.card-list-table table');
        if (table) {
            // 根据可见列数计算合适的宽度
            const baseWidth = 40 + 60 + 200 + 80 + 80 + 120; // 前6列的基础宽度
            let additionalWidth = 0;
            
            // 为每列计算合适的宽度
            for (let i = 7; i <= 12; i++) {
                const checkbox = document.querySelector(`input[data-column="${i}"]`);
                if (checkbox && checkbox.checked) {
                    if (i === 12) {
                        // 操作列需要更宽的空间来容纳按钮
                        additionalWidth += 180;
                    } else {
                        additionalWidth += 120;
                    }
                }
            }
            
            const totalWidth = Math.max(800, baseWidth + additionalWidth);
            
            table.style.minWidth = totalWidth + 'px';
            table.style.width = 'auto';
        }
    }
    
    // 页面加载完成后初始化
    document.addEventListener('DOMContentLoaded', function() {
        // 为列控制复选框添加事件监听器
        const checkboxes = document.querySelectorAll('.column-checkboxes input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateColumnVisibility);
        });
        
        // 为搜索框添加键盘事件
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchCards();
            }
        });
        
        // 根据屏幕大小自动隐藏一些列
        if (window.innerWidth <= 768) {
            hideOptionalColumns();
        }
        
        // 初始化表格宽度
        updateColumnVisibility();
    });
    
    // 窗口大小改变时重新调整列显示
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) {
            hideOptionalColumns();
        } else if (window.innerWidth > 1024) {
            showAllColumns();
        }
    });
    
    // 复制到剪贴板函数
    function copyToClipboard(text) {
        // 检查是否支持现代clipboard API
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function() {
                showCopySuccess();
            }, function(err) {
                console.error('复制失败: ', err);
                fallbackCopyTextToClipboard(text);
            });
        } else {
            // 使用兼容性方法
            fallbackCopyTextToClipboard(text);
        }
    }
    
    function showCopySuccess() {
        // 创建临时提示元素
        var toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 12px 20px;
            border-radius: 4px;
            font-size: 14px;
            z-index: 9999;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            animation: slideIn 0.3s ease-out;
        `;
        toast.innerHTML = '<i class="fas fa-check"></i> 设备ID已复制到剪贴板';
        
        // 添加动画样式
        var style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
        
        document.body.appendChild(toast);
        
        // 3秒后自动移除
        setTimeout(function() {
            toast.style.animation = 'slideIn 0.3s ease-out reverse';
            setTimeout(function() {
                document.body.removeChild(toast);
                document.head.removeChild(style);
            }, 300);
        }, 3000);
    }
    
    function fallbackCopyTextToClipboard(text) {
        var textArea = document.createElement("textarea");
        textArea.value = text;
        
        // 避免滚动到底部
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        textArea.style.opacity = "0";
        
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            var successful = document.execCommand('copy');
            if (successful) {
                showCopySuccess();
            } else {
                alert('复制失败，请手动复制');
            }
        } catch (err) {
            console.error('复制失败: ', err);
            alert('复制失败，请手动复制');
        }
        
        document.body.removeChild(textArea);
    }
    </script>
    
    <style>
    .filter-group {
        display: flex;
        gap: 20px;
        margin-top: 15px;
        flex-wrap: wrap;
        align-items: center;
    }
    
    .filter-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .filter-item label {
        font-weight: 500;
        color: #555;
        white-space: nowrap;
    }
    
    .filter-select {
        min-width: 120px;
        padding: 6px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background-color: white;
        font-size: 14px;
    }
    
    .filter-select:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
    }
    
    @media (max-width: 768px) {
        .filter-group {
            flex-direction: column;
            align-items: stretch;
        }
        
        .filter-item {
            flex-direction: column;
            align-items: stretch;
        }
        
        .filter-select {
            min-width: auto;
        }
    }
    </style>
<?php 
echo $layout->renderMainContentEnd();
echo '</div>';
echo $layout->renderFooter(); 
echo $layout->renderScripts(); 
?>
