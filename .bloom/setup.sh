#!/usr/bin/env bash

# Stop on errors or unset variables.
set -eu

# Bloom provides the original checkout path and this workspace's ID.
# Start with the original project's environment settings.
cp "$BLOOM_ROOT_PATH/.env" .env

# Give this workspace its own HTTPS .test domain with Laravel Valet.
SITE="my-app-$(printf '%s' "$BLOOM_WORKSPACE_ID" | tr -cd '[:alnum:]' | cut -c1-10)"
herd link "$SITE"
herd secure "$SITE"
sed -i '' "s|^APP_URL=.*|APP_URL=https://$SITE.test|" .env

# Use a separate SQLite database so the main checkout is untouched.
# Only create it if it doesn't exist, so setup can run again.
if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
fi

# Install PHP and JavaScript dependencies, then build the frontend assets.
composer install
npm install
npm run build

# Create the tables and seed the workspace database with initial data.
php artisan migrate --seed --force
