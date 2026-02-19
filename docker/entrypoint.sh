#!/usr/bin/env bash
set -e

# When source is mounted (e.g. docker-compose volume), ensure dependencies are installed
if [ -f "composer.json" ] && [ ! -d "vendor" ]; then
    composer install --no-interaction --prefer-dist
fi

# Ensure .env exists (e.g. staging/production where .env is not in the image or volume)
if [ -f "artisan" ] && [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        cp .env.example .env
    else
        echo "WARNING: No .env or .env.example found. Create .env before running artisan commands."
    fi
fi

# Generate app key if missing (e.g. first run without APP_KEY in .env)
if [ -f "artisan" ] && [ -f ".env" ] && ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    php artisan key:generate --no-interaction 2>/dev/null || true
fi

# Use built Vite assets in container (no dev server). Remove hot so @vite() uses manifest.
if [ -f "public/hot" ]; then
    rm -f public/hot
fi

exec "$@"
