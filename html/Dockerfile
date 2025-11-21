FROM php:8.2-fpm

# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    libonig-dev \
    libexif-dev \
    gettext \
    zip \
    unzip \
    wget \
    curl \
    autoconf \
    g++ \
    make \
    libtool \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql mysqli zip gd intl gettext mbstring iconv sockets exif \
    && docker-php-ext-enable sockets
    #&& pecl install redis && docker-php-ext-enable redis

# Устанавливаем настройки PHP
RUN echo "upload_max_filesize = 60M" > /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size = 62M" >> /usr/local/etc/php/conf.d/uploads.ini

# Устанавливаем Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer --quiet

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html

COPY --chown=www-data:www-data ./composer.* /var/www/html/
COPY --chown=www-data:www-data ./composer.json /var/www/html/

WORKDIR /var/www/html

# Install PHP dependencies
RUN composer install --no-scripts --no-autoloader --no-interaction
RUN composer dump-autoload