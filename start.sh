# Запускаем миграции
php artisan migrate --force

# Запускаем Nginx
service nginx start

# Запускаем PHP-FPM
php-fpm
php artisan serve --port=8080  

