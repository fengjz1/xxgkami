#!/bin/bash
set -e

echo "启动 PHP-FPM 服务..."

# 仅修改应用所需文件的权限，避免影响宿主机上的git项目
echo "设置 config.php 和 install.lock 的权限..."
touch /var/www/html/config.php /var/www/html/install.lock
chown www-data:www-data /var/www/html/config.php /var/www/html/install.lock

echo "权限设置完成，启动 php-fpm..."

# 启动 php-fpm
exec /usr/local/sbin/php-fpm --nodaemonize
