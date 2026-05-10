# Smart Garden IoT Dashboard

Project Laravel untuk monitoring suhu, kelembaban, dan kontrol relay ESP8266/NodeMCU. Aplikasi ini sudah berisi login admin, dashboard Bootstrap, grafik Chart.js 24 jam terakhir, toggle Lampu 1-3 dan Pompa, serta endpoint API untuk perangkat IoT.

## Fitur

- Login admin Laravel session.
- Dashboard realtime polling setiap 5 detik.
- Grafik suhu dan kelembaban 24 jam terakhir.
- Toggle AJAX untuk `lampu1`, `lampu2`, `lampu3`, dan `pompa`.
- Jadwal otomatis pompa seperti alarm: pilih hari, jam mulai, durasi, aktif/nonaktif, dan hapus jadwal.
- API ESP8266:
  - `POST /api/iot/sensor`
  - `GET /api/iot/control`
- Seeder admin dan default device control.
- Siap deploy ke VPS dengan Nginx, PHP-FPM, MySQL, Composer, dan Vite build.

## Setup Local

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
npm install
npm run dev
php artisan serve
```

Login default dari `.env.example`:

```text
Email: admin@example.com
Password: password
```

Ganti `ADMIN_EMAIL` dan `ADMIN_PASSWORD` sebelum deploy production.

## Endpoint IoT

Jalankan dummy sensor realtime untuk testing dashboard:

```bash
./scripts/dummy-sensor.sh
```

Contoh kirim 30 data setiap 2 detik:

```bash
./scripts/dummy-sensor.sh --interval 2 --runs 30
```

Untuk test ke VPS:

```bash
API_URL=https://domain-kamu.com/api/iot/sensor IOT_API_TOKEN=token-rahasia ./scripts/dummy-sensor.sh
```

Kirim data sensor:

```bash
curl -X POST https://domain-kamu.com/api/iot/sensor \
  -H "Content-Type: application/json" \
  -H "X-IOT-TOKEN: isi-token-jika-dipakai" \
  -d '{"suhu": 28.5, "kelembaban": 72.4}'
```

Ambil status kontrol:

```bash
curl https://domain-kamu.com/api/iot/control \
  -H "X-IOT-TOKEN: isi-token-jika-dipakai"
```

Response kontrol:

```json
{
  "lampu1": 1,
  "lampu2": 0,
  "lampu3": 0,
  "pompa": 1
}
```

Nilai `pompa` otomatis menjadi `1` jika tombol manual pompa ON atau ada jadwal pompa yang sedang aktif. Jadi ESP8266 tetap cukup polling endpoint `GET /api/iot/control`; tidak perlu cron khusus untuk menyalakan pompa.

Jika `IOT_API_TOKEN` dikosongkan, API IoT dapat diakses tanpa token. Untuk VPS publik, sebaiknya isi token dan kirim header `X-IOT-TOKEN` dari ESP8266.

## Deploy VPS

Contoh asumsi:

- Domain: `domain-kamu.com`
- Folder project: `/var/www/smart-garden`
- PHP-FPM: PHP 8.3+
- Database: MySQL/MariaDB

1. Upload atau clone project ke VPS.

```bash
cd /var/www/smart-garden
composer install --no-dev --optimize-autoloader
npm install
npm run build
cp .env.example .env
php artisan key:generate
```

2. Edit `.env`.

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-kamu.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smart_garden
DB_USERNAME=smart_garden
DB_PASSWORD=password_database

ADMIN_EMAIL=admin@domain-kamu.com
ADMIN_PASSWORD=password-admin-yang-kuat
IOT_API_TOKEN=token-rahasia-untuk-nodemcu
```

3. Jalankan migrasi dan cache production.

```bash
php artisan migrate --seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

4. Contoh konfigurasi Nginx.

```nginx
server {
    listen 80;
    server_name domain-kamu.com;
    root /var/www/smart-garden/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Aktifkan site dan reload Nginx:

```bash
ln -s /etc/nginx/sites-available/smart-garden /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

Tambahkan SSL dengan Certbot sesuai domain VPS kamu.

## Catatan ESP8266

- Relay active low tetap memakai nilai Laravel `1` untuk ON dan `0` untuk OFF.
- ESP8266 membaca `/api/iot/control`, lalu mapping nilai `1` menjadi pin relay aktif sesuai rangkaian active low.
- ESP8266 mengirim suhu/kelembaban ke `/api/iot/sensor` dalam JSON.
- Tombol manual pompa dan jadwal otomatis digabung di response API: jika salah satunya ON, nilai `pompa` adalah `1`.
