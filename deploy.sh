#!/usr/bin/env bash
set -euo pipefail
cd /var/www/bidacpf

echo "→ Pulling latest code"
git fetch origin main
git reset --hard origin/main

echo "→ PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

echo "→ Migrations"
php artisan migrate --force

echo "→ Rebuilding caches"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "→ Restarting queue workers"
php artisan queue:restart

echo "✓ Deploy complete"