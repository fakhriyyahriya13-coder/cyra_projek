# Struktur Proyek CYRA

Dokumen ini merangkum struktur proyek saat ini dan arah perapihan yang aman untuk aplikasi chatbot CYRA yang masih native PHP.

## Ringkasan

CYRA adalah chatbot akademik berbasis Dialogflow. Proyek terdiri dari halaman chat publik, webhook Dialogflow, panel admin untuk data akademik, autentikasi admin, konfigurasi database, asset CSS/JS, dan dependency Composer.

## Struktur Saat Ini

```text
cyra/
|-- index.php
|-- cek_koneksi.php
|-- .env.example
|-- composer.json
|-- composer.lock
|-- README.md
|-- app/
|   |-- Foundation/
|   |   |-- Auth.php
|   |   |-- Database.php
|   |   `-- Paths.php
|   |-- Services/
|   |   `-- Cyra/
|   |       |-- AcademicAnswers.php
|   |       |-- Context.php
|   |       |-- DatabaseHelpers.php
|   |       |-- Dialogflow.php
|   |       |-- Response.php
|   |       `-- Text.php
|   |-- admin/
|   |   |-- dashboard.php
|   |   |-- akun.php
|   |   |-- dosen.php
|   |   |-- faq.php
|   |   |-- jadwal_kuliah.php
|   |   |-- jadwal_uts.php
|   |   |-- jadwal_uas.php
|   |   |-- kurikulum.php
|   |   |-- prosedur_frs.php
|   |   |-- prosedur_kp.php
|   |   |-- prosedur_ta.php
|   |   |-- prosedur.php
|   |   `-- layout/
|   |       |-- sidebar.php
|   |       |-- topbar.php
|   |       `-- footer.php
|   |-- auth/
|   |   |-- login.php
|   |   |-- proses_login.php
|   |   `-- logout.php
|   |-- config/
|   |   `-- database.php
|   `-- cyra/
|       |-- chatbot.php
|       |-- webhook.php
|       |-- README.md
|-- bootstrap/
|   `-- app.php
|-- config/
|   `-- database.php
|-- database/
|   |-- migrations/
|   `-- seeders/
|-- public/
|   `-- README.md
|-- resources/
|   |-- assets/
|   `-- views/
|-- routes/
|   |-- api.php
|   `-- web.php
|-- storage/
|   |-- framework/
|   |   `-- session_state/
|   `-- logs/
|-- assets/
|   |-- css/
|   |   `-- admin.css
|   `-- js/
|       `-- cyra-chat.js
`-- vendor/
```

## Peran File Utama

- `index.php`: halaman chat publik dan endpoint AJAX chat session browser.
- `app/cyra/chatbot.php`: endpoint JSON sederhana untuk mengirim pesan ke Dialogflow.
- `app/cyra/webhook.php`: endpoint/router webhook Dialogflow yang tetap dipakai Dialogflow.
- `app/Services/Cyra/*`: helper Dialogflow, response, context, normalisasi teks, database, dan pembentuk jawaban akademik.
- `config/database.php`: konfigurasi dan koneksi MySQL utama.
- `app/config/database.php`: wrapper lama agar halaman native yang belum dimigrasi tetap jalan.
- `app/Foundation/Paths.php`: helper path root/app/assets.
- `app/Foundation/Database.php`: loader koneksi database untuk refactor bertahap.
- `app/Foundation/Auth.php`: helper session dan guard login admin untuk refactor bertahap.
- `bootstrap/app.php`: bootstrap native ala Laravel.
- `routes/*`: peta route untuk migrasi front controller di masa depan.
- `storage/*`: lokasi runtime log dan session state.
- `app/auth/*`: login, proses login, logout admin.
- `app/admin/*`: CRUD native untuk dashboard, akun, dosen, FAQ, jadwal, kurikulum, dan prosedur.
- `assets/css/admin.css`: CSS admin lama/umum.
- `assets/js/cyra-chat.js`: JS chat lama yang masih memanggil `app/cyra/chatbot.php`.

## Temuan Audit

- `vendor/` adalah dependency Composer, tidak perlu dibaca atau diedit untuk perapihan aplikasi.
- `key.json`, `storage/logs/`, dan `storage/framework/session_state/` termasuk file sensitif/runtime, sebaiknya tidak masuk Git.
- `webhook.php` sudah diperkecil menjadi endpoint/router. Helper teknisnya sudah dipindahkan ke `app/Services/Cyra/`.
- `index.php` masih mencampur session, HTML, CSS, dan JS dalam satu file, tetapi logic Dialogflow sudah memakai helper bersama.
- `app/cyra/chatbot.php` dan logic AJAX di `index.php` sekarang memakai `app/Services/Cyra/Dialogflow.php`, sehingga duplikasi Google client berkurang.
- Banyak halaman admin sudah memakai prepared statement, tetapi `app/admin/prosedur.php` terlihat sebagai file lama dan tidak sejalan dengan pola halaman prosedur FRS/KP/TA.
- File admin banyak memiliki CSS inline yang mirip. Ini bisa dipindahkan bertahap ke asset bersama setelah fungsi CRUD stabil.

## Target Struktur Bertahap

Struktur yang disarankan tanpa memutus URL lama:

```text
app/
|-- config/
|   `-- database.php
|-- Foundation/
|   |-- Auth.php
|   |-- Database.php
|   `-- Paths.php
|-- Services/
|   `-- Cyra/
|-- cyra/
|   |-- chatbot.php
|   |-- webhook.php
|-- admin/
`-- auth/
```

## Urutan Perapihan Aman

1. Amankan file sensitif dan runtime dengan `.gitignore`.
2. Pisahkan helper umum, misalnya auth admin dan loader database, tanpa mengubah URL halaman.
3. Pecah `webhook.php` menjadi helper kecil: response, text normalization, context, query akademik, dan router intent. Selesai tahap awal.
4. Satukan akses Dialogflow agar `index.php` dan `app/cyra/chatbot.php` memakai service yang sama. Selesai tahap awal.
5. Pindahkan helper global di `app/Services/Cyra/` menjadi class service agar lebih siap menuju Laravel.
6. Pindahkan CSS inline admin ke asset bersama secara halaman per halaman.
7. Evaluasi `app/admin/prosedur.php`: hapus jika sudah tidak dipakai, atau ubah agar aman dan konsisten.
8. Ganti loader database dan guard login manual di halaman admin dengan helper `app/Foundation/*` secara bertahap.

## Catatan Keamanan

- Jangan commit `key.json`.
- Jangan commit isi `storage/logs/`.
- Jangan commit isi `storage/framework/session_state/`.
- Hindari query SQL langsung dari `$_GET` atau `$_POST`; gunakan prepared statement.
- Setelah memecah file, jalankan lint dengan `C:\xampp\php\php.exe -l nama_file.php`.
