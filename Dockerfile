# TurneroYa · Imagen de producción PHP 8.2 + Nginx + PHP-FPM + Postgres client
# Multi-stage: primero deps de composer, luego runtime mínimo.

# ---- Stage 1: composer install ----
FROM composer:2 AS deps
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --no-progress --optimize-autoloader

# ---- Stage 2: runtime ----
FROM php:8.2-fpm-alpine AS runtime

# Paquetes del sistema: nginx + extensiones PHP + gettext para envsubst
RUN apk add --no-cache \
    nginx \
    supervisor \
    bash \
    curl \
    gettext \
    postgresql-client \
    postgresql-dev \
    oniguruma-dev \
    libzip-dev \
 && docker-php-ext-install pdo pdo_pgsql pgsql mbstring

# Config de PHP, Nginx, supervisord y entrypoint
COPY docker/php.ini /usr/local/etc/php/conf.d/zzz-turneroya.ini
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Workdir
WORKDIR /var/www/html

# Copiamos app + vendor desde el stage anterior
COPY --from=deps /app/vendor ./vendor
COPY . .

# Permisos: storage debe ser writable por el usuario del php-fpm (www-data)
RUN mkdir -p storage/logs storage/sessions storage/uploads \
 && chown -R www-data:www-data storage public \
 && chmod -R 775 storage

# Render (y muchos PaaS) inyectan $PORT. Nginx escucha ahí.
ENV PORT=10000
EXPOSE 10000

# Entrypoint prepara nginx.conf ($PORT) y corre migraciones, luego supervisord
CMD ["/entrypoint.sh"]
