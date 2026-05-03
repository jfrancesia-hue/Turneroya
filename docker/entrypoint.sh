#!/bin/sh
set -e

# Render/Railway inyectan $PORT en runtime; lo sustituimos en nginx.conf
: "${PORT:=10000}"
envsubst '${PORT}' < /etc/nginx/nginx.conf > /tmp/nginx.conf

# Asegurar permisos sobre storage (por si el volumen se creó vacío)
mkdir -p /var/www/html/storage/logs /var/www/html/storage/sessions /var/www/html/storage/uploads
chown -R www-data:www-data /var/www/html/storage

# Migraciones automáticas en cada deploy (idempotente: CREATE TABLE IF NOT EXISTS)
if [ -n "${DB_HOST}" ] && [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "[entrypoint] Corriendo migraciones..."
    php /var/www/html/scripts/migrate.php
fi

# Arranca supervisord → nginx + php-fpm
exec supervisord -c /etc/supervisord.conf
