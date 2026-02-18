#!/bin/sh
set -e

# Ensure Laravel storage and cache dirs exist and are writable (fixes bind-mount permissions)
mkdir -p storage/logs \
         storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/app/public \
         bootstrap/cache

# chmod/chown are not supported on Windows bind mounts; running container as root allows writes

exec php-fpm
