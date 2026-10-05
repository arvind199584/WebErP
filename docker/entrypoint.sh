#!/bin/bash
set -e

# Ensure sessions and storage directories exist and are writable
mkdir -p /var/www/html/sessions_new \
         /var/www/html/storage/sessions \
         /var/www/html/storage/backups \
         /var/www/html/storage/templates \
         /var/www/html/logs

chmod -R 777 /var/www/html/sessions_new /var/www/html/storage /var/www/html/logs 2>/dev/null || true

# If a custom command was passed to docker run, execute that instead
if [ "$1" != "supervisord" ] && [ -n "$1" ]; then
    exec "$@"
fi

# Run supervisord in foreground
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
