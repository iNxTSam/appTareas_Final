FROM php:8.4-cli

ENV COMPOSER_ALLOW_SUPERUSER=1

# Librerias del sistema que necesitan las extensiones de PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libpq-dev libzip-dev libonig-dev libicu-dev libsqlite3-dev \
        ca-certificates curl \
    && docker-php-ext-install pdo_pgsql pdo_sqlite mbstring zip bcmath intl pcntl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ============================================================
# Xdebug para pruebas cobertura de código
# ============================================================
RUN apt-get update && apt-get install -y --no-install-recommends \
        $PHPIZE_DEPS \
    && pecl channel-update pecl.php.net \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && printf 'xdebug.mode=coverage\nxdebug.start_with_request=yes\nxdebug.log_level=0\n' \
        > /usr/local/etc/php/conf.d/xdebug-coverage.ini \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

#subir memoria para pruebas con cobertura
RUN printf 'memory_limit=1G\n' > /usr/local/etc/php/conf.d/memory-limit.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Dependencias PHP primero (aprovecha la cache de capas)
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Codigo de la aplicacion
COPY . .

RUN composer dump-autoload --optimize

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
