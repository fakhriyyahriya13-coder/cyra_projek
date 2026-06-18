# Running dan Refactor Bertahap

## Cara Running Saat Ini

1. Jalankan Apache dan MySQL dari XAMPP.
2. Pastikan PHP yang dipakai XAMPP minimal `8.2`.
3. Pastikan database `cyra` tersedia dan sesuai dengan tabel admin/chatbot.
4. Pastikan `key.json` ada di root proyek.
5. Buka `http://localhost/cyra/` untuk halaman chat.
6. Buka `http://localhost/cyra/app/auth/login.php` untuk admin.
7. Webhook Dialogflow tetap mengarah ke `app/cyra/webhook.php`.

## Syarat PHP

Dependency Composer CYRA membutuhkan PHP `>= 8.2`.

Cek versi PHP yang dipakai web server:

```text
http://localhost/cyra/cek_php.php
```

Cek versi PHP dari terminal:

```powershell
C:\xampp\php\php.exe -v
```

Jika masih PHP `8.0.30`, gunakan XAMPP/PHP 8.2 atau lebih baru. Jangan downgrade dependency ke versi lama yang terkena security advisory.

## Perubahan Struktur Tahap Ini

- `app/cyra/webhook.php` tetap menjadi URL webhook, tetapi helper dipindahkan ke `app/Services/Cyra/`.
- `index.php` dan `app/cyra/chatbot.php` memakai helper Dialogflow yang sama: `app/Services/Cyra/Dialogflow.php`.
- Fondasi helper aplikasi ditambahkan di `app/Foundation/`.
- Konfigurasi utama dipindahkan ke `config/database.php`, dengan wrapper lama di `app/config/database.php`.
- File runtime dipindahkan ke `storage/logs/` dan `storage/framework/session_state/`.

## Verifikasi Cepat

Gunakan PHP XAMPP karena `php` belum masuk PATH:

```powershell
C:\xampp\php\php.exe -l index.php
C:\xampp\php\php.exe -l app\cyra\chatbot.php
C:\xampp\php\php.exe -l app\cyra\webhook.php
C:\xampp\php\php.exe -l app\Services\Cyra\Dialogflow.php
C:\xampp\php\php.exe -l app\Foundation\Auth.php
C:\xampp\php\php.exe -l app\Foundation\Database.php
```

Peringatan `Module "openssl" is already loaded` berasal dari konfigurasi PHP XAMPP, bukan dari kode aplikasi.

## Sinkron Website Resmi UQ

CYRA dapat mengambil daftar link publik dari `https://www.uqgresik.ac.id/index.html`.

Jalankan:

```powershell
C:\xampp\php\php.exe scripts\sync_uq_official.php
```

Cache tersimpan di:

```text
storage/app/uq_official_links.json
```

Data juga disimpan ke tabel:

```text
uq_official_links
```

Webhook bisa menjawab pertanyaan seperti:

- `link siakad`
- `website uq`
- `pmb uq`
- `lppm uq`
- `teknik informatika uq`

Data akademik internal seperti jadwal kuliah atau KRS tetap sebaiknya memakai API/izin resmi kampus, bukan scraping halaman login SIAKAD.

## Roadmap Jangka Panjang

1. Pakai `app/Foundation/Auth.php` untuk login guard admin di halaman admin satu per satu.
2. Pakai `app/Foundation/Database.php` untuk load koneksi database tanpa loop manual.
3. Ubah fungsi di `app/Services/Cyra/` menjadi class service.
4. Pisahkan template admin menjadi layout bersama.
5. Pindahkan CSS inline admin ke `assets/css/admin.css` atau file asset per modul.
6. Baru setelah logic bersih, migrasi ke Laravel akan lebih aman karena service dan tanggung jawab file sudah jelas.
