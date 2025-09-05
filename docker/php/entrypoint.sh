#!/bin/bash
set -e

# 将 /var/www/html 目录的所有权变更为 www-data 用户和组
# 这样做可以确保PHP-FPM进程有权限写入文件（例如：创建config.php）
echo "正在修复文件权限..."
chown -R www-data:www-data /var/www/html

echo "权限修复完成，启动核心服务..."

# 执行传递给脚本的原始命令 (即 Dockerfile 中的 CMD)
exec "$@"
