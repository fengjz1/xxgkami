#!/bin/bash
set -e

echo "启动 PHP-FPM 服务..."

# 设置文件权限，确保 www-data 用户可以写入
echo "设置文件权限..."
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html

# 确保特定文件可写
touch /var/www/html/config.php /var/www/html/install.lock
chown www-data:www-data /var/www/html/config.php /var/www/html/install.lock
chmod 644 /var/www/html/config.php /var/www/html/install.lock

echo "权限设置完成，启动 php-fpm..."

# 启动 php-fpm
exec /usr/local/sbin/php-fpm --nodaemonize
