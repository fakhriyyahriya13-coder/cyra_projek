INSERT INTO users (nama, email, username, password, role)
SELECT 'Administrator', 'admin@cyra.local', 'admin', '$2y$10$0gdkQ5qxf19j38ANN8CQC.6VAUzziG/buIkoLCngqm0TPirGoPhcy', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'admin');

INSERT INTO jadwal_kuliah (semester, mata_kuliah, dosen, hari, jam_mulai, jam_selesai, ruang)
SELECT 4, 'Pemrograman Berbasis Web', 'Ahmad Wahyu Rosyadi', 'Rabu', '08:00:00', '10:30:00', '206'
WHERE NOT EXISTS (
    SELECT 1
    FROM jadwal_kuliah
    WHERE semester = 4
      AND LOWER(mata_kuliah) = 'pemrograman berbasis web'
);

INSERT INTO prosedur_kp (judul, deskripsi)
SELECT 'Memenuhi syarat akademik', 'Mahasiswa memastikan syarat akademik Kerja Praktik sudah terpenuhi sesuai ketentuan prodi.'
WHERE NOT EXISTS (SELECT 1 FROM prosedur_kp);

INSERT INTO prosedur_kp (judul, deskripsi)
SELECT 'Menentukan tempat KP', 'Mahasiswa menentukan instansi atau tempat Kerja Praktik yang sesuai dengan bidang keilmuan.'
WHERE (SELECT COUNT(*) FROM prosedur_kp) < 2;

INSERT INTO prosedur_kp (judul, deskripsi)
SELECT 'Mengajukan surat pengantar', 'Mahasiswa mengajukan surat pengantar KP melalui prodi atau bagian akademik.'
WHERE (SELECT COUNT(*) FROM prosedur_kp) < 3;

INSERT INTO prosedur_frs (judul, deskripsi)
SELECT 'Konsultasi dosen wali', 'Mahasiswa berkonsultasi dengan dosen wali sebelum mengisi FRS/KRS.'
WHERE NOT EXISTS (SELECT 1 FROM prosedur_frs);

INSERT INTO prosedur_ta (judul, deskripsi)
SELECT 'Menentukan topik TA', 'Mahasiswa menentukan topik Tugas Akhir dan berkonsultasi dengan dosen pembimbing atau prodi.'
WHERE NOT EXISTS (SELECT 1 FROM prosedur_ta);

INSERT INTO faq (pertanyaan, jawaban, kategori)
SELECT 'Apa itu CYRA?', 'CYRA adalah chatbot akademik untuk membantu informasi jadwal, dosen, mata kuliah, FRS, KP, TA, dan FAQ kampus.', 'Umum'
WHERE NOT EXISTS (SELECT 1 FROM faq);

INSERT INTO faq (pertanyaan, jawaban, kategori)
SELECT 'website kampus', 'Website utama Universitas Qomaruddin:\nhttps://info.uqgresik.ac.id/', 'Umum'
WHERE NOT EXISTS (SELECT 1 FROM faq WHERE pertanyaan = 'website kampus');

INSERT INTO faq (pertanyaan, jawaban, kategori)
SELECT 'link siakad', 'SIAKAD Universitas Qomaruddin:\nhttps://siakad.uqgresik.ac.id', 'Umum'
WHERE NOT EXISTS (SELECT 1 FROM faq WHERE pertanyaan = 'link siakad');

INSERT INTO faq (pertanyaan, jawaban, kategori)
SELECT 'website siakad', 'SIAKAD Universitas Qomaruddin:\nhttps://siakad.uqgresik.ac.id', 'Umum'
WHERE NOT EXISTS (SELECT 1 FROM faq WHERE pertanyaan = 'website siakad');

INSERT INTO faq (pertanyaan, jawaban, kategori)
SELECT 'pendaftaran mahasiswa baru', 'Pendaftaran Mahasiswa Baru Universitas Qomaruddin:\nhttps://daftar.uqgresik.ac.id', 'Umum'
WHERE NOT EXISTS (SELECT 1 FROM faq WHERE pertanyaan = 'pendaftaran mahasiswa baru');

INSERT INTO faq (pertanyaan, jawaban, kategori)
SELECT 'website informatika', 'Website Teknik Informatika Universitas Qomaruddin:\nhttps://if.uqgresik.ac.id/', 'Umum'
WHERE NOT EXISTS (SELECT 1 FROM faq WHERE pertanyaan = 'website informatika');

INSERT INTO faq (pertanyaan, jawaban, kategori)
SELECT 'hubungi admin prodi', 'Untuk menghubungi admin Prodi Teknik Informatika, silakan cek website resmi prodi:\nhttps://if.uqgresik.ac.id/\nAtau sampaikan pertanyaan melalui kanal akademik/prodi resmi yang tercantum di sana.', 'Umum'
WHERE NOT EXISTS (SELECT 1 FROM faq WHERE pertanyaan = 'hubungi admin prodi');

INSERT INTO faq (pertanyaan, jawaban, kategori)
SELECT 'link lppm', 'LPPM Universitas Qomaruddin:\nhttps://lppm.uqgresik.ac.id', 'Umum'
WHERE NOT EXISTS (SELECT 1 FROM faq WHERE pertanyaan = 'link lppm');
