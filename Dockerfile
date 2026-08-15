# BeMart self-contained quickstart image: PHP 8.4 CLI + the BEAR.Sunday app.
# Pairs with docker-compose.yml (mysql:8.0) for a one-command storefront.
#
#   docker compose up --build   →   http://localhost:8080
FROM php:8.4-cli AS app

# System libraries for the PHP extensions + the mysql client the seed scripts use.
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip default-mysql-client \
        libicu-dev libonig-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl mbstring zip gd bcmath \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /app

# Production INI (display_errors off, opcache on). Without it the base image
# runs on the compiled defaults and writes diagnostics into responses.
COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-bemart.ini"

# Dependency layer first (kept cache-friendly): the manifest, the `be` path
# repository, and the composer patches are all that `composer install` needs.
COPY composer.json composer.lock ./
COPY be ./be
COPY patches ./patches
RUN composer install --no-interaction --no-progress --prefer-dist

# Application source. vendor/.git/tools are excluded via .dockerignore so the
# GPL EC-CUBE reference clone under tools/ never enters the image.
COPY . .
RUN composer dump-autoload --optimize \
    && mkdir -p var/tmp var/log && chmod -R 0770 var

EXPOSE 8080
ENTRYPOINT ["/app/docker/entrypoint.sh"]
