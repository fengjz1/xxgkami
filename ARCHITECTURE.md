# 系统架构说明

## 🏗️ 整体架构

小小怪卡密验证系统采用现代化的 MVC 架构设计，具有清晰的层次结构和良好的可维护性。

## 📁 目录结构

```
xxgkami/
├── controllers/          # 控制器层 - 处理HTTP请求和响应
│   ├── BaseController.php        # 基础控制器
│   ├── HomeController.php        # 首页控制器
│   ├── AdminLoginController.php  # 管理员登录控制器
│   └── QueryController.php       # 卡密查询控制器
├── models/              # 数据模型层 - 数据库操作
│   ├── CardModel.php            # 卡密数据模型
│   ├── SettingsModel.php        # 系统设置模型
│   └── ApiModel.php             # API数据模型
├── services/            # 业务逻辑层 - 核心业务处理
│   ├── CardService.php          # 卡密业务服务
│   ├── ApiService.php           # API业务服务
│   └── ApiCardService.php       # API卡密服务
├── views/               # 视图层 - 页面模板
│   ├── layouts/                 # 布局模板
│   │   ├── FrontendLayout.php   # 前端布局
│   │   └── AdminLayout.php      # 管理后台布局
│   └── pages/                   # 页面模板
│       ├── home.php             # 首页
│       ├── verify.php           # 卡密验证页
│       ├── query.php            # 卡密查询页
│       └── admin_login.php      # 管理员登录页
├── utils/               # 工具类 - 通用功能
│   ├── Database.php             # 数据库连接
│   ├── Response.php             # HTTP响应处理
│   └── Validator.php            # 数据验证
├── home/                # 管理后台页面 - 已重构的admin页面
│   ├── index.php                # 卡密管理
│   ├── stats.php                # 数据统计
│   ├── settings.php             # 系统设置
│   ├── api_settings.php         # API设置
│   └── card_actions.php         # 卡密操作
├── api/                 # API接口 - RESTful API
│   ├── verify.php               # 卡密验证API
│   └── query.php                # 卡密查询API
├── assets/              # 静态资源
│   ├── css/                     # 样式文件
│   ├── js/                      # JavaScript文件
│   └── images/                  # 图片资源
└── docker/              # Docker配置
    ├── nginx/                   # Nginx配置
    └── supervisor/              # Supervisor配置
```

## 🔄 架构层次

### 1. 控制器层 (Controllers)
- **职责**：处理HTTP请求，调用服务层，返回响应
- **特点**：轻量级，只负责请求路由和响应格式化
- **示例**：`HomeController` 处理首页请求，`AdminLoginController` 处理管理员登录

### 2. 服务层 (Services)
- **职责**：实现核心业务逻辑，协调多个模型
- **特点**：业务逻辑集中，易于测试和维护
- **示例**：`CardService` 处理卡密相关业务，`ApiService` 处理API相关业务

### 3. 模型层 (Models)
- **职责**：数据库操作，数据验证和格式化
- **特点**：专注于数据访问，与业务逻辑分离
- **示例**：`CardModel` 处理卡密数据，`SettingsModel` 处理系统设置

### 4. 视图层 (Views)
- **职责**：页面模板，数据展示
- **特点**：模板化，支持布局复用
- **示例**：`FrontendLayout` 前端布局，`AdminLayout` 管理后台布局

### 5. 工具层 (Utils)
- **职责**：通用功能，跨层使用
- **特点**：无状态，可复用
- **示例**：`Database` 数据库连接，`Validator` 数据验证

## 🔗 数据流向

```
HTTP请求 → 控制器 → 服务层 → 模型层 → 数据库
                ↓
HTTP响应 ← 视图层 ← 控制器 ← 服务层 ← 模型层
```

## 🛡️ 安全特性

### 1. 输入验证
- 使用 `Validator` 类进行统一的数据验证
- 支持CSRF令牌验证
- SQL注入防护（PDO预处理语句）

### 2. 权限控制
- 管理员登录验证
- 会话管理
- API密钥认证

### 3. 数据安全
- 卡密SHA1加密存储
- 敏感信息过滤
- XSS防护

## 🚀 性能优化

### 1. 数据库优化
- 单例模式的数据库连接
- 查询结果缓存
- 索引优化

### 2. 前端优化
- 静态资源CDN
- 图片压缩
- CSS/JS压缩

### 3. 缓存策略
- 会话缓存
- 查询结果缓存
- 静态资源缓存

## 🔧 扩展指南

### 1. 添加新功能
1. 在 `models/` 中创建数据模型
2. 在 `services/` 中实现业务逻辑
3. 在 `controllers/` 中创建控制器
4. 在 `views/` 中创建页面模板

### 2. 添加新API
1. 在 `api/` 目录创建API文件
2. 在 `services/` 中实现API业务逻辑
3. 更新API文档

### 3. 修改数据库
1. 更新 `install/install.sql`
2. 在 `models/` 中更新相关模型
3. 更新数据库升级脚本

## 📝 开发规范

### 1. 命名规范
- 类名：PascalCase (如：`CardService`)
- 方法名：camelCase (如：`getCardStats`)
- 文件名：与类名一致
- 数据库表：snake_case (如：`card_verifications`)

### 2. 代码结构
- 每个类一个文件
- 方法按功能分组
- 添加必要的注释
- 遵循PSR-4自动加载规范

### 3. 错误处理
- 使用异常处理
- 记录错误日志
- 返回友好的错误信息

## 🔍 调试指南

### 1. 日志查看
```bash
# Docker环境
docker-compose logs -f php

# 直接查看PHP错误日志
tail -f /var/log/php_errors.log
```

### 2. 数据库调试
```bash
# 进入MySQL容器
docker-compose exec mysql mysql -u root -p

# 查看表结构
DESCRIBE cards;
```

### 3. 前端调试
- 使用浏览器开发者工具
- 检查网络请求
- 查看控制台错误

## 📚 相关文档

- [README.md](README.md) - 项目介绍和快速开始
- [DOCKER_DEPLOYMENT.md](DOCKER_DEPLOYMENT.md) - Docker部署指南
- [API文档](home/api_settings.php) - API接口文档（在管理后台查看）
