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
