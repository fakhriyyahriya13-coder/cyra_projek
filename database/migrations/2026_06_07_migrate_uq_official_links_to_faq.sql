/*
  Migrasi link resmi UQ ke FAQ.

  Tujuan:
  - Semua data uq_official_links masuk ke faq.
  - Kategori FAQ memakai "Umum".
  - label menjadi pertanyaan.
  - url menjadi jawaban.
  - Pertanyaan alternatif natural ditambahkan agar pencarian chatbot lebih mudah.

  Jalankan pada database CYRA setelah tabel faq tersedia.
*/

DELIMITER //

DROP PROCEDURE IF EXISTS migrate_uq_official_links_to_faq//

CREATE PROCEDURE migrate_uq_official_links_to_faq()
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = 'uq_official_links'
    ) THEN
        INSERT INTO faq (pertanyaan, jawaban, kategori)
        SELECT TRIM(label), TRIM(url), 'Umum'
        FROM uq_official_links old_links
        WHERE TRIM(label) <> ''
          AND TRIM(url) <> ''
          AND NOT EXISTS (
              SELECT 1
              FROM faq
              WHERE pertanyaan = TRIM(old_links.label)
                AND jawaban = TRIM(old_links.url)
                AND LOWER(kategori) = 'umum'
          );

        INSERT INTO faq (pertanyaan, jawaban, kategori)
        SELECT 'website kampus',
               CONCAT('Website utama Universitas Qomaruddin:', CHAR(10), COALESCE((
                   SELECT url
                   FROM uq_official_links
                   WHERE LOWER(CONCAT(label, ' ', url)) LIKE '%uqgresik.ac.id%'
                      OR LOWER(label) LIKE '%website utama%'
                      OR LOWER(label) LIKE '%universitas qomaruddin%'
                   ORDER BY id_link ASC
                   LIMIT 1
               ), 'https://info.uqgresik.ac.id/')),
               'Umum'
        WHERE NOT EXISTS (SELECT 1 FROM faq WHERE pertanyaan = 'website kampus');

        INSERT INTO faq (pertanyaan, jawaban, kategori)
        SELECT 'link siakad',
               CONCAT('SIAKAD Universitas Qomaruddin:', CHAR(10), COALESCE((
                   SELECT url
                   FROM uq_official_links
                   WHERE LOWER(CONCAT(label, ' ', url)) LIKE '%siakad%'
                   ORDER BY id_link ASC
                   LIMIT 1
               ), 'https://siakad.uqgresik.ac.id')),
               'Umum'
        WHERE NOT EXISTS (SELECT 1 FROM faq WHERE pertanyaan = 'link siakad');

        INSERT INTO faq (pertanyaan, jawaban, kategori)
        SELECT 'website siakad',
               CONCAT('SIAKAD Universitas Qomaruddin:', CHAR(10), COALESCE((
                   SELECT url
                   FROM uq_official_links
                   WHERE LOWER(CONCAT(label, ' ', url)) LIKE '%siakad%'
                   ORDER BY id_link ASC
                   LIMIT 1
               ), 'https://siakad.uqgresik.ac.id')),
               'Umum'
        WHERE NOT EXISTS (SELECT 1 FROM faq WHERE pertanyaan = 'website siakad');

        INSERT INTO faq (pertanyaan, jawaban, kategori)
        SELECT 'pendaftaran mahasiswa baru',
               CONCAT('Pendaftaran Mahasiswa Baru Universitas Qomaruddin:', CHAR(10), COALESCE((
                   SELECT url
                   FROM uq_official_links
                   WHERE LOWER(CONCAT(label, ' ', url)) LIKE '%pmb%'
                      OR LOWER(CONCAT(label, ' ', url)) LIKE '%daftar%'
                      OR LOWER(CONCAT(label, ' ', url)) LIKE '%pendaftaran%'
                   ORDER BY id_link ASC
                   LIMIT 1
               ), 'https://daftar.uqgresik.ac.id')),
               'Umum'
        WHERE NOT EXISTS (SELECT 1 FROM faq WHERE pertanyaan = 'pendaftaran mahasiswa baru');

        INSERT INTO faq (pertanyaan, jawaban, kategori)
        SELECT 'website informatika',
               CONCAT('Website Teknik Informatika Universitas Qomaruddin:', CHAR(10), COALESCE((
                   SELECT url
                   FROM uq_official_links
                   WHERE LOWER(CONCAT(label, ' ', url)) LIKE '%informatika%'
                      OR LOWER(CONCAT(label, ' ', url)) LIKE '%if.uqgresik%'
                   ORDER BY id_link ASC
                   LIMIT 1
               ), 'https://if.uqgresik.ac.id/')),
               'Umum'
        WHERE NOT EXISTS (SELECT 1 FROM faq WHERE pertanyaan = 'website informatika');

        INSERT INTO faq (pertanyaan, jawaban, kategori)
        SELECT 'link lppm',
               CONCAT('LPPM Universitas Qomaruddin:', CHAR(10), COALESCE((
                   SELECT url
                   FROM uq_official_links
                   WHERE LOWER(CONCAT(label, ' ', url)) LIKE '%lppm%'
                   ORDER BY id_link ASC
                   LIMIT 1
               ), 'https://lppm.uqgresik.ac.id')),
               'Umum'
        WHERE NOT EXISTS (SELECT 1 FROM faq WHERE pertanyaan = 'link lppm');
    END IF;
END//

CALL migrate_uq_official_links_to_faq()//

DROP PROCEDURE IF EXISTS migrate_uq_official_links_to_faq//

DELIMITER ;

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
SELECT 'link lppm', 'LPPM Universitas Qomaruddin:\nhttps://lppm.uqgresik.ac.id', 'Umum'
WHERE NOT EXISTS (SELECT 1 FROM faq WHERE pertanyaan = 'link lppm');

-- Setelah data FAQ diverifikasi, tabel lama boleh dihapus manual jika sudah tidak diperlukan:
-- DROP TABLE uq_official_links;
