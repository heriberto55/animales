#!/bin/sh
set -e

DATA_DIR="${CMS_DATA_DIR:-/var/www/data}"
SITE_DIR=/var/www/html

mkdir -p "$DATA_DIR/pages" "$DATA_DIR/backups" "$DATA_DIR/sessions" "$DATA_DIR/uploads/cms"

for page in "$SITE_DIR"/*.html; do
  [ -e "$page" ] || continue
  name=$(basename "$page")
  if [ ! -e "$DATA_DIR/pages/$name" ]; then
    cp "$page" "$DATA_DIR/pages/$name"
  fi
  ln -sfn "$DATA_DIR/pages/$name" "$page"
done

chown -R www-data:www-data "$DATA_DIR"
chmod 700 "$DATA_DIR/sessions"

exec "$@"
