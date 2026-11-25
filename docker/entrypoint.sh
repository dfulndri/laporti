#!/bin/sh
set -e
APP_ROOT="${APP_ROOT:-/var/www/html}"
cd "$APP_ROOT"

if [ ! -f hesk_settings.inc.php ]; then
  if [ -f hesk_settings.inc.php.example ]; then
    php /opt/laporti/render-settings.php
  else
    echo "[laporti] PERINGATAN: hesk_settings.inc.php dan .example sama-sama tidak ada"
  fi
fi

mkdir -p cache attachments/temp
chmod -R 777 cache attachments 2>/dev/null || true
if [ -f hesk_settings.inc.php ]; then chmod 666 hesk_settings.inc.php 2>/dev/null || true; fi

exec docker-php-entrypoint "$@"
