#!/usr/bin/env bash
# Don't use set -e so one failing step (e.g. chmod on mount) doesn't prevent PHP-FPM from starting

# Ensure storage subdirs exist and are writable (FPM runs as root in Docker pool)
if [ -d "storage" ]; then
    mkdir -p \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/cache \
        storage/framework/cache/data \
        storage/fonts \
        storage/logs \
        bootstrap/cache 2>/dev/null || true
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
    chmod -R 775 storage bootstrap/cache 2>/dev/null || true
    chmod -R 777 storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache 2>/dev/null || true
fi

# When source is mounted (e.g. docker-compose volume), ensure dependencies are installed
if [ -f "composer.json" ] && [ ! -d "vendor" ]; then
    composer install --no-interaction --prefer-dist || true
fi

# Ensure .env exists (e.g. staging/production where .env is not in the image or volume)
if [ -f "artisan" ] && [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        cp .env.example .env
    else
        echo "WARNING: No .env or .env.example found. Create .env before running artisan commands."
    fi
fi

# Skip key:generate here — artisan can hang on OpenTelemetry shutdown and block PHP-FPM.

# Use built Vite assets in container (no dev server). Remove hot so @vite() uses manifest.
if [ -f "public/hot" ]; then
    rm -f public/hot
fi

# Do not run artisan cache here. Those commands can hang on shutdown
# (OpenTelemetry export to :4318) and nginx then returns 502 until FPM starts.
# Warm caches after the container is up, e.g.:
#   docker exec athenaerp-app php artisan config:cache --no-interaction

exec "$@"
