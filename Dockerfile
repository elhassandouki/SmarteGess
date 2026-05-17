FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libicu-dev libpng-dev libonig-dev libxml2-dev curl \
    && docker-php-ext-install pdo pdo_mysql intl zip bcmath \
    && pecl install redis && docker-php-ext-enable redis

WORKDIR /var/www/html
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . .
RUN composer install --no-dev --optimize-autoloader
RUN chown -R www-data:www-data storage bootstrap/cache

CMD ["php-fpm"]
