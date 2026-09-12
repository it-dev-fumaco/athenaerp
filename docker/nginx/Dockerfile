# Athena edge nginx: Athena static from app image + Essex static from Essex app image (no ESSEX_PATH bind on server).
#
# Build args (must be declared BEFORE the first FROM — required for FROM ${VAR}).
#   APP_IMAGE         - Athena PHP-FPM image (source of /var/www/html/public)
#   ESSEX_APP_IMAGE   - Essex PHP-FPM image (source of /var/www/html/public for Essex vhost).
#                      Hub tag is case-sensitive (use v4 not V4).
#   LMS_APP_IMAGE     - Laravel LMS PHP-FPM image (same layout: /var/www/html/public → /var/www/lms/public in nginx)
#   LMS_KIOSK_APP_IMAGE - LMSForProduction PHP-FPM image → /var/www/lms-kiosk/public in nginx
#   COST_ENGINE_APP_IMAGE - Cost Engine PHP-FPM image → /var/www/cost-engine/public in nginx
#
# CI: build/push ATHENAERP_APP_IMAGE; pull ESSEX_APP_IMAGE, LMS_APP_IMAGE, LMS_KIOSK_APP_IMAGE; have COST_ENGINE_APP_IMAGE local or on a registry; then build nginx.
# Local: `docker compose -f docker-compose.prod.yml build nginx`
# Do not copy LMS from essex_src — the Essex image has no LMS tree.

ARG APP_IMAGE=athenaerp-app-fpm:latest
ARG ESSEX_APP_IMAGE=devfumaco/essex-app:v4
ARG LMS_APP_IMAGE=lms-app-fpm:latest
ARG LMS_KIOSK_APP_IMAGE=devfumaco/lms-kiosk:staging
ARG COST_ENGINE_APP_IMAGE=cost-engine-app-fpm:latest

FROM ${APP_IMAGE} AS app_src

FROM ${ESSEX_APP_IMAGE} AS essex_src

FROM ${LMS_APP_IMAGE} AS lms_src

FROM ${LMS_KIOSK_APP_IMAGE} AS lms_kiosk_src

FROM ${COST_ENGINE_APP_IMAGE} AS cost_engine_src

FROM nginx:1.27-alpine
WORKDIR /var/www/html

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
# Athena: same public/ as the FPM container
COPY --from=app_src /var/www/html/public ./public
# Essex: baked into image (root /var/www/essex/public in default.conf)
COPY --from=essex_src /var/www/html/public /var/www/essex/public
# LMS: same public/ as the LMS container
COPY --from=lms_src /var/www/html/public /var/www/lms/public
# LMS kiosk: full public/ (index.php + css/js assets) from FPM image at nginx build time
COPY --from=lms_kiosk_src /var/www/html/public /var/www/lms-kiosk/public
COPY --from=cost_engine_src /var/www/html/public /var/www/cost-engine/public
RUN test -f /var/www/lms-kiosk/public/css/app.css \
    && test -f /var/www/lms-kiosk/public/js/app.js \
    || (echo "ERROR: lms-kiosk public/css/app.css or public/js/app.js missing — pull/build LMS_KIOSK_APP_IMAGE then rebuild nginx" && exit 1)
RUN test -f /var/www/cost-engine/public/build/manifest.json \
    || (echo "ERROR: cost-engine public/build/manifest.json missing — build COST_ENGINE_APP_IMAGE then rebuild nginx" && exit 1)

CMD ["nginx", "-g", "daemon off;"]
