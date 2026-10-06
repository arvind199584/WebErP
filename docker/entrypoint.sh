#!/bin/bash
set -e

# Ensure sessions and storage directories exist and are writable
mkdir -p /var/www/html/sessions_new \
         /var/www/html/storage/sessions \
         /var/www/html/storage/backups \
         /var/www/html/storage/templates \
         /var/www/html/logs

chmod -R 777 /var/www/html/sessions_new /var/www/html/storage /var/www/html/logs 2>/dev/null || true

# Dump runtime environment variables into /var/www/html/.env as a reliable fallback
if [ ! -f /var/www/html/.env ]; then
    touch /var/www/html/.env
fi
[ -n "$DATABASE_URL" ] && (grep -q "^DATABASE_URL=" /var/www/html/.env || echo "DATABASE_URL=$DATABASE_URL" >> /var/www/html/.env)
[ -n "$DB_HOST" ] && (grep -q "^DB_HOST=" /var/www/html/.env || echo "DB_HOST=$DB_HOST" >> /var/www/html/.env)
[ -n "$DB_PORT" ] && (grep -q "^DB_PORT=" /var/www/html/.env || echo "DB_PORT=$DB_PORT" >> /var/www/html/.env)
[ -n "$DB_NAME" ] && (grep -q "^DB_NAME=" /var/www/html/.env || echo "DB_NAME=$DB_NAME" >> /var/www/html/.env)
[ -n "$DB_USER" ] && (grep -q "^DB_USER=" /var/www/html/.env || echo "DB_USER=$DB_USER" >> /var/www/html/.env)
[ -n "$DB_PASS" ] && (grep -q "^DB_PASS=" /var/www/html/.env || echo "DB_PASS=$DB_PASS" >> /var/www/html/.env)
[ -n "$DB_SSLMODE" ] && (grep -q "^DB_SSLMODE=" /var/www/html/.env || echo "DB_SSLMODE=$DB_SSLMODE" >> /var/www/html/.env)

# If a custom command was passed to docker run, execute that instead
if [ "$1" != "supervisord" ] && [ -n "$1" ]; then
    exec "$@"
fi

# Run supervisord in foreground
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
