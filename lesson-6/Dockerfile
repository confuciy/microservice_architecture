FROM php:8.2-fpm as php-app

# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    wget \
    curl \
    autoconf \
    g++ \
    make \
    libtool \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && pecl install redis && docker-php-ext-enable redis

# Устанавливаем Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer --quiet

# Установка NGINX Prometheus Exporter
RUN apt-get update -o Acquire::ForceIPv4=true && apt-get install -y \
    wget \
    && wget https://github.com/nginxinc/nginx-prometheus-exporter/releases/download/v0.10.0/nginx-prometheus-exporter_0.10.0_linux_amd64.tar.gz \
    && tar -xzf nginx-prometheus-exporter_0.10.0_linux_amd64.tar.gz \
    && mv nginx-prometheus-exporter /usr/local/bin/ \
    && rm nginx-prometheus-exporter_0.10.0_linux_amd64.tar.gz

# Копирование конфигураций
COPY ./docker/nginx.conf /etc/nginx/nginx.conf
COPY ./docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
#COPY ./src/Config /var/www/html/src/Config
COPY ./src/Database /var/www/html/src/Database
COPY ./src/Service/Health /var/www/html/src/Service/Health
COPY ./src/Service/Metrics /var/www/html/src/Service/Metrics
COPY ./src/Service/Main /var/www/html/src/Service/Main
COPY ./src/index.php /var/www/html/src/index.php
COPY ./src/routes.php /var/www/html/src/routes.php
COPY ./composer.* /var/www/html/
COPY ./composer.json /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Install PHP dependencies
RUN composer install --no-scripts --no-autoloader --no-interaction
RUN composer dump-autoload

# Запуск Nginx и PHP-FPM
#CMD service nginx start && nginx-prometheus-exporter -nginx.scrape-uri http://127.0.0.1/nginx_status && php-fpm

# Запуск через supervisord
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]






FROM php:8.2-fpm as service-user

# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    wget \
    curl \
    autoconf \
    g++ \
    make \
    libtool \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && pecl install redis && docker-php-ext-enable redis

# Устанавливаем Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer --quiet

# Установка NGINX Prometheus Exporter
RUN apt-get update -o Acquire::ForceIPv4=true && apt-get install -y \
    wget \
    && wget https://github.com/nginxinc/nginx-prometheus-exporter/releases/download/v0.10.0/nginx-prometheus-exporter_0.10.0_linux_amd64.tar.gz \
    && tar -xzf nginx-prometheus-exporter_0.10.0_linux_amd64.tar.gz \
    && mv nginx-prometheus-exporter /usr/local/bin/ \
    && rm nginx-prometheus-exporter_0.10.0_linux_amd64.tar.gz

# Копирование конфигураций
COPY ./docker/nginx.conf /etc/nginx/nginx.conf
COPY ./docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
#COPY ./src/Config /var/www/html/src/Config
COPY ./src/Database /var/www/html/src/Database
COPY ./src/Service/Health /var/www/html/src/Service/Health
COPY ./src/Service/Metrics /var/www/html/src/Service/Metrics
COPY ./src/Service/User /var/www/html/src/Service/User
COPY ./src/index.php /var/www/html/src/index.php
COPY ./src/routes.php /var/www/html/src/routes.php
COPY ./composer.* /var/www/html/
COPY ./composer.json /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Install PHP dependencies
RUN composer install --no-scripts --no-autoloader --no-interaction
RUN composer dump-autoload

# Запуск Nginx и PHP-FPM
#CMD service nginx start && nginx-prometheus-exporter -nginx.scrape-uri http://127.0.0.1/nginx_status && php-fpm

# Запуск через supervisord
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]





FROM php:8.2-fpm as service-auth

# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    wget \
    curl \
    autoconf \
    g++ \
    make \
    libtool \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && pecl install redis && docker-php-ext-enable redis

# Устанавливаем Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer --quiet

# Установка NGINX Prometheus Exporter
RUN apt-get update -o Acquire::ForceIPv4=true && apt-get install -y \
    wget \
    && wget https://github.com/nginxinc/nginx-prometheus-exporter/releases/download/v0.10.0/nginx-prometheus-exporter_0.10.0_linux_amd64.tar.gz \
    && tar -xzf nginx-prometheus-exporter_0.10.0_linux_amd64.tar.gz \
    && mv nginx-prometheus-exporter /usr/local/bin/ \
    && rm nginx-prometheus-exporter_0.10.0_linux_amd64.tar.gz

# Копирование конфигураций
COPY ./docker/nginx.conf /etc/nginx/nginx.conf
COPY ./docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
#COPY ./src/Config /var/www/html/src/Config
COPY ./src/Database /var/www/html/src/Database
COPY ./src/Service/Health /var/www/html/src/Service/Health
COPY ./src/Service/Metrics /var/www/html/src/Service/Metrics
COPY ./src/Service/Auth /var/www/html/src/Service/Auth
COPY ./src/index.php /var/www/html/src/index.php
COPY ./src/routes.php /var/www/html/src/routes.php
COPY ./composer.* /var/www/html/
COPY ./composer.json /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Install PHP dependencies
RUN composer install --no-scripts --no-autoloader --no-interaction
RUN composer dump-autoload

# Запуск Nginx и PHP-FPM
#CMD service nginx start && nginx-prometheus-exporter -nginx.scrape-uri http://127.0.0.1/nginx_status && php-fpm

# Запуск через supervisord
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]





FROM php:8.2-fpm as service-notification

# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    wget \
    curl \
    autoconf \
    g++ \
    make \
    libtool \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && pecl install redis && docker-php-ext-enable redis

# Устанавливаем Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer --quiet

# Установка NGINX Prometheus Exporter
RUN apt-get update -o Acquire::ForceIPv4=true && apt-get install -y \
    wget \
    && wget https://github.com/nginxinc/nginx-prometheus-exporter/releases/download/v0.10.0/nginx-prometheus-exporter_0.10.0_linux_amd64.tar.gz \
    && tar -xzf nginx-prometheus-exporter_0.10.0_linux_amd64.tar.gz \
    && mv nginx-prometheus-exporter /usr/local/bin/ \
    && rm nginx-prometheus-exporter_0.10.0_linux_amd64.tar.gz

# Копирование конфигураций
COPY ./docker/nginx.conf /etc/nginx/nginx.conf
COPY ./docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
#COPY ./src/Config /var/www/html/src/Config
COPY ./src/Database /var/www/html/src/Database
COPY ./src/Service/Health /var/www/html/src/Service/Health
COPY ./src/Service/Metrics /var/www/html/src/Service/Metrics
COPY ./src/Service/Notification /var/www/html/src/Service/Notification
COPY ./src/index.php /var/www/html/src/index.php
COPY ./src/routes.php /var/www/html/src/routes.php
COPY ./composer.* /var/www/html/
COPY ./composer.json /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Install PHP dependencies
RUN composer install --no-scripts --no-autoloader --no-interaction
RUN composer dump-autoload

# Запуск Nginx и PHP-FPM
#CMD service nginx start && nginx-prometheus-exporter -nginx.scrape-uri http://127.0.0.1/nginx_status && php-fpm

# Запуск через supervisord
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]