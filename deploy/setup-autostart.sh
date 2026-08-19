#!/usr/bin/env bash
# Pasang DLM Medika di port 3001 via nginx + php8.4-fpm, otomatis jalan saat boot.
# Jalankan: sudo bash /opt/DLM/deploy/setup-autostart.sh
set -euo pipefail

APP=/opt/DLM

echo "==> 0. Hentikan dev server 'artisan serve' bila masih memakai port 3001"
pkill -f "artisan serve .*--port=3001" || true

echo "==> 1. Izin folder untuk www-data (php-fpm)"
chgrp -R www-data "$APP/storage" "$APP/bootstrap/cache"
chmod -R g+rwX     "$APP/storage" "$APP/bootstrap/cache"
find "$APP/storage" "$APP/bootstrap/cache" -type d -exec chmod g+s {} \;

echo "==> 2. Pasang konfigurasi nginx"
ln -sfn "$APP/deploy/nginx-dlm.conf" /etc/nginx/sites-available/dlm
ln -sfn /etc/nginx/sites-available/dlm /etc/nginx/sites-enabled/dlm
nginx -t

echo "==> 3. Aktifkan service (nginx, php-fpm, mysql) supaya jalan saat boot"
systemctl enable --now php8.4-fpm nginx
systemctl enable mysql || systemctl enable mariadb || true
systemctl reload nginx

echo "==> 4. Cek"
sleep 1
curl -s -o /dev/null -w "HTTP %{http_code}\n" http://127.0.0.1:3001/
echo "Selesai. Buka http://<ip-server>:3001"
