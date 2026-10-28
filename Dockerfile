FROM php:8.4-cli

ENV COMPOSER_ALLOW_SUPERUSER=1

# Librerias del sistema que necesitan las extensiones de PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libpq-dev libzip-dev libonig-dev libicu-dev libsqlite3-dev \
        ca-certificates curl gnupg \
    && docker-php-ext-install pdo_pgsql pdo_sqlite mbstring zip bcmath intl pcntl \
    && curl -fsSL https://deb.nodesource.com/setup_24.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Dependencias PHP primero (aprovecha la cache de capas)
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Codigo de la aplicacion
COPY . .

# Dependencias de frontend y compilacion de assets con Vite
RUN npm ci --no-audit --no-fund && npm run build

RUN composer dump-autoload --optimize

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
