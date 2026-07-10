#!/usr/bin/env bash
# Installer PHP 8.5 + Composer untuk project PT Dimas Love Medika
set -e

echo "==> Update apt & install PHP 8.5 + ekstensi..."
sudo apt-get update
sudo apt-get install -y \
  php8.5-cli php8.5-mbstring php8.5-xml php8.5-mysql \
  php8.5-gd php8.5-zip php8.5-bcmath php8.5-curl php8.5-intl unzip

echo "==> Install Composer..."
if ! command -v composer >/dev/null 2>&1; then
  curl -sS https://getcomposer.org/installer | php
  sudo mv composer.phar /usr/local/bin/composer
  sudo chmod +x /usr/local/bin/composer
fi

echo ""
echo "==> Selesai. Versi terpasang:"
php -v | head -1
composer --version | head -1
