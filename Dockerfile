# BeMart — EC-CUBE 4.3 → BEAR.Sunday + Be Framework
#
# Dev/demo image: PHP built-in server + Composer.
# The database (MySQL 8.0) is external — pass it via DATABASE_URL at runtime.
#
#   docker build -t bemart .
#   docker run --rm -p 8080:8080 \
#     -e DATABASE_URL='mysql://root@host.docker.internal:3306/eccubedb?charset=utf8mb4' \
#     bemart
#
# HTML page server (public/page.php) is the same image with a different command:
#   docker run --rm -p 8081:8081 -e DATABASE_URL=... bemart \
#     php -S 0.0.0.0:8081 -t public public/page.php
FROM php:8.4-cli-bookworm

# --- OS packages ---------------------------------------------------------
# git / unzip         : composer (VCS repositories + dist extraction)
# patch               : cweagans/composer-patches applies patches/*.patch on install
# default-mysql-client: sql/setup-db.sh / seed-dev.sh require the `mysql` client
# python3             : sql/seed/build-dev-fixture.py builds the dev catalog seed
# lib*-dev            : build dependencies for the PHP extensions below
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        patch \
        default-mysql-client \
        python3 \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

# --- PHP extensions ------------------------------------------------------
# pdo_mysql: MySQL 8.0 / intl: i18n / gd: TCPDF images / zip: composer & PDFs
# bcmath: numeric domain values / opcache: throughput / apcu: framework cache
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        intl \
        gd \
        zip \
        bcmath \
        opcache \
    && pecl install apcu \
    && docker-php-ext-enable apcu

# --- PHP runtime config --------------------------------------------------
RUN { \
        echo 'memory_limit=512M'; \
        echo 'apc.enable_cli=1'; \
    } > /usr/local/etc/php/conf.d/zz-bemart.ini

# --- Composer ------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# --- Dependencies (cache layer) -----------------------------------------
# Copy only what `composer install` needs first, so edits to application
# code don't invalidate the vendor cache.
#   be/      : path repository (my-vendor/be-mart-be)
#   patches/ : feeds cweagans/composer-patches
COPY composer.json composer.lock ./
COPY be ./be
COPY patches ./patches
RUN composer install --no-interaction --no-progress --prefer-dist --no-autoloader

# --- Application ---------------------------------------------------------
COPY . .
RUN composer dump-autoload --optimize \
    && mkdir -p var/tmp var/log

# --- Serve ---------------------------------------------------------------
# Bind 0.0.0.0 so the server is reachable from outside the container.
# (The composer serve scripts bind 127.0.0.1, which is host-only.)
EXPOSE 8080 8081
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public", "public/index.php"]
