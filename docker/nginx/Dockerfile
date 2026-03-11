# Copy public (including Vite build) from the app image so Nginx serves the same
# assets as the app. Build app first, then build nginx with build-arg APP_IMAGE=<that image>.
ARG APP_IMAGE=athenaerp-app-fpm:latest
FROM ${APP_IMAGE} AS app_src

FROM nginx:1.27-alpine
WORKDIR /var/www/html

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
# Serve the same public/ (including build/assets) as the app container
COPY --from=app_src /var/www/html/public ./public

# Ensure this image runs nginx, not any other process
CMD ["nginx", "-g", "daemon off;"]

