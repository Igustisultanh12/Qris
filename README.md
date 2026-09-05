# Qmis - PT Kreatif Sky Abadi QRIS Platform

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Vue 3](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=for-the-badge&logo=vuedotjs&logoColor=white)](https://vuejs.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-4.x-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![ASPI QRIS](https://img.shields.io/badge/Standard-ASPI%20%2F%20EMVCo-blue?style=for-the-badge)](https://aspi-indonesia.or.id)
[![License](https://img.shields.io/badge/License-Proprietary-red?style=for-the-badge)]()

> **Production-Ready SaaS Platform** untuk konversi QRIS Statis ke Dinamis dengan nominal transaksi dan biaya fleksibel, dilengkapi Versioned REST API (`/api/v1`), Multi-Tenant Logical Isolation, Sistem Penagihan & Langganan SaaS, Webhook HMAC-SHA256, 2FA TOTP, serta Portal Web Interaktif untuk Merchant dan Super Admin.

---

## Daftar Isi
1. [Latar Belakang & Spesifikasi Standar](#1-latar-belakang--spesifikasi-standar)
2. [Arsitektur Algoritma QRIS Engine](#2-arsitektur-algoritma-qris-engine)
3. [Fitur Utama Sistem](#3-fitur-utama-sistem)
4. [Struktur Folder Proyek](#4-struktur-folder-proyek)
5. [Panduan Instalasi & Menjalankan Sistem](#5-panduan-instalasi--menjalankan-sistem)
   - [A. Menggunakan Docker Compose (Direkomendasikan)](#a-menggunakan-docker-compose)
   - [B. Instalasi Lokal (Manual)](#b-instalasi-lokal-manual)
   - [C. Panduan Lengkap Upload & Deployment ke aaPanel](#c-panduan-lengkap-upload--deployment-ke-aapanel)
6. [Sistem Email Gateway & SMTP Relay](#6-sistem-email-gateway--smtp-relay)
7. [Kredensial Bawaan (Seeders)](#7-kredensial-bawaan-seeders)
8. [Dokumentasi REST API v1 & Contoh cURL](#8-dokumentasi-rest-api-v1--contoh-curl)
9. [Panduan Portal Web](#9-panduan-portal-web)
10. [Pengujian Otomatis (Automated Tests)](#10-pengujian-otomatis-automated-tests)
11. [Operasional, Backup & Keamanan](#11-operasional-backup--keamanan)

---

## 1. Latar Belakang & Spesifikasi Standar

Banyak merchant UMKM, restoran, toko ritel, dan bisnis digital telah memiliki stiker QRIS Statis dari berbagai bank penerbit (BCA, Mandiri, BRI, BNI, CIMB) atau PJP e-wallet (GoPay, OVO, ShopeePay, DANA, LinkAja). Namun, QRIS statis memiliki keterbatasan:
- Pelanggan harus mengetikkan nominal secara manual di aplikasi mobile banking.
- Rawan kesalahan pengetikan nominal (*human error*).
- Kasir harus mengecek mutasi secara manual.
- Sulit diintegrasikan ke sistem kasir POS, vending machine, website checkout, atau aplikasi mobile.

**Qmis (PT Kreatif Sky Abadi)** memecahkan masalah ini dengan membaca dan mengurai QRIS statis milik merchant, lalu mengubahnya secara deterministik menjadi **QRIS Dinamis** yang langsung memuat nominal transaksi dan biaya layanan tertentu.

Platform ini sepenuhnya patuh pada:
- **Peraturan Anggota Dewan Gubernur Bank Indonesia (PADG BI) No. 21/18/PADG/2019** tentang Implementasi Standar Nasional Quick Response Code untuk Pembayaran.
- **Standar Teknis ASPI (Asosiasi Sistem Pembayaran Indonesia)** untuk QRIS Merchant-Presented Mode (MPM).
- **EMVCo QR Code Specification for Payment Systems (Merchant-Presented Mode)**.

---

## 2. Arsitektur Algoritma QRIS Engine

Dibangun secara *in-house* dengan kepatuhan penuh terhadap regulasi Bank Indonesia (PADG No. 21/18/PADG/2019) dan spesifikasi standar EMVCo QR Code Merchant-Presented Mode (MPM), modul QRIS engine berkinerja tinggi di `app/Services/Qris/` mengimplementasikan:

```
+------------------+      +-------------------+      +----------------------+
|   QRIS Statis    | ---> |    TLV Parser     | ---> |    QrisValidator     |
| (Tag 01 = '11')  |      |  Recursive Decon  |      | Mandatory Tags & CRC |
+------------------+      +-------------------+      +----------------------+
                                                                |
                                                                v
+------------------+      +-------------------+      +----------------------+
|  Dynamic QR Code | <--- |  CRC16-CCITT Calc | <--- |    QrisConverter     |
| SVG / PNG Render |      |  Poly: 0x1021     |      | Tag 01='12' + Amt/Fee|
+------------------+      +-------------------+      +----------------------+
```

### Tag EMVCo yang Dimanipulasi
1. **Tag 01 (Point of Initiation Method):** Diubah dari `11` (Static QR) menjadi `12` (Dynamic QR).
2. **Tag 54 (Transaction Amount):** Disuntikkan dengan nilai total transaksi (misal: `50000` atau `15500.00`).
3. **Tag 55 (Tip or Convenience Indicator):**
   - Nilai `02` jika mengenakan Fixed Fee.
   - Nilai `03` jika mengenakan Percentage Fee.
4. **Tag 56 (Value of Convenience Fee Fixed):** Berisi nominal rupiah biaya tetap (misal: `1000`).
5. **Tag 57 (Value of Convenience Fee Percentage):** Berisi nilai persentase fee (misal: `0.7` untuk 0.7%).
6. **Tag 63 (CRC16-CCITT):** Dihitung ulang secara akurat menggunakan polinomial `0x1021` dengan inisialisasi `0xFFFF`.

---

## 3. Fitur Utama Sistem

### A. Konversi & Generator QRIS
- **TLV Parser & Builder:** Mendukung sub-tags nested (Tag 26-51 untuk Merchant Account Information dan Tag 62 untuk Additional Data).
- **Validasi Ketat:** Memastikan Tag 00 bernilai `01`, ada minimal satu Tag 26-51, Tag 58=`ID`, Tag 53=`360`, dan integritas CRC asli.
- **Rendering Resolusi Tinggi:** Menghasilkan vektor SVG murni dan PNG Data URI (base64) melalui library `bacon/bacon-qr-code`.
- **Scanner Kamera Terpadu:** Pindai kode QR fisik via webcam atau upload gambar menggunakan library `jsqr` secara langsung di browser tanpa upload ke server luar.

### B. Arsitektur Multi-Tenant SaaS
- **Isolasi Logis Tenant:** Setiap `Customer` memiliki isolasi data terhadap `merchants`, `transactions`, `api_keys`, `webhooks`, dan `invoices` menggunakan Eloquent Global Scope `BelongsToCustomer`.
- **Paket Langganan & Kuota:** Tier Basic, Pro, dan Enterprise yang membatasi jumlah merchant yang dapat didaftarkan dan batas *Rate Limit*.
- **Masa Percobaan (Trial) & Grace Period:** Akun baru otomatis mendapatkan 14 hari masa uji coba gratis.
- **Penagihan & Faktur Pajak (PPN 11%):** Otomatisasi penerbitan faktur langganan dengan dukungan berbagai gateway:
  - Transfer Bank Manual (BCA, Mandiri, BRI)
  - Midtrans Gateway (Snap, QRIS, Virtual Account)
  - Xendit Gateway (VA & E-Wallet)
  - Tripay Gateway (Retail Alfamart/Indomaret & VA)

### C. Keamanan & REST API Platform
- **API Key & Secret Authentication:** Kunci API diverifikasi menggunakan hashing SHA-256 (`ka_live_...` & `kas_...`). Secret Key hanya ditampilkan **satu kali** saat pembuatan.
- **Idempotency Guard:** Header `Idempotency-Key` mencegah transaksi ganda jika terjadi kegagalan jaringan atau request duplikat.
- **Tiered Rate Limiting:** Pembatasan request per menit dengan standar response header `X-RateLimit-*` dan `Retry-After`.
- **Signed Webhooks:** Pengiriman HTTP POST real-time dengan tanda tangan digital `X-Signature-SHA256: hash_hmac('sha256', payload, secret)` dan antrean retry bertingkat (*exponential backoff*).
- **Two-Factor Authentication (2FA):** Kompatibel dengan Google Authenticator / Authy menggunakan algoritma RFC 6238 TOTP.
- **Audit Logging:** Setiap mutasi data penting (login, ganti kuota, suspend akun, pembaruan setting) dicatat forensik di tabel `audit_logs`.

---

## 4. Struktur Folder Proyek

```
qris-platform/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/              # Controller Super Admin
│   │   │   ├── Api/                # Controller REST API v1
│   │   │   ├── Auth/               # Autentikasi & 2FA
│   │   │   └── Customer/           # Controller Web Portal Merchant
│   │   ├── Middleware/             # ApiKeyAuthenticate, ApiRateLimiter, ApiLogger, AssignRequestId
│   │   └── Responses/              # ApiResponse helper seragam
│   ├── Jobs/                       # ExpireTransactionsJob, DispatchWebhookJob
│   ├── Models/                     # User, Customer, Merchant, Transaction, Invoice, ApiKey, Webhook, dll.
│   ├── Services/
│   │   ├── Billing/                # BillingService, RefundService
│   │   ├── Gateway/                # PaymentGatewayInterface, Midtrans, Xendit, Tripay, Manual
│   │   ├── Qris/                   # QRIS Engine (TlvParser, QrisConverter, Crc16, QrisGenerator, dll)
│   │   └── Transaction/            # TransactionService
│   └── Traits/                     # BelongsToCustomer, HasUuid
├── database/
│   ├── migrations/                 # 13 migrasi database lengkap
│   └── seeders/                    # Role, Setting, Plan, Admin, DemoCustomer
├── resources/
│   ├── css/                        # Tailwind CSS v4 setup & typography
│   ├── js/
│   │   ├── api/                    # Axios client instance
│   │   ├── components/             # CameraScanner, QrCodeViewer, Modal, ToastContainer
│   │   ├── layouts/                # PublicLayout, DashboardLayout, AdminLayout
│   │   ├── router/                 # Vue Router dengan navigation guards
│   │   ├── stores/                 # Pinia stores (auth, theme, toast)
│   │   └── views/                  # Public, Auth, Customer, dan Admin views
│   └── views/                      # app.blade.php (SPA shell)
├── routes/
│   ├── api.php                     # 70 endpoint terdaftar (v1, customer, admin, auth)
│   ├── web.php                     # SPA fallback route
│   └── console.php                 # Scheduled background jobs
├── tests/
│   ├── Feature/                    # ApiPlatformTest (REST API integration tests)
│   └── Unit/                       # QrisEngineTest (Algoritma EMVCo, TLV, CRC16, Fee math)
├── Dockerfile                      # Multi-stage production container
├── docker-compose.yml              # Local orchestration (App, MySQL 8, Redis, Scheduler)
└── public/docs/openapi.json        # Spesifikasi OpenAPI 3.0
```

---

## 5. Panduan Instalasi & Menjalankan Sistem

### A. Menggunakan Docker Compose (Direkomendasikan)

Pastikan Docker dan Docker Compose telah terpasang di sistem Anda.

1. **Clone repositori:**
   ```bash
   git clone https://github.com/Igustisultanh12/Qris.git
   cd Qris
   ```

2. **Jalankan container dengan Docker Compose:**
   ```bash
   docker-compose up -d --build
   ```

3. **Inisialisasi database dan seeder di dalam container:**
   ```bash
   docker-compose exec app php artisan key:generate
   docker-compose exec app php artisan migrate:fresh --seed
   ```

4. **Akses aplikasi:**
   - Aplikasi Web: [http://localhost:8000](http://localhost:8000)
   - Dokumentasi API: [http://localhost:8000/api-docs](http://localhost:8000/api-docs)

---

### B. Instalasi Lokal (Manual)

**Prasyarat:**
- PHP >= 8.3 dengan ekstensi: `pdo`, `mbstring`, `bcmath`, `gd`, `zip`, `intl`, `sqlite` atau `pdo_mysql`.
- Composer 2.x
- Node.js >= 20.x & npm
- SQLite atau MySQL 8.0

**Langkah-langkah:**

1. **Clone repositori dan masuk ke direktori:**
   ```bash
   git clone https://github.com/Igustisultanh12/Qris.git
   cd Qris
   ```

2. **Salin file konfigurasi lingkungan:**
   ```bash
   cp .env.example .env
   ```

3. **Install dependensi PHP:**
   ```bash
   composer install
   ```

4. **Generate Application Key:**
   ```bash
   php artisan key:generate
   ```

5. **Jalankan migrasi database dan seeders:**
   ```bash
   php artisan migrate:fresh --seed
   ```

6. **Install dependensi frontend dan build assets:**
   ```bash
   npm install
   npm run build
   ```

7. **Jalankan development server:**
   ```bash
   php artisan serve
   ```
   Aplikasi akan berjalan di `http://127.0.0.1:8000`.

8. **(Opsional) Menjalankan Queue Worker & Task Scheduler:**
   ```bash
   # Terminal 1: Queue Worker
   php artisan queue:work

   # Terminal 2: Scheduled Jobs (Expire Transactions & Auto-renewal)
   php artisan schedule:work
   ```

---

### C. Panduan Lengkap Upload & Deployment ke aaPanel

Panduan komprehensif terpisah juga tersedia di: **[`AAPANEL_DEPLOYMENT_GUIDE.md`](./AAPANEL_DEPLOYMENT_GUIDE.md)**.

Berikut adalah ringkasan alur instalasi produksi pada server Linux menggunakan **aaPanel**:

#### 1. Persiapan Software di aaPanel App Store
Masuk ke dashboard aaPanel Anda (`http://IP-SERVER:7800`) > menu **App Store**, lalu pasang:
- **Nginx:** 1.24 atau 1.26
- **PHP:** 8.3
- **MySQL:** 8.0
- **Redis:** 7.0
- **Node.js Version Manager:** Install Node 20.x
- **Supervisor:** Process manager untuk antrean Laravel

#### 2. Konfigurasi PHP 8.3 di aaPanel (Penting!)
- **Install Extensions:** Masuk ke App Store > PHP 8.3 > Setting > *Install extensions* > pasang: `fileinfo`, `redis`, `opcache`, `exif`, `intl`, `bcmath`.
- **Hapus Pembatasan Fungsi (*Disabled Functions*):**
  Masuk ke App Store > PHP 8.3 > Setting > *Disabled functions* > **HAPUS** fungsi-fungsi berikut dari daftar:
  `putenv`, `proc_open`, `proc_get_status`, `pcntl_signal`, `pcntl_alarm`, `symlink`.
  *Lalu restart service PHP 8.3.*

#### 3. Buat Database & Tambahkan Website di aaPanel
1. Masuk ke menu **Databases** > klik **Add Database** > buat DB `qris_platform` dengan user `qris_user` (catat passwordnya).
2. Masuk ke menu **Website** > klik **Add site**:
   - **Domain:** Masukkan domain Anda (misal: `qris.perusahaananda.com`)
   - **Root directory:** `/www/wwwroot/qris-platform`
   - **PHP Version:** `PHP-83`

#### 4. Upload Kode Program (via Git Clone)
Buka menu **Terminal** di aaPanel atau login via SSH:
```bash
cd /www/wwwroot
rm -rf qris-platform
git clone https://github.com/Igustisultanh12/Qris.git qris-platform
cd qris-platform
```

#### 5. Atur Document Root & URL Rewrite Nginx
1. Di menu **Website** aaPanel > klik nama domain Anda > tab **Site directory**:
   - Ganti **Running directory** ke `/public` lalu klik **Save**.
2. Masuk ke tab **URL rewrite**:
   Pilih template **Laravel 5** atau paste rule Nginx berikut:
   ```nginx
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }
   ```
   Klik **Save**.

#### 6. Inisialisasi Environment & Database
Di Terminal server:
```bash
cd /www/wwwroot/qris-platform
cp .env.example .env
# Edit konfigurasi DB & APP_URL di .env:
# nano .env

composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan storage:link
php artisan migrate:fresh --seed --force
npm install && npm run build
chown -R www:www .
chmod -R 775 storage bootstrap/cache
```

#### 7. Pasang SSL Gratis (Let's Encrypt)
Di pengaturan website aaPanel > tab **SSL** > pilih **Let's Encrypt** > klik **Apply** > aktifkan toggle **Force HTTPS**.

#### 8. Setup Supervisor (Queue Worker) & Cron Task
1. Buka App Store > **Supervisor** > Setting > **Add Process**:
   - **Name:** `qris-worker`
   - **Run User:** `www`
   - **Run Dir:** `/www/wwwroot/qris-platform`
   - **Command:** `/usr/bin/php /www/wwwroot/qris-platform/artisan queue:work --sleep=3 --tries=3`
2. Buka menu **Cron** aaPanel > tambahkan task:
   - **Period:** `Every 1 Minute`
   - **Script:** `/usr/bin/php /www/wwwroot/qris-platform/artisan schedule:run >> /dev/null 2>&1`

---

## 6. Sistem Email Gateway & SMTP Relay

Platform dilengkapi dengan **Sistem Email Gateway Dinamis** (arsitektur seperti pada platform *sisfoperskc* / *instagram unfollowers*) yang dapat dikonfigurasi dan diuji secara real-time langsung melalui portal web Super Admin tanpa perlu mengubah file konfigurasi server secara manual:

```
+---------------------+     +-----------------------+     +------------------------+
| Transaksi / Invoice | --> |  EmailGatewayService  | --> | Dynamic SMTP Transport |
|  Pendaftaran Akun   |     |  Template & Branding  |     | Gmail/Mailgun/Brevo/cPanel
+---------------------+     +-----------------------+     +------------------------+
                                        |
                                        v
                            +-----------------------+
                            | Live Test Ping UI     |
                            | Latency & Status Cek  |
                            +-----------------------+
```

### Fitur Email Gateway:
1. **Multi-Driver Support:** Mendukung relay `smtp`, `sendmail`, dan `log` (debug mode).
2. **Kustomisasi Port & Enkripsi:** Fleksibel untuk TLS (Port 587), SSL (Port 465), atau Non-TLS (Port 25).
3. **Preset Populer 1-Klik:** Dilengkapi tombol preset cepat untuk Gmail / G-Suite, Mailgun, Brevo (Sendinblue), dan aaPanel / cPanel Webmail lokal.
4. **Live Test Send (Ping):** Uji coba koneksi SMTP real-time ke email penerima uji coba dengan pelaporan kecepatan respon (*roundtrip latency dalam milidetik*) dan pesan kesalahan diagnostik.
5. **Template Email HTML Responsif:**
   - Resi konfirmasi pembayaran transaksi QRIS dinamis (`TransactionReceiptMailable`).
   - Email selamat datang & kredensial onboarding akun merchant baru (`WelcomeCustomerMailable`).
   - Pemberitahuan penerbitan faktur tagihan langganan SaaS (`InvoiceCreatedMailable`).

Akses menu ini di Portal Super Admin pada: **`/admin/email-gateway`**.

---

## 7. Kredensial Bawaan (Seeders)

Database telah dilengkapi dengan akun bawaan untuk pengujian instan:

### 1. Akun Super Admin
- **Email:** `admin@kreatifskyabadi.co.id`
- **Password:** `KreatifSkyAbadi2026!`
- **Role:** Super Admin (Akses penuh ke `/admin/*`)

### 2. Akun Demo Pelanggan (Merchant)
- **Email:** `demo@example.com`
- **Password:** `password`
- **Role:** Customer Merchant (Akses penuh ke `/dashboard`, `/customer/*`)

### 3. Kunci API Demo (Untuk Pemanggilan REST API v1)
- **X-API-Key:** `ka_live_demo1234567890abcdef12`
- **X-API-Secret:** `kas_demoSecretKey9876543210zyxwvutsrq`

---

## 8. Dokumentasi REST API v1 & Contoh cURL

Seluruh endpoint publik berakar pada `/api/v1/*`. Setiap pemanggilan wajib menyertakan kredensial autentikasi.

### 1. Health Check
Memeriksa status operasional API dan komponen database.

```bash
curl -X GET http://127.0.0.1:8000/api/v1/health \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "success": true,
  "message": "QRIS Platform API is healthy",
  "data": {
    "status": "healthy",
    "version": "1.0.0",
    "timestamp": "2026-09-05T10:15:00Z"
  }
}
```

---

### 2. Validasi & Parsing QRIS Statis
Mengecek apakah string payload valid menurut standar EMVCo dan mengekstrak informasi merchant.

```bash
curl -X POST http://127.0.0.1:8000/api/v1/qris/validate \
  -H "X-API-Key: ka_live_demo1234567890abcdef12" \
  -H "X-API-Secret: kas_demoSecretKey9876543210zyxwvutsrq" \
  -H "Content-Type: application/json" \
  -d '{
    "qris": "00020101021126620014ID.LINKAJA.WWW01189360091100220945610211000000000010303UMI51440014ID.CO.QRIS.WWW0215ID10200210000010303UMI5204541153033605802ID5923KREATIF SKY ABADI STORE6013JAKARTA PUSAT61051011062070703A0163046155"
  }'
```

---

### 3. Membuat QRIS Dinamis (Generate Dynamic QRIS)
Mengonversi QRIS statis merchant menjadi QRIS dinamis dengan nominal rupiah dan biaya tambahan tertentu.

```bash
curl -X POST http://127.0.0.1:8000/api/v1/qris/dynamic \
  -H "X-API-Key: ka_live_demo1234567890abcdef12" \
  -H "X-API-Secret: kas_demoSecretKey9876543210zyxwvutsrq" \
  -H "Idempotency-Key: ORD-INV-202609-001" \
  -H "Content-Type: application/json" \
  -d '{
    "merchant_id": "MC-DEMO-001",
    "amount": 50000,
    "reference": "INV-20260905-001",
    "fee_type": "fixed",
    "fee_value": 1500,
    "fee_mode": "charged_to_customer",
    "expiry_minutes": 15
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "Dynamic QRIS generated successfully",
  "data": {
    "transaction_id": "tx_9d817b12-c288-4f70-a8ce-59fe849ce31c",
    "reference": "INV-20260905-001",
    "merchant": {
      "code": "MC-DEMO-001",
      "name": "Kreatif Sky Abadi Store"
    },
    "amount": 50000,
    "fee_amount": 1500,
    "total_amount": 51500,
    "status": "generated",
    "expires_at": "2026-09-05T10:30:00Z",
    "qris_dynamic": "00020101021226620014ID.LINKAJA.WWW...540551500...6304XXXX",
    "qr_svg": "<svg ...></svg>",
    "qr_png": "data:image/png;base64,iVBORw0KGgoAAAANSUhEU..."
  }
}
```

---

### 4. Cek Status Transaksi QRIS
Mengecek status pembayaran (`generated`, `paid`, `expired`, `cancelled`).

```bash
curl -X GET http://127.0.0.1:8000/api/v1/transactions/INV-20260905-001 \
  -H "X-API-Key: ka_live_demo1234567890abcdef12" \
  -H "X-API-Secret: kas_demoSecretKey9876543210zyxwvutsrq"
```

---

### 5. Membatalkan Transaksi QRIS
Membatalkan transaksi yang belum kadaluarsa sehingga QR tidak dapat dibayar lagi.

```bash
curl -X POST http://127.0.0.1:8000/api/v1/qris/tx_9d817b12-c288-4f70-a8ce-59fe849ce31c/cancel \
  -H "X-API-Key: ka_live_demo1234567890abcdef12" \
  -H "X-API-Secret: kas_demoSecretKey9876543210zyxwvutsrq"
```

---

### 6. Simulasi Pembayaran QRIS (Sandbox / Testing)
Menyimulasikan pelanggan berhasil membayar transaksi QRIS di lingkungan sandbox/development tanpa uang riil. Endpoint ini langsung mengubah status transaksi menjadi `paid`, mengatur timestamp `paid_at`, mengirimkan email struk, dan **otomatis menembakkan Webhook notifikasi (`transaction.paid`)** ke URL webhook merchant Anda.

Dapat dipanggil menggunakan `transaction_id` (UUID) atau kode `reference` pesanan:

```bash
curl -X POST http://127.0.0.1:8000/api/v1/qris/INV-20260905-001/simulate-paid \
  -H "X-API-Key: ka_live_demo1234567890abcdef12" \
  -H "X-API-Secret: kas_demoSecretKey9876543210zyxwvutsrq"
```

**Response:**
```json
{
  "success": true,
  "message": "Payment simulation successful. Transaction marked as paid and webhook dispatched.",
  "data": {
    "transaction_id": "tx_9d817b12-c288-4f70-a8ce-59fe849ce31c",
    "reference": "INV-20260905-001",
    "status": "paid",
    "amount": 50000,
    "fee_amount": 1500,
    "total_amount": 51500,
    "paid_at": "2026-09-05T10:35:12Z"
  }
}
```

---

### 7. Sistem Webhook & Notifikasi Pembayaran Otomatis

Webhook adalah mekanisme pengiriman notifikasi HTTP POST asinkron dari server **Qmis** ke server backend aplikasi Anda saat status transaksi berubah (khususnya saat pembayaran berhasil). Dengan webhook, sistem POS, e-commerce, atau aplikasi kasir Anda dapat **mengubah status pesanan secara otomatis menjadi PAID** tanpa perlu melakukan polling terus-menerus.

#### Alur Kerja Notifikasi Otomatis
```
+----------------+       1. Scan & Bayar       +-----------------+
| Pelanggan / HP | --------------------------> | Bank / E-Wallet |
+----------------+                             +-----------------+
                                                        |
                                                        | 2. Settlement Notif
                                                        v
+------------------+     3. HTTP POST Webhook    +-----------------+
| Server Toko Anda | <-------------------------- |  Server QMIS    |
| (Update ke PAID) |                             | (Auto Signature)|
+------------------+                             +-----------------+
```

1. **Pendaftaran Webhook:** Daftarkan URL endpoint Anda di menu **Customer Portal > Webhooks** (contoh: `https://toko-anda.com/api/webhooks/qmis`). Anda akan mendapatkan **Webhook Secret** (contoh: `whsec_live_9a8b7c6d5e4f...`).
2. **Pengiriman Event:** Ketika QRIS dibayar (atau via endpoint simulasi), Qmis akan mengirimkan payload JSON ke URL Anda dengan HTTP Header keamanan.
3. **Verifikasi Signature:** Server Anda wajib memverifikasi header `X-Qmis-Signature` menggunakan Webhook Secret sebelum mengupdate database.
4. **Respon Cepat:** Kembalikan status HTTP `200 OK` segera setelah signature terverifikasi untuk mencegah retry otomatis.

#### HTTP Headers yang Dikirimkan Qmis
| Header Name | Keterangan |
|-------------|------------|
| `User-Agent` | `Qmis-Webhook/1.0` |
| `Content-Type` | `application/json` |
| `X-Qmis-Event` | Nama event, misalnya: `transaction.paid`, `transaction.expired`, atau `ping` |
| `X-Qmis-Delivery` | UUID unik untuk pengiriman ini (berguna untuk audit dan idempotency) |
| `X-Qmis-Signature` | Signature HMAC-SHA256: `hash_hmac('sha256', raw_json_payload, webhook_secret)` |
| `X-Signature-SHA256` | Alias signature HMAC-SHA256 untuk kompatibilitas integrasi |

#### Contoh Payload JSON (`transaction.paid`)
```json
{
  "event": "transaction.paid",
  "timestamp": "2026-09-05T10:35:12Z",
  "data": {
    "transaction_id": "tx_9d817b12-c288-4f70-a8ce-59fe849ce31c",
    "reference": "INV-20260905-001",
    "merchant": {
      "code": "MC-DEMO-001",
      "name": "Kreatif Sky Abadi Store"
    },
    "amount": 50000,
    "fee_amount": 1500,
    "total_amount": 51500,
    "status": "paid",
    "paid_at": "2026-09-05T10:35:12Z",
    "created_at": "2026-09-05T10:20:00Z"
  }
}
```

#### Contoh Implementasi Verifikasi Webhook di Backend Toko / Aplikasi Anda

##### A. PHP (Native / Laravel)
```php
<?php
// Ambil raw payload dan signature
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_QMIS_SIGNATURE'] ?? $_SERVER['HTTP_X_SIGNATURE_SHA256'] ?? '';
$secret = getenv('QMIS_WEBHOOK_SECRET'); // whsec_live_...

// Hitung signature lokal
$expected = hash_hmac('sha256', $payload, $secret);

// Verifikasi dengan timing-safe comparison
if (!hash_equals($expected, $signature)) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

// Decode event data
$event = json_decode($payload, true);

if ($event['event'] === 'transaction.paid') {
    $orderRef = $event['data']['reference'];
    $amountPaid = $event['data']['total_amount'];
    
    // UPDATE DATABASE TOKO ANDA: Ubah status order menjadi 'PAID'
    // Order::where('reference', $orderRef)->update(['status' => 'PAID', 'paid_at' => now()]);
}

// Selalu kembalikan HTTP 200 OK
http_response_code(200);
echo json_encode(['received' => true]);
```

##### B. Node.js (Express.js)
```javascript
const express = require('express');
const crypto = require('crypto');
const app = express();

// PENTING: Gunakan raw body buffer agar string hash identik
app.use('/webhook/qmis', express.raw({ type: 'application/json' }));

app.post('/webhook/qmis', (req, res) => {
  const signature = req.headers['x-qmis-signature'] || req.headers['x-signature-sha256'];
  const secret = process.env.QMIS_WEBHOOK_SECRET;

  // Hitung HMAC-SHA256
  const hmac = crypto.createHmac('sha256', secret);
  hmac.update(req.body);
  const expected = hmac.digest('hex');

  // Verifikasi signature secara aman
  if (!crypto.timingSafeEqual(Buffer.from(signature || ''), Buffer.from(expected))) {
    return res.status(401).json({ error: 'Invalid signature' });
  }

  const event = JSON.parse(req.body.toString());

  if (event.event === 'transaction.paid') {
    const { reference, total_amount } = event.data;
    // UPDATE DATABASE TOKO: tandai order sebagai PAID
    console.log(`Order ${reference} paid successfully: Rp ${total_amount}`);
  }

  // Respon status 200 segera
  res.status(200).json({ received: true });
});

app.listen(3000, () => console.log('Webhook receiver running on port 3000'));
```

##### C. Python (FastAPI / Flask)
```python
import hmac
import hashlib
from fastapi import FastAPI, Request, HTTPException

app = FastAPI()
WEBHOOK_SECRET = "whsec_live_..."

@app.post("/webhook/qmis")
async def handle_qmis_webhook(request: Request):
    signature = request.headers.get("x-qmis-signature") or request.headers.get("x-signature-sha256")
    raw_body = await request.body()
    
    # Hitung HMAC-SHA256
    expected = hmac.new(WEBHOOK_SECRET.encode(), raw_body, hashlib.sha256).hexdigest()
    
    if not signature or not hmac.compare_digest(expected, signature):
        raise HTTPException(status_code=401, detail="Invalid signature")
        
    payload = await request.json()
    if payload.get("event") == "transaction.paid":
        order_ref = payload["data"]["reference"]
        # UPDATE DATABASE SISTEM ANDA: Update order status to PAID
        print(f"Order {order_ref} telah dibayar!")
        
    return {"received": True}
```

---

## 9. Panduan Portal Web

Aplikasi web dirancang dengan Vue 3 SPA yang responsif dan modern, mendukung mode Terang & Gelap (*Dark Mode*):

1. **Halaman Publik:**
   - **Beranda (`/`):** Penjelasan fitur, komparasi statis vs dinamis, dan kalkulator simulasi instan.
   - **Harga (`/pricing`):** Pilihan tier Basic, Pro, dan Enterprise dengan rincian fitur transparan.
   - **Dokumentasi API (`/api-docs`):** Playground interaktif dengan contoh kode cURL, PHP, Node.js, dan Python.
   - **Legalitas & Kepatuhan (`/legal`):** Ketentuan layanan, kebijakan privasi, dan kepatuhan standar ASPI / BI.

2. **Customer Portal (Merchant Dashboard):**
   - **Dashboard Ringkasan (`/dashboard`):** Total volume penjualan, kuota merchant, grafik tren 14 hari, transaksi terkini.
   - **Generator QRIS Dinamis (`/customer/generator`):** Wizard 5 langkah interaktif dengan chip nominal cepat, pengaturan fee, countdown timer, download SVG/PNG, dan inspektor EMVCo TLV breakdown.
   - **Manajemen Merchant (`/customer/merchants`):** Pendaftaran merchant QRIS statis lengkap dengan **Live Camera Scanner** (webcam) dan drag-and-drop file gambar.
   - **Riwayat Transaksi (`/customer/transactions`):** Pencarian referensi, filter status pembayaran, dan preview detail modal.
   - **API Keys & Integrasi (`/customer/api-keys`):** Pembuatan kunci API dengan modal pengungkapan secret satu kali dan kontrol IP whitelist.
   - **Webhooks (`/customer/webhooks`):** Pengaturan endpoint notifikasi HTTP POST dan tombol **Test Ping** real-time dengan verifikasi tanda tangan HMAC-SHA256.
   - **Tagihan & Langganan (`/customer/billing`):** Pengelolaan paket SaaS, riwayat invoice PPN 11%, dan pembayaran multi-gateway (Midtrans, Xendit, Tripay, Transfer Bank).
   - **Pusat Bantuan (`/customer/tickets`):** Helpdesk tiket dukungan teknis dengan thread percakapan dua arah.
   - **Profil & Keamanan (`/customer/profile`):** Ganti password dan setup Two-Factor Authentication (2FA) Google Authenticator.

3. **Super Admin Portal:**
   - **Monitoring Sistem (`/admin/dashboard`):** Agregat volume transaksi nasional, MRR SaaS, status kesehatan engine API, dan log audit keamanan.
   - **Manajemen Tenant (`/admin/customers`):** Kontrol suspensi/aktivasi akun pelanggan dan penyesuaian kuota sub-merchant secara manual.
   - **Paket & Tarif (`/admin/plans`):** Editor paket langganan SaaS, harga, dan limit laju request API.
   - **Laporan Finansial (`/admin/reports`):** Peringkat merchant bervolume tertinggi dan fitur **Export CSV** transaksi.
   - **Audit Trail Forensik (`/admin/audit-logs`):** Penelusuran kronologis seluruh perubahan data sistem beserta alamat IP pengguna.
   - **Pengaturan Global (`/admin/settings`):** Konfigurasi identitas platform, default expiry QRIS, dan saklar Maintenance Mode.

---

## 10. Pengujian Otomatis (Automated Tests)

Platform ini memiliki cakupan automated test suite yang lengkap pada level Unit dan Feature:

Jalankan seluruh test suite dengan perintah:
```bash
php artisan test
```

### Hasil Uji (100% Passing):
```
PASS  Tests\Unit\QrisEngineTest
✓ it calculates valid crc16 ccitt checksum
✓ it recursively parses and serializes tlv payloads
✓ it validates required emvco tags and detects corrupted crc
✓ it accurately converts static qris tag 01 to dynamic 12
✓ it injects transaction amount into tag 54
✓ it applies fixed convenience fee correctly in tag 55 and 57
✓ it applies percentage fee correctly in tag 55 and 56
✓ it generates high quality svg qr code
✓ it generates png data uri qr code
✓ it correctly handles absorbed fee calculations
✓ it correctly handles charged to customer fee calculations

PASS  Tests\Feature\ApiPlatformTest
✓ health check endpoint returns 200 and healthy status
✓ api key authentication fails with invalid credentials
✓ api key authentication succeeds with valid headers
✓ static qris validation endpoint verifies valid payload
✓ dynamic qris creation converts static to dynamic with amount
✓ idempotency key prevents duplicate transaction creation
✓ rate limiter returns 429 when quota exceeded
✓ simulate paid marks transaction as paid

PASS  Tests\Feature\EmailGatewayTest
✓ it retrieves email gateway configuration
✓ it updates email gateway configuration
✓ it sends test email via email gateway
✓ it dispatches welcome email on registration

Tests:    25 passed (98 assertions)
Duration: 1.57s (100% Pass Rate)
```

---

## 11. Operasional, Backup & Keamanan

### A. Backup Otomatis
Jalankan perintah backup database dan asset kapan saja:
```bash
php artisan app:backup
```
Arsip ZIP terenkripsi akan disimpan di storage yang ditentukan dalam `.env` (`BACKUP_DISK`).

### B. Otomatisasi Transaksi Kadaluarsa
Transaksi QRIS yang melewati batas `expires_at` akan otomatis ditandai sebagai `expired` setiap menit oleh scheduler:
```bash
php artisan transactions:expire
```

### C. Keamanan & Kepatuhan Regulasi
- **Non-Custodial Architecture:** Platform tidak mengendapkan dana pengguna. Dana pembayaran QRIS langsung mengalir dari nasabah pembeli ke rekening bank/PJP acquirer merchant yang terdaftar pada QRIS statis asli.
- **Data Protection:** Hashing kata sandi menggunakan Bcrypt (cost 12), hashing API Secret menggunakan SHA-256, enkripsi session & 2FA TOTP menggunakan secret key aplikasi.
- **Toleransi Bencana (High Availability):** Siap dideploy pada cluster multi-container di AWS ECS, Google Cloud Run, atau Kubernetes dengan database MySQL terkelola dan Redis Cluster.

---

## Hak Cipta & Lisensi

Dikembangkan secara eksklusif untuk **PT Kreatif Sky Abadi**. Seluruh hak cipta dilindungi undang-undang.
