FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update -o Acquire::ForceIPv4=true && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    nginx \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql zip

# Установка расширения Redis для PHP
RUN pecl install redis && docker-php-ext-enable redis

# Установка NGINX Prometheus Exporter
RUN apt-get update -o Acquire::ForceIPv4=true && apt-get install -y \
    wget \
    && wget https://github.com/nginxinc/nginx-prometheus-exporter/releases/download/v0.10.0/nginx-prometheus-exporter_0.10.0_linux_amd64.tar.gz \
    && tar -xzf nginx-prometheus-exporter_0.10.0_linux_amd64.tar.gz \
    && mv nginx-prometheus-exporter /usr/local/bin/ \
    && rm nginx-prometheus-exporter_0.10.0_linux_amd64.tar.gz

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www/html

# Создаем директорию для логов Nginx
RUN mkdir -p /var/log/nginx

# Настройка Nginx конфигурации
RUN echo 'server { \
    listen 80; \
    listen [::]:80; \
    server_name localhost arch.homework; \
    root /var/www/html/src; \
    index index.php index.html index.htm; \
    access_log /var/log/nginx/access.log; \
    error_log /var/log/nginx/error.log; \
    location / { \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
    location ~ \.php$ { \
        include snippets/fastcgi-php.conf; \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
        include fastcgi_params; \
    } \
    location ~ /\.ht { \
        deny all; \
    } \
    location /nginx_status { \
        stub_status on; \
        access_log off; \
        allow all; \
    } \
}' > /etc/nginx/conf.d/default.conf

# Copy application code
COPY . ./

# Install PHP dependencies
RUN composer install --no-scripts --no-autoloader --no-interaction
RUN composer dump-autoload

# Set permissions
RUN chown -R www-data:www-data ./
RUN chown -R www-data:www-data /var/log
RUN chown -R www-data:www-data /var/log/nginx

# Expose port
EXPOSE 80 3000 9000

# Запуск Nginx и PHP-FPM
CMD service nginx start && nginx-prometheus-exporter -nginx.scrape-uri http://arch.homework/nginx_status & php-fpm