# Используем официальный образ PHP с поддержкой FPM и нужной версией
FROM php:8.2-fpm

# Установка Nginx
RUN apt-get update && apt-get install -y nginx

# Установка рабочего каталога
WORKDIR /var/www/html

# Настройка Nginx конфигурации
RUN echo 'server { \
    listen 80; \
    listen [::]:80; \
    server_name localhost arch.homework; \
    root /var/www/html/public; \
    index index.php index.html index.htm; \
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
}' > /etc/nginx/conf.d/default.conf

# Копируем папку public в контейнер
COPY public/ ./public/

# Открываем необходимые порты
EXPOSE 80 9000

# Запуск Nginx и PHP-FPM
CMD service nginx start && php-fpm