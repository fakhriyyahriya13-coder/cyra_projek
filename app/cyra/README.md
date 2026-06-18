# Modul CYRA

Folder ini berisi komponen chatbot CYRA.

## File

- `chatbot.php`: endpoint JSON untuk chat dari browser ke Dialogflow.
- `webhook.php`: endpoint webhook Dialogflow untuk menjawab intent dari database akademik.
- `../Services/Cyra/Dialogflow.php`: helper bersama untuk koneksi Dialogflow dari `index.php` dan `chatbot.php`.
- `../Services/Cyra/Response.php`: helper response JSON dan logging webhook.
- `../Services/Cyra/Text.php`: helper normalisasi teks, typo, intent, parameter, hari, tanggal, dan FAQ matching.
- `../Services/Cyra/Context.php`: helper context Dialogflow dan session state lokal.
- `../Services/Cyra/DatabaseHelpers.php`: helper database untuk query akademik.
- `../Services/Cyra/AcademicAnswers.php`: pembentuk jawaban akademik dari database.
- `../../storage/logs/webhook.log`: log runtime webhook, tidak perlu masuk Git.
- `../../storage/framework/session_state/`: penyimpanan context sementara webhook, tidak perlu masuk Git.

## Arah Perapihan

`webhook.php` sekarang menjadi endpoint/router utama, sedangkan helper teknis dipindahkan ke `app/Services/Cyra/`. URL `app/cyra/webhook.php` tetap sama untuk Dialogflow, tetapi isi filenya lebih mudah dirawat.

Tahap berikutnya yang aman:

- ubah fungsi global di `app/Services/Cyra/` menjadi class service;
- lanjutkan pemindahan runtime ke `storage/` jika ada file lain;
- tambah test manual untuk intent penting;
- rapikan halaman admin dengan helper auth dan layout bersama.
