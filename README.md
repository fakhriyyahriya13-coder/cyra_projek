# CYRA (Cyber Assistant for Informatics)

Chatbot akademik berbasis Dialogflow dengan native PHP.

Syarat utama:

- PHP `>= 8.2`
- XAMPP/Apache + MySQL
- Composer dependencies dari `composer.lock`
- `key.json` Dialogflow di root proyek

Struktur proyek sudah dirapikan bertahap agar mirip Laravel tanpa memutus URL lama:

- `app/Foundation/`: helper inti aplikasi.
- `app/Services/Cyra/`: logic chatbot, Dialogflow, webhook, dan formatter jawaban.
- `app/cyra/`: endpoint lama chatbot dan webhook.
- `config/`: konfigurasi utama.
- `routes/`: peta route untuk migrasi front controller.
- `storage/`: file runtime seperti log dan session state.
- `docs/`: dokumentasi struktur dan cara running.

Lihat `docs/RUNNING_AND_REFACTOR.md` untuk cara menjalankan dan roadmap refactor.

## Deploy Percobaan ke Vercel

Proyek menyediakan `vercel.json` dan front controller `api/index.php` untuk
community PHP runtime. Database lokal XAMPP tidak dapat diakses dari Vercel,
jadi gunakan MySQL eksternal seperti Aiven.

Environment variable minimum:

- `DB_HOST`
- `DB_PORT`
- `DB_USER`
- `DB_PASS`
- `DB_NAME`
- `DB_SSL=true`
- `DB_SSL_CA_BASE64` berisi CA certificate Aiven yang sudah diubah ke Base64
- `CYRA_DIALOGFLOW_PROJECT_ID`
- `CYRA_DIALOGFLOW_CREDENTIALS_BASE64` berisi `key.json` yang sudah diubah ke Base64

Jangan upload `key.json`, CA certificate, atau password database ke GitHub.

Data MySQL lokal dapat disalin ke Aiven dengan mengisi environment variable
target, lalu menjalankan:

```powershell
C:\xampp\php\php.exe scripts\migrate_mysql_to_aiven.php
```

Gunakan `--append` jika target tidak boleh dikosongkan.
