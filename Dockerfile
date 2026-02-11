# AthenaERP - Laravel application
FROM php:8.3-cli-bookworm

ARG WWWGROUP=1000
ARG WWWUSER=1000
ARG NODE_VERSION=20

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC

# System dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libgd-dev \
    libldap2-dev \
    libicu-dev \
    unzip \
    zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
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
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP config
RUN echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/app.ini \
    && echo "upload_max_filesize=100M" >> /usr/local/etc/php/conf.d/app.ini \
    && echo "post_max_size=100M" >> /usr/local/etc/php/conf.d/app.ini \
    && echo "variables_order=EGPCS" >> /usr/local/etc/php/conf.d/app.ini

# OPcache: cache compiled PHP (faster page loads with volume mount)
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=2" >> /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/html

# Install app dependencies (cache layer)
COPY composer.json composer.lock ./
# Install deps (use COMPOSER_INSTALL_FLAGS=--no-dev for production build)
ARG COMPOSER_INSTALL_FLAGS=""
RUN composer install $COMPOSER_INSTALL_FLAGS --no-scripts --no-autoloader --prefer-dist

COPY . .

RUN composer dump-autoload --optimize \
    && composer run-script post-autoload-dump --no-interaction 2>/dev/null || true

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# Optional: build frontend assets (uncomment if you want built assets in image)
# COPY package.json package-lock.json* ./
# RUN curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash - \
#     && apt-get install -y nodejs \
#     && npm ci && npm run build && rm -rf node_modules

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
