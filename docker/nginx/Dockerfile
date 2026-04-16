# Athena edge nginx: static assets from Athena PHP-FPM image public/.
#
# Build arg (must be declared BEFORE the first FROM — required for FROM ${VAR}).
#   APP_IMAGE - Athena PHP-FPM image (source of /var/www/html/public)
#
# CI: build/push ATHENAERP_APP_IMAGE first, then build nginx.

ARG APP_IMAGE=athenaerp-app-fpm:latest

FROM ${APP_IMAGE} AS app_src

FROM nginx:1.27-alpine
WORKDIR /var/www/html

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app_src /var/www/html/public ./public

CMD ["nginx", "-g", "daemon off;"]
