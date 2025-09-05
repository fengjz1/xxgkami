# Docker 部署指南

本指南将帮助您使用 Docker Compose 在一台服务器上部署小小怪卡密验证系统。

## 📋 系统要求

- Docker Engine 20.10+
- Docker Compose 2.0+
- 至少 2GB 可用内存
- 至少 10GB 可用磁盘空间

## 🚀 快速开始

### 1. 克隆项目

```bash
git clone https://github.com/fengjz1/xxgkami.git
cd xxgkami
```

### 2. 配置环境变量

```bash
# 复制环境变量模板
cp env.example .env

# 编辑环境变量（可选，使用默认值也可以）
nano .env
```

### 3. 启动服务

```bash
# 构建并启动所有服务
docker-compose up -d

# 查看服务状态
docker-compose ps

# 查看日志
docker-compose logs -f
```

### 4. 访问应用

- **主应用**: http://your-server-ip
- **phpMyAdmin**: http://your-server-ip:8080
- **健康检查**: http://your-server-ip/health

## 🔧 配置说明

### 环境变量

| 变量名 | 默认值 | 说明 |
|--------|--------|------|
| MYSQL_ROOT_PASSWORD | root123456 | MySQL root 密码 |
| MYSQL_DATABASE | xxgkami | 数据库名 |
| MYSQL_USER | xxgkami_user | 数据库用户名 |
| MYSQL_PASSWORD | xxgkami_pass | 数据库密码 |
| TZ | Asia/Shanghai | 时区设置 |

### 端口映射

| 服务 | 容器端口 | 主机端口 | 说明 |
|------|----------|----------|------|
| nginx | 80 | 80 | Web 服务 |
| nginx | 443 | 443 | HTTPS 服务 |
| mysql | 3306 | 3306 | MySQL 数据库 |
| phpmyadmin | 80 | 8080 | 数据库管理 |

## 📁 目录结构

```
xxgkami/
├── docker/
│   ├── nginx/
│   │   ├── nginx.conf          # Nginx 主配置
│   │   └── conf.d/
│   │       └── default.conf    # 站点配置
│   └── supervisor/
│       └── supervisord.conf    # Supervisor 配置
├── docker-compose.yml          # Docker Compose 配置
├── Dockerfile                  # PHP 应用镜像
├── env.example                 # 环境变量模板
└── .dockerignore              # Docker 忽略文件
```

## 🛠️ 常用命令

### 服务管理

```bash
# 启动服务
docker-compose up -d

# 停止服务
docker-compose down

# 重启服务
docker-compose restart

# 查看服务状态
docker-compose ps

# 查看日志
docker-compose logs -f [service_name]
```

### 数据库管理

```bash
# 进入 MySQL 容器
docker-compose exec mysql mysql -u root -p

# 备份数据库
docker-compose exec mysql mysqldump -u root -p xxgkami > backup.sql

# 恢复数据库
docker-compose exec -T mysql mysql -u root -p xxgkami < backup.sql
```

### 应用管理

```bash
# 进入 PHP 容器
docker-compose exec php sh

# 查看应用日志
docker-compose logs -f php

# 重启 PHP 服务
docker-compose restart php
```

## 🔒 安全配置

### 1. 修改默认密码

```bash
# 编辑 .env 文件
nano .env

# 修改以下密码
MYSQL_ROOT_PASSWORD=your_strong_password
MYSQL_PASSWORD=your_strong_password
```

### 2. 配置防火墙

```bash
# 只开放必要端口
ufw allow 80
ufw allow 443
ufw allow 22  # SSH
ufw enable
```

### 3. 配置 HTTPS

1. 将 SSL 证书放在 `docker/nginx/ssl/` 目录
2. 修改 `docker/nginx/conf.d/default.conf` 添加 HTTPS 配置
3. 重启服务：`docker-compose restart nginx`

## 📊 监控和维护

### 查看资源使用情况

```bash
# 查看容器资源使用
docker stats

# 查看磁盘使用
docker system df
```

### 清理无用数据

```bash
# 清理未使用的镜像和容器
docker system prune -a

# 清理未使用的卷
docker volume prune
```

### 日志管理

```bash
# 查看应用日志
docker-compose logs -f nginx
docker-compose logs -f php
docker-compose logs -f mysql

# 限制日志大小（在 docker-compose.yml 中配置）
logging:
  driver: "json-file"
  options:
    max-size: "10m"
    max-file: "3"
```

## 🔄 更新应用

```bash
# 拉取最新代码
git pull

# 重新构建并启动
docker-compose down
docker-compose up -d --build

# 或者只更新特定服务
docker-compose up -d --build php
```

## 🐛 故障排除

### 常见问题

1. **端口被占用**
   ```bash
   # 查看端口使用情况
   netstat -tulpn | grep :80
   
   # 修改 docker-compose.yml 中的端口映射
   ports:
     - "8080:80"  # 改为其他端口
   ```

2. **数据库连接失败**
   ```bash
   # 检查 MySQL 服务状态
   docker-compose logs mysql
   
   # 检查网络连接
   docker-compose exec php ping mysql
   ```

3. **权限问题**
   ```bash
   # 修复文件权限
   docker-compose exec php chown -R www-data:www-data /var/www/html
   ```

### 查看详细日志

```bash
# 查看所有服务日志
docker-compose logs

# 查看特定服务日志
docker-compose logs nginx
docker-compose logs php
docker-compose logs mysql

# 实时查看日志
docker-compose logs -f --tail=100 nginx
```

## 📝 生产环境建议

1. **使用外部数据库**：将 MySQL 数据存储在外部卷或云数据库
2. **配置备份**：定期备份数据库和配置文件
3. **监控告警**：配置服务监控和告警
4. **负载均衡**：使用多个实例进行负载均衡
5. **SSL 证书**：配置 HTTPS 加密传输

## 🆘 获取帮助

如果遇到问题，请：

1. 查看本文档的故障排除部分
2. 检查 GitHub Issues
3. 提交新的 Issue 并附上详细的错误日志

---

**注意**：首次启动后，请访问 http://your-server-ip/install/ 完成系统安装配置。
