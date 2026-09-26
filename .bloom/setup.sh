#!/usr/bin/env bash
# .bloom/setup.sh
set -euo pipefail

# Keep in sync with archive.sh
SITE="mfm-$(printf '%s' "$BLOOM_WORKSPACE_ID" | tr -cd '[:alnum:]' | tr '[:upper:]' '[:lower:]' | cut -c1-10)"

cp "$BLOOM_ROOT_PATH/.env" .env
sed -i '' "s|^APP_URL=.*|APP_URL=https://$SITE.test|" .env
# Only needed if your root .env sets SESSION_DOMAIN:
# sed -i '' "s|^SESSION_DOMAIN=.*|SESSION_DOMAIN=$SITE.test|" .env

herd link "$SITE"
herd secure "$SITE"
echo "https://$SITE.test" > "$BLOOM_URL_FILE"

[ -f database/database.sqlite ] || touch database/database.sqlite

composer install --quiet
npm ci --quiet
npm run build

php artisan migrate --seed --force
