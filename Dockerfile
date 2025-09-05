# 使用官方PHP-FPM镜像作为基础镜像
FROM php:8.1-fpm

# 设置工作目录
WORKDIR /var/www/html

# 安装系统依赖
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    mariadb-client \
    curl \
    zip \
    unzip \
    git \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    && rm -rf /var/lib/apt/lists/*

# 安装PHP扩展
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        mbstring \
        xml \
        intl \
        opcache

# 配置PHP
RUN echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/docker-php-memory.ini \
    && echo "upload_max_filesize = 64M" >> /usr/local/etc/php/conf.d/docker-php-upload.ini \
    && echo "post_max_size = 64M" >> /usr/local/etc/php/conf.d/docker-php-upload.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/docker-php-time.ini \
    && echo "date.timezone = Asia/Shanghai" >> /usr/local/etc/php/conf.d/docker-php-timezone.ini

# 配置OPcache
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=2" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini

# 创建必要的目录
RUN mkdir -p /var/lib/php/sessions \
    && mkdir -p /var/log/nginx \
    && mkdir -p /run/nginx \
    && mkdir -p /var/log/supervisor \
    && mkdir -p /var/lib/nginx/body \
    && mkdir -p /var/lib/nginx/fastcgi \
    && mkdir -p /var/lib/nginx/proxy \
    && mkdir -p /var/lib/nginx/scgi \
    && mkdir -p /var/lib/nginx/uwsgi

# 复制Nginx配置
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/conf.d/default.conf /etc/nginx/conf.d/default.conf

# 复制Supervisor配置
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# 复制PHP-FPM配置
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# 复制应用文件，入口脚本将包含在其中
COPY . /var/www/html/

# 确保supervisord配置正确

# 设置权限
RUN chown -R www-data:www-data /var/www/html \
    && chown -R www-data:www-data /var/lib/nginx \
    && chown -R www-data:www-data /var/log/nginx \
    && chown -R www-data:www-data /run/nginx \
    && chown -R www-data:www-data /var/lib/php/sessions

# 等待MySQL服务启动的脚本
RUN echo '#!/bin/bash\n\
echo "等待MySQL服务启动..."\n\
while ! nc -z mysql 3306; do\n\
  echo "MySQL未就绪，等待5秒..."\n\
  sleep 5\n\
done\n\
echo "MySQL已就绪，启动应用..."\n\
exec "$@"' > /usr/local/bin/wait-for-mysql.sh \
    && chmod +x /usr/local/bin/wait-for-mysql.sh

# 暴露端口
EXPOSE 9000

# 创建启动脚本
COPY docker/php/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# 创建必要的文件
RUN touch /var/www/html/config.php /var/www/html/install.lock

# 启动命令
CMD ["/usr/local/bin/start.sh"]
