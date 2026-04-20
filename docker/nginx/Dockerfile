# Athena edge nginx: Athena static from app image + Essex static from Essex app image (no ESSEX_PATH bind on server).
#
# Build args (must be declared BEFORE the first FROM — required for FROM ${VAR}).
#   APP_IMAGE       - Athena PHP-FPM image (source of /var/www/html/public)
#   ESSEX_APP_IMAGE - Essex PHP-FPM image (source of /var/www/html/public for Essex vhost)
#
# CI: build/push ATHENAERP_APP_IMAGE first, ensure ESSEX_APP_IMAGE exists (pull), then build nginx.

ARG APP_IMAGE=athenaerp-app-fpm:latest
ARG ESSEX_APP_IMAGE=devfumaco/essex-app:essexV4

FROM ${APP_IMAGE} AS app_src

FROM ${ESSEX_APP_IMAGE} AS essex_src

FROM nginx:1.27-alpine
WORKDIR /var/www/html

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
# Athena: same public/ as the FPM container
COPY --from=app_src /var/www/html/public ./public
# Essex: baked into image (root /var/www/essex/public in default.conf)
COPY --from=essex_src /var/www/html/public /var/www/essex/public

CMD ["nginx", "-g", "daemon off;"]
