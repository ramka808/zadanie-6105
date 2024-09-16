FROM php:8.2-fpm

# Устанавливаем рабочую директорию 
WORKDIR /var/www/html

# Устанавливаем зависимости
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libpq-dev \
    libonig-dev \
    libzip-dev \
    nginx

# Очищаем кэш
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Устанавливаем расширения PHP
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl pdo_pgsql

# Загружаем актуальную версию Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Копируем файлы проекта
COPY . /var/www/html

# Устанавливаем зависимости Laravel
RUN composer install --ignore-platform-reqs

# Генерируем ключ приложения
RUN php artisan key:generate

# Настраиваем права доступа
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage

# Копируем конфигурацию Nginx
COPY nginx.conf /etc/nginx/sites-available/default

# Копируем скрипт запуска
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Открываем порт 80 для веб-сервера
EXPOSE 8080

# Запускаем скрипт запуска
CMD ["/usr/local/bin/start.sh"]