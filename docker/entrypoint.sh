#!/bin/sh
set -e

cd /var/www/html

# Buat folder storage dan cache jika belum ada
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache bootstrap/cache

# Salin .env dari .env.example jika belum ada
if [ ! -f .env ] && [ -f .env.example ]; then
  echo "Creating .env file from .env.example..."
  cp .env.example .env
fi

# Install dependencies jika vendor belum ada (terutama untuk development)
if [ ! -f vendor/autoload.php ]; then
  echo "Installing composer dependencies..."
  composer install --prefer-dist --no-interaction --no-progress
fi

# Generate APP_KEY hanya jika belum diset di .env
if [ -f artisan ] && [ -f .env ]; then
  if ! grep -q "^APP_KEY=base64:" .env && ! grep -q "^APP_KEY=.\+$" .env; then
    echo "Generating application key..."
    php artisan key:generate --force
  else
    echo "Application key is already set."
  fi
fi

# Set permissions untuk storage & bootstrap cache agar writable oleh php-fpm
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

exec "$@"
