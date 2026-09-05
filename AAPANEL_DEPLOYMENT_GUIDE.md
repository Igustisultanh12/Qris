# Panduan Lengkap Upload & Deployment ke aaPanel
## Qmis - PT Kreatif Sky Abadi QRIS Platform

Dokumen ini adalah panduan teknis langkah demi langkah (*step-by-step*) untuk melakukan proses deployment, instalasi, dan konfigurasi produksi **Qmis (PT Kreatif Sky Abadi)** pada server VPS Linux yang menggunakan control panel **aaPanel**.

---

## 1. Spesifikasi Server & Prasyarat Sistem

| Komponen | Rekomendasi Minimum | Rekomendasi Optimal Produksi |
|---|---|---|
| **Sistem Operasi** | Ubuntu 22.04 LTS / Debian 12 | Ubuntu 24.04 LTS |
| **CPU Core** | 1 vCPU | 2 - 4 vCPU |
| **RAM** | 2 GB | 4 - 8 GB |
| **Penyimpanan (Disk)** | 20 GB SSD | 40+ GB NVMe SSD |
| **Control Panel** | aaPanel v6.8.x atau lebih baru | aaPanel versi terbaru |

---

## 2. Langkah 1: Instalasi Software Stack di aaPanel App Store

Setelah berhasil masuk ke dashboard aaPanel Anda (`http://IP-SERVER:7800`):

Buka menu **App Store** di sidebar kiri, kemudian pasang (*install*) aplikasi-aplikasi berikut menggunakan metode **Fast (RPM/DEB)** atau **Compiled**:

1. **Nginx:** Versi `1.24` atau `1.26` (Web Server)
2. **PHP:** Versi `8.3` (Runtime Backend Laravel 12)
3. **MySQL:** Versi `8.0` (Database Relasional)
4. **Redis:** Versi `7.0` (Cache, Session, dan Queue Worker)
5. **Node.js Version Manager:** Install Node.js `v20.x` LTS (Untuk kompilasi frontend Vite)
6. **Supervisor (Process Manager):** Untuk menjalankan antrean worker webhook & email

---

## 3. Langkah 2: Konfigurasi Ekstensi & Fungsi PHP 8.3 di aaPanel

> [!IMPORTANT]
> Secara default, aaPanel mematikan beberapa fungsi PHP (`disable_functions`) yang dibutuhkan oleh Laravel (seperti `proc_open` dan `putenv`). Jika tidak diatur, instalasi Composer dan eksekusi background worker akan gagal.

### A. Mengaktifkan Ekstensi PHP 8.3
1. Buka menu **App Store** > cari **PHP 8.3** > klik tombol **Setting**.
2. Masuk ke tab **Install extensions**.
3. Pasang ekstensi berikut:
   - `fileinfo` (Wajib untuk validasi file dan deteksi QR image)
   - `redis` (Wajib untuk performa antrean dan caching)
   - `opcache` (Mengoptimalkan kecepatan kompilasi PHP)
   - `exif` (Membaca metadata gambar QRIS)
   - `intl` (Untuk format mata uang Rupiah dan tanggal)
   - `bcmath` (Perhitungan floating point akurat CRC16 & fee)

### B. Menghapus Fungsi yang Dibatasi (Disabled Functions)
1. Pada jendela **PHP 8.3 Setting**, klik tab **Disabled functions**.
2. Cari dan **HAPUS (Delete)** fungsi-fungsi berikut dari daftar:
   - `putenv`
   - `proc_open`
   - `proc_get_status`
   - `pcntl_signal`
   - `pcntl_alarm`
   - `symlink`
3. Klik tombol **Restart** pada tab *Service* PHP 8.3 agar perubahan diterapkan.

### C. Konfigurasi Batas Memori & Upload
Pada tab **Configuration**, sesuaikan nilai berikut:
```ini
max_execution_time = 300
memory_limit = 512M
post_max_size = 50M
upload_max_filesize = 50M
```
Klik **Save** lalu restart PHP 8.3.

---

## 4. Langkah 3: Membuat Database MySQL di aaPanel

1. Buka menu **Databases** di sidebar aaPanel.
2. Klik tombol **Add Database**:
   - **DB Name:** `qris_platform`
   - **DB User:** `qris_user`
   - **Password:** *(Gunakan password acak yang kuat, misal: `KreatifQris2026!#Db`)*
   - **Character Set:** `utf8mb4`
   - **Access Permission:** `Local server` (127.0.0.1)
3. Klik **Submit**. Simpan nama database, username, dan password tersebut untuk konfigurasi file `.env`.

---

## 5. Langkah 4: Menambahkan Website di aaPanel

1. Masuk ke menu **Website** di sidebar aaPanel.
2. Klik tombol **Add site**:
   - **Domain:** Masukkan domain atau subdomain Anda (misal: `qris.domainanda.com`).
   - **Description:** `Qmis - PT Kreatif Sky Abadi QRIS Platform`
   - **Root directory:** `/www/wwwroot/qris-platform`
   - **FTP:** *Do not create*
   - **Database:** *Do not create* (sudah dibuat pada langkah 3)
   - **PHP Version:** Pilih `PHP-83`
3. Klik **Submit**.

---

## 6. Langkah 5: Upload Kode Program ke Server

Terdapat dua metode upload. **Metode A (Git Clone)** adalah metode yang paling direkomendasikan.

### Metode A: Menggunakan Terminal aaPanel / SSH (Direkomendasikan)
1. Buka menu **Terminal** di aaPanel atau login via SSH client (PuTTY/Termius):
2. Masuk ke direktori web dan clone repositori:
   ```bash
   cd /www/wwwroot
   # Hapus folder default yang dibuat aaPanel jika masih kosong
   rm -rf qris-platform
   # Clone dari GitHub
   git clone https://github.com/Igustisultanh12/Qris.git qris-platform
   cd qris-platform
   ```

### Metode B: Menggunakan File Manager aaPanel
1. Unduh kode sumber dari GitHub dalam format ZIP.
2. Buka menu **Files** di aaPanel, navigasikan ke folder `/www/wwwroot/`.
3. Klik **Upload**, pilih file ZIP repositori, lalu ekstrak ke dalam folder `/www/wwwroot/qris-platform`.

---

## 7. Langkah 6: Konfigurasi Site Directory & Nginx Pseudo-Static

### A. Mengubah Running Directory ke `/public`
> [!WARNING]
> Laravel mewajibkan root web mengarah ke subfolder `/public`. Jangan arahkan root ke direktori utama agar file `.env` dan kode aplikasi aman dari akses publik!

1. Buka menu **Website** > klik nama domain Anda (`qris.domainanda.com`).
2. Masuk ke tab **Site directory**:
   - **Site directory:** `/www/wwwroot/qris-platform`
   - **Running directory:** Pilih `/public` pada dropdown.
3. Klik **Save**.

### B. Konfigurasi Nginx URL Rewrite (Pseudo-Static)
Agar routing Single Page Application (Vue Router) dan REST API Laravel berfungsi saat di-refresh tanpa error 404:

1. Pada jendela pengaturan domain yang sama, klik tab **URL rewrite**.
2. Pilih template **Laravel 5** dari dropdown, atau paste konfigurasi berikut:
   ```nginx
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }

   location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
       expires 30d;
       add_header Cache-Control "public, no-transform";
   }
   ```
3. Klik **Save**.

---

## 8. Langkah 7: Konfigurasi File Lingkungan (.env)

Masuk ke terminal server di direktori project:
```bash
cd /www/wwwroot/qris-platform
cp .env.example .env
```

Buka file `.env` menggunakan File Manager aaPanel atau `nano .env`, lalu sesuaikan parameter berikut:

```ini
APP_NAME="Qmis - PT Kreatif Sky Abadi"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://qris.domainanda.com

LOG_CHANNEL=stack
LOG_LEVEL=error

# Konfigurasi Database MySQL aaPanel
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=qris_platform
DB_USERNAME=qris_user
DB_PASSWORD=password_db_yang_anda_buat

# Konfigurasi Cache, Session, & Queue dengan Redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Email Gateway (Dapat juga dikonfigurasi melalui UI Super Admin)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@domainanda.com
MAIL_PASSWORD=password_smtp_anda
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@domainanda.com
MAIL_FROM_NAME="Qmis - PT Kreatif Sky Abadi"
```

Simpan perubahan file `.env`.

---

## 9. Langkah 8: Install Dependensi & Migrasi Database

Jalankan perintah berikut di Terminal server:

```bash
cd /www/wwwroot/qris-platform

# 1. Generate Application Encryption Key
php artisan key:generate

# 2. Install Dependensi PHP (Optimasi Produksi)
composer install --no-dev --optimize-autoloader

# 3. Jalankan Migrasi Database & Seeder Bawaan
php artisan migrate:fresh --seed --force

# 4. Buat Symlink Storage Publik
php artisan storage:link

# 5. Build Aset Frontend Vue 3 (Jika belum ter-build)
npm install
npm run build

# 6. Optimasi Cache Konfigurasi & Route Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 10. Langkah 9: Atur Permission & Hak Milik File

Pastikan user sistem `www:www` (user standar Nginx/PHP-FPM di aaPanel) memiliki hak akses penuh ke folder storage dan cache:

```bash
cd /www/wwwroot/qris-platform
chown -R www:www .
chmod -R 775 storage bootstrap/cache
```

---

## 11. Langkah 10: Pemasangan Sertifikat SSL Gratis (Let's Encrypt)

1. Buka menu **Website** di aaPanel > klik nama domain Anda.
2. Masuk ke tab **SSL**.
3. Pilih opsi **Let's Encrypt**:
   - Pilih nama domain Anda.
   - Centang opsi persetujuan Terms.
   - Klik tombol **Apply**.
4. Setelah sertifikat terbit, aktifkan saklar **Force HTTPS** di pojok kanan atas untuk memastikan seluruh transaksi dan pemanggilan API terlindungi dengan enkripsi TLS 1.3.

---

## 12. Langkah 11: Konfigurasi Background Queue Worker (Supervisor)

Queue worker diperlukan agar pengiriman Webhook ber-tanda tangan HMAC-SHA256, pengiriman email via Email Gateway, dan pemrosesan otomatis lainnya berjalan di background tanpa memperlambat respons API.

1. Buka menu **App Store** > cari **Supervisor Manager** > klik **Setting**.
2. Klik tombol **Add Process**:
   - **Name:** `qris-worker`
   - **Run User:** `www`
   - **Run Dir:** `/www/wwwroot/qris-platform`
   - **Command:** `/usr/bin/php /www/wwwroot/qris-platform/artisan queue:work --sleep=3 --tries=3 --max-time=3600`
   - **Processes:** `2`
3. Klik **Confirm**.
4. Pastikan status process menunjukkan status **Running** (ikon hijau).

---

## 13. Langkah 12: Konfigurasi Task Scheduler (Cron Job)

Laravel Task Scheduler bertugas menjalankan pembatalan transaksi kadaluarsa (`ExpireTransactionsJob`) setiap menit dan pengecekan perpanjangan langganan SaaS.

1. Buka menu **Cron** di sidebar utama aaPanel.
2. Isi formulir pembuatan cron job:
   - **Type of Task:** `Shell Script`
   - **Name of Task:** `Qmis Task Scheduler`
   - **Period:** Pilih `Every 1 Minute` (N Minutes: 1)
   - **Script Content:**
     ```bash
     /usr/bin/php /www/wwwroot/qris-platform/artisan schedule:run >> /dev/null 2>&1
     ```
3. Klik **Add task**.

---

## 14. Langkah 13: Konfigurasi & Pengujian Sistem Email Gateway

Platform dilengkapi fitur **Email Gateway System** yang dapat diatur dan diuji langsung melalui antarmuka web:

1. Buka browser dan akses URL domain Anda: `https://qris.domainanda.com/login`
2. Masuk menggunakan akun **Super Admin**:
   - **Email:** `admin@kreatifskyabadi.co.id`
   - **Password:** `KreatifSkyAbadi2026!`
3. Pada sidebar menu, klik **Email Gateway & SMTP**.
4. Masukkan konfigurasi SMTP provider Anda:
   - **Preset Cepat:** Klik tombol preset yang tersedia (*Gmail, Mailgun, Brevo, atau aaPanel Local Webmail*).
   - **Host:** misal `smtp.mailgun.org` atau `mail.domainanda.com`
   - **Port:** `587` (TLS) atau `465` (SSL)
   - **Username & Password:** Akun SMTP Anda
   - **From Address:** Alamat email resmi Anda (misal: `billing@domainanda.com`)
   - **From Name:** `Qmis - PT Kreatif Sky Abadi`
5. Klik **Simpan Konfigurasi**.
6. **Uji Pengiriman Email (Live Test Ping):**
   - Pada kolom *Kirim Ke Email*, ketikkan alamat email pribadi Anda.
   - Klik **Kirim Test Email Sekarang**.
   - Sistem akan menampilkan status pengiriman real-time beserta kecepatan respon (*latency dalam milidetik*).

---

## 15. Troubleshooting Masalah Populer di aaPanel

| Gejala Masalah | Penyebab Utama | Solusi |
|---|---|---|
| **Error 500 Internal Server Error** | File `.env` belum dibuat atau `APP_KEY` kosong | Jalankan `cp .env.example .env` dan `php artisan key:generate`. Cek log di `storage/logs/laravel.log`. |
| **Error 404 Not Found saat klik menu / refresh halaman Vue** | Nginx rewrite belum mengarah ke `index.php` | Buka Website > URL Rewrite > masukkan rule `try_files $uri $uri/ /index.php?$query_string;`. |
| **Error `proc_open() has been disabled for security reasons`** | aaPanel mematikan fungsi process di PHP | Buka App Store > PHP 8.3 > Disabled functions > hapus `proc_open` & `putenv`, lalu restart PHP. |
| **Permission Denied pada storage/logs** | Kepemilikan file masih milik root | Jalankan perintah `chown -R www:www /www/wwwroot/qris-platform` dan `chmod -R 775 storage bootstrap/cache`. |
| **Redis Connection Refused (Port 6379)** | Layanan Redis belum berjalan di aaPanel | Buka App Store > Redis > klik Start/Restart. |
| **Kompilasi Vite `npm run build` gagal kehabisan memori** | RAM server kurang dari 2GB | Buat swap file via aaPanel (App Store > Linux Tools > Add Swap 2GB) atau lakukan build di komputer lokal lalu upload folder `public/build`. |

---

*PT Kreatif Sky Abadi — Dokumentasi Resmi Deployment aaPanel.*
