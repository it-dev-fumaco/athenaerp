FROM nginx:1.27-alpine

WORKDIR /var/www/html

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY public ./public

# Ensure this image runs nginx, not any other process
CMD ["nginx", "-g", "daemon off;"]

