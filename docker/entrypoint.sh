#!/usr/bin/env bash
set -e

# When source is mounted (e.g. docker-compose volume), ensure dependencies are installed
if [ -f "composer.json" ] && [ ! -d "vendor" ]; then
    composer install --no-interaction --prefer-dist
fi

# Generate app key if missing (e.g. first run without .env)
if [ -f "artisan" ] && ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    php artisan key:generate --no-interaction 2>/dev/null || true
fi

# Use built Vite assets in container (no dev server). Remove hot so @vite() uses manifest.
if [ -f "public/hot" ]; then
    rm -f public/hot
fi

exec "$@"
