# ==============================
# Base PHP image
# ==============================
FROM php:8.3-fpm-bookworm AS base

ARG WWWGROUP=1000
ARG WWWUSER=1000
ARG NODE_VERSION=20

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libpng-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libldap2-dev \
    libicu-dev \
    gnupg \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        bcmath \
        intl \
        opcache \
        ldap \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP configuration
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/app.ini \
    && echo "upload_max_filesize=100M" >> /usr/local/etc/php/conf.d/app.ini \
    && echo "post_max_size=100M" >> /usr/local/etc/php/conf.d/app.ini \
    && echo "variables_order=EGPCS" >> /usr/local/etc/php/conf.d/app.ini

# OPcache tuned for Laravel
RUN echo "opcache.enable=1" > /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.enable_cli=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=2" >> /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/html

# ==============================
# Composer install stage
# ==============================
FROM base AS vendor

COPY composer.json composer.lock ./
ARG COMPOSER_INSTALL_FLAGS="--no-dev"
RUN composer install $COMPOSER_INSTALL_FLAGS \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# ==============================
# Final App Image
# ==============================
FROM base

# Create non-root user
RUN groupadd -g ${WWWGROUP} www \
    && useradd -u ${WWWUSER} -g www -m www

WORKDIR /var/www/html

COPY --chown=www:www . .
COPY --from=vendor /var/www/html/vendor ./vendor

RUN mkdir -p storage bootstrap/cache \
    && chown -R www:www storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Entrypoint fixes storage/cache permissions at startup (needed for bind mounts, e.g. Windows)
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
