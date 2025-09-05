#!/bin/bash
set -e

echo "启动 PHP-FPM 服务..."

# 创建必要的文件，但不修改权限（避免影响宿主机）
echo "创建必要文件..."
touch /var/www/html/config.php /var/www/html/install.lock

echo "启动 php-fpm..."

# 启动 php-fpm
exec /usr/local/sbin/php-fpm --nodaemonize
