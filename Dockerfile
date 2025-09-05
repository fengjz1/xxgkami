# 使用官方PHP-FPM镜像作为基础镜像
FROM php:8.1-fpm-alpine

# 设置工作目录
WORKDIR /var/www/html

# 安装系统依赖
RUN apk add --no-cache \
    nginx \
    supervisor \
    mysql-client \
    curl \
    zip \
    unzip \
    git \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev \
    icu-dev

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
    && mkdir -p /var/log/supervisor

# 复制Nginx配置
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/conf.d/default.conf /etc/nginx/conf.d/default.conf

# 复制Supervisor配置
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# 复制应用文件，入口脚本将包含在其中
COPY . /var/www/html/

# 确保项目内的入口脚本有执行权限
RUN chmod +x /var/www/html/docker/php/init.sh

# 设置权限
RUN chown -R www-data:www-data /var/www/html

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

# 重写基础镜像的ENTRYPOINT，使用我们的初始化脚本
ENTRYPOINT ["/var/www/html/docker/php/init.sh"]

# 启动命令
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
