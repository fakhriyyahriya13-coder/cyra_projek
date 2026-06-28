<?php
/*
 * Academic answer builders for CYRA webhook responses.
 * Extracted from app/cyra/webhook.php to keep the webhook endpoint small.
 */

/* =========================================================
   PENGERTIAN SINGKAT
========================================================= */
function pengertianFRS()
{
    return "FRS adalah Formulir Rencana Studi, yaitu dokumen untuk merencanakan mata kuliah pada semester berjalan.";
}

function pengertianKP()
{
    return "KP atau Kerja Praktik adalah kegiatan akademik untuk memberi pengalaman kerja nyata sesuai bidang keilmuan.";
}

function pengertianTA()
{
    return "TA atau Tugas Akhir adalah karya ilmiah, penelitian, atau proyek akhir sebagai salah satu syarat kelulusan.";
}

function cyraIsPertanyaanTentangCyra($userText)
{
    return containsAny($userText, [
        'apa itu cyra',
        'cyra itu apa',
        'siapa cyra',
        'fungsi cyra',
        'apa fungsi cyra',
        'fitur cyra',
        'apa saja fitur cyra',
        'cyra bisa apa',
        'kegunaan cyra',
        'manfaat cyra',
        'tentang cyra',
        'chatbot cyra'
    ]);
}

function cyraJawabanTentangCyra($userText)
{
    if (containsAny($userText, ['fitur cyra', 'apa saja fitur cyra', 'fitur apa saja'])) {
        return "Fitur CYRA meliputi informasi jadwal kuliah, jadwal UTS, jadwal UAS, mata kuliah, data dosen, prosedur FRS, prosedur KP, prosedur TA, FAQ, dan informasi pendaftaran prodi.";
    }

    if (containsAny($userText, ['fungsi cyra', 'apa fungsi cyra', 'cyra bisa apa', 'kegunaan cyra', 'manfaat cyra'])) {
        return "Fungsi CYRA adalah membantu mahasiswa mendapatkan informasi akademik Informatika, seperti jadwal kuliah, jadwal UTS/UAS, data dosen, mata kuliah, prosedur FRS, KP, TA, FAQ, dan pendaftaran prodi.";
    }

    return "CYRA adalah Cyber Assistant for Informatics, yaitu chatbot akademik yang membantu mahasiswa mendapatkan informasi Prodi Teknik Informatika Universitas Qomaruddin.";
}

function cyraIsPertanyaanUmumAkademik($userText)
{
    $text = normalizeText(normalizeTypo($userText));

    return in_array($text, ['cyra', 'uq', 'qomaruddin', 'universitas qomaruddin', 'teknik informatika', 'informatika'], true) ||
        containsAny($text, [
            'apa itu uq',
            'uq itu apa',
            'apa itu universitas qomaruddin',
            'universitas qomaruddin itu apa',
            'apa itu qomaruddin',
            'informasi kampus',
            'tentang kampus',
            'tentang universitas qomaruddin',
            'apa itu teknik informatika',
            'teknik informatika itu apa',
            'apa itu prodi teknik informatika',
            'prodi teknik informatika itu apa',
            'tentang teknik informatika',
            'tentang prodi informatika',
            'informasi teknik informatika',
            'informasi prodi'
        ]);
}

function cyraJawabanUmumAkademik($userText)
{
    $text = normalizeText(normalizeTypo($userText));

    if (cyraIsPertanyaanKontakProdi($userText)) {
        return cyraJawabanKontakProdi();
    }

    if ($text === 'cyra' || containsAny($text, ['apa itu cyra', 'cyra itu apa', 'siapa cyra', 'tentang cyra'])) {
        return cyraJawabanTentangCyra($userText);
    }

    if (containsAny($text, ['teknik informatika', 'prodi informatika', 'informatika'])) {
        return "Prodi Teknik Informatika Universitas Qomaruddin adalah program studi yang berfokus pada bidang komputasi, pemrograman, sistem informasi, jaringan, basis data, kecerdasan buatan, dan pengembangan teknologi informasi.";
    }

    if (containsAny($text, ['uq', 'qomaruddin', 'universitas qomaruddin', 'kampus'])) {
        return "Universitas Qomaruddin (UQ) adalah perguruan tinggi di Gresik. CYRA membantu menyediakan informasi akademik terkait Prodi Teknik Informatika Universitas Qomaruddin.";
    }

    return "CYRA membantu menjawab informasi akademik Informatika, seperti jadwal kuliah, UTS, UAS, dosen, mata kuliah, FRS, KP, TA, FAQ, dan informasi umum prodi.";
}

function cyraIsPertanyaanKontakProdi($userText)
{
    $text = normalizeText(normalizeTypo($userText));

    $hasContactCue = containsAny($text, [
        'hubungi',
        'menghubungi',
        'kontak',
        'contact',
        'telepon',
        'wa',
        'whatsapp',
        'email'
    ]);

    $hasTargetCue = containsAny($text, [
        'admin',
        'prodi',
        'program studi',
        'teknik informatika',
        'informatika'
    ]);

    return $hasContactCue && $hasTargetCue;
}

function cyraJawabanKontakProdi()
{
    return "Untuk menghubungi admin Prodi Teknik Informatika, silakan cek website resmi prodi di https://if.uqgresik.ac.id/ atau hubungi kanal akademik/prodi resmi yang tercantum di sana.";
}

function cyraIsPertanyaanFAQAdmin($userText)
{
    return containsAny($userText, [
        'admin bisa menambah faq',
        'admin tambah faq',
        'menambah faq',
        'tambah faq',
        'ubah faq',
        'mengubah faq',
        'hapus faq',
        'menghapus faq',
        'faq admin',
        'faq bisa ditambah'
    ]);
}

function cyraIsPertanyaanFAQMasukCyra($userText)
{
    return containsAny($userText, [
        'data faq masuk ke cyra',
        'faq masuk ke cyra',
        'faq tersambung ke cyra',
        'faq nyambung ke cyra',
        'faq terhubung ke cyra',
        'faq dari admin masuk ke cyra',
        'kalau admin tambah faq apakah masuk ke cyra',
        'kalau admin menambah faq apakah cyra bisa menjawab',
        'data faq digunakan cyra',
        'faq bisa dijawab cyra',
        'faq admin digunakan cyra'
    ]);
}

function cyraJawabanFAQAdmin()
{
    return "Bisa. Admin dapat menambah, mengubah, dan menghapus FAQ melalui halaman admin. Data FAQ yang ditambahkan akan digunakan CYRA untuk menjawab pertanyaan pengguna.";
}

function cyraJawabanFAQMasukCyra()
{
    return "Ya, data FAQ yang ditambahkan oleh admin akan masuk ke CYRA dan digunakan untuk menjawab pertanyaan pengguna.";
}

function cyraIsPertanyaanDaftarDosen($userText)
{
    return containsAny($userText, [
        'tampilkan daftar dosen',
        'daftar dosen',
        'data dosen',
        'siapa saja dosen informatika',
        'siapa saja dosen',
        'dosen informatika'
    ]);
}

function cyraIsBareTodayQuestion($userText): bool
{
    $text = normalizeText(normalizeTypo($userText));

    return in_array($text, ['hari ini', 'sekarang', 'today'], true);
}

function cyraIsOutOfScopeCasualQuestion($userText): bool
{
    $text = normalizeText(normalizeTypo($userText));

    return containsAny($text, ['tidur', 'makan', 'minum', 'film', 'bioskop', 'game', 'cuaca']) &&
        !hasAcademicContext($text);
}

function cyraIsExamRegistrationQuestion($userText): bool
{
    $text = normalizeText(normalizeTypo($userText));

    return containsAny($text, ['uts', 'uas']) &&
        containsAny($text, ['cara', 'bagaimana', 'prosedur', 'daftar', 'mendaftar', 'pendaftaran', 'registrasi']);
}

function cyraExamRegistrationAnswer($userText): string
{
    $text = normalizeText(normalizeTypo($userText));
    $label = containsAny($text, ['uts']) ? 'UTS' : 'UAS';

    return "Untuk $label biasanya tidak ada pendaftaran mandiri melalui CYRA. Pastikan FRS/KRS sudah disetujui, nama tercatat sebagai peserta mata kuliah, dan tidak ada kendala administrasi akademik/keuangan.\n\nUntuk melihat jadwalnya, ketik: jadwal $label semester 6 atau jadwal $label semua.";
}

function cariJawabanFAQKategori($conn, $userText, $kategori = 'Umum', $minimumScore = 70): ?string
{
    return cariJawabanFAQ($conn, $userText, $minimumScore, $kategori);
}

function tampilPendaftaranTeknikInformatika($conn)
{
    $faqAnswer = cariJawabanFAQKategori($conn, 'pendaftaran mahasiswa baru pmb teknik informatika', 'Umum', 65);

    if ($faqAnswer !== null) {
        return $faqAnswer;
    }

    $text = "Pendaftaran Mahasiswa Baru Teknik Informatika Universitas Qomaruddin:\n\n";
    $text .= "1. Buka kanal PMB resmi Universitas Qomaruddin.\n";
    $text .= "2. Pilih program studi Teknik Informatika (S-1).\n";
    $text .= "3. Isi formulir pendaftaran dan lengkapi data calon mahasiswa.\n";
    $text .= "4. Ikuti instruksi pembayaran, verifikasi, dan pengumuman dari panitia PMB.\n";
    $text .= "5. Untuk informasi akademik prodi, cek website Teknik Informatika.\n\n";

    $text .= "Link penting:\n";
    $text .= "- PMB: https://daftar.uqgresik.ac.id\n";
    $text .= "- Teknik Informatika: https://if.uqgresik.ac.id/\n\n";
    $text .= "Catatan: CYRA difokuskan untuk informasi Prodi Teknik Informatika. Untuk biaya, gelombang pendaftaran, dan jadwal seleksi terbaru, ikuti informasi resmi di halaman PMB.";

    return cyraNormalizeAnswerText($text);
}

/* =========================================================
   PROSEDUR
   - Mode singkat dipakai untuk pertanyaan: cara/prosedur/langkah/alur/bagaimana
   - Bagian pengertian/tujuan/penjelasan umum akan dilewati
========================================================= */
function isBagianUmumProsedur($judul, $deskripsi)
{
    $gabungan = normalizeText($judul . ' ' . $deskripsi);

    $kataUmum = [
        'pengertian',
        'tujuan',
        'definisi',
        'arti',
        'adalah',
        'merupakan',
        'bertujuan',
        'digunakan',
        'dokumen yang digunakan',
        'kartu rencana studi',
        'formulir rencana studi'
    ];

    foreach ($kataUmum as $kata) {
        if (strpos($gabungan, normalizeText($kata)) !== false) {
            return true;
        }
    }

    return false;
}

function defaultProsedurText($table, $judulHeader)
{
    $defaults = [
        'prosedur_frs' => [
            'Pastikan tidak ada kendala administrasi akademik/keuangan.',
            'Konsultasikan rencana mata kuliah dengan dosen wali.',
            'Isi FRS/KRS sesuai mata kuliah yang tersedia pada semester berjalan.',
            'Ajukan validasi atau persetujuan dosen wali.',
            'Simpan atau cetak bukti FRS/KRS yang sudah disetujui.'
        ],
        'prosedur_kp' => [
            'Pastikan sudah memenuhi syarat akademik untuk mengajukan Kerja Praktik.',
            'Tentukan tempat atau instansi tujuan Kerja Praktik.',
            'Konsultasikan rencana KP dengan dosen pembimbing atau prodi.',
            'Ajukan surat pengantar KP melalui prodi atau bagian akademik.',
            'Laksanakan KP sesuai ketentuan kampus dan instansi.',
            'Susun laporan KP dan lakukan bimbingan.',
            'Ajukan seminar atau penilaian KP sesuai prosedur prodi.'
        ],
        'prosedur_ta' => [
            'Pastikan sudah memenuhi syarat pengajuan Tugas Akhir.',
            'Tentukan topik atau judul Tugas Akhir.',
            'Konsultasikan topik dengan calon dosen pembimbing atau prodi.',
            'Ajukan judul dan proposal sesuai ketentuan prodi.',
            'Lakukan bimbingan dan revisi sampai disetujui.',
            'Ajukan seminar proposal, seminar hasil, atau sidang sesuai tahapan prodi.'
        ]
    ];

    if (!isset($defaults[$table])) {
        return null;
    }

    $text = $judulHeader . ":\n\n";

    foreach ($defaults[$table] as $index => $step) {
        $text .= ($index + 1) . '. ' . $step . "\n";
    }

    $text .= "\nCatatan: ini jawaban sementara karena data $judulHeader belum tersedia lengkap.";

    return trim($text);
}

function cyraRequirementKeywords(): array
{
    return [
        'syarat',
        'persyaratan',
        'ketentuan',
        'aturan',
        'administratif',
        'administrasi',
        'kewajiban',
        'prasyarat',
        'minimal',
        'kelayakan',
        'batas',
        'durasi',
        'larangan',
        'pembatalan'
    ];
}

function cyraIsRequirementPhrase(string $userText): bool
{
    return containsAny($userText, cyraRequirementKeywords());
}

function cyraProcedureRequirementTopic(string $userText): ?string
{
    if (containsAny($userText, ['frs'])) {
        return 'frs';
    }

    if (containsAny($userText, ['kp', 'kerja praktik', 'kerja praktek'])) {
        return 'kp';
    }

    if (containsAny($userText, ['ta', 'tugas akhir', 'skripsi'])) {
        return 'ta';
    }

    return null;
}

function cyraIsProcedureRequirementQuestion(string $userText): bool
{
    return cyraProcedureRequirementTopic($userText) !== null && cyraIsRequirementPhrase($userText);
}

function cyraRequirementConfig(string $topic): ?array
{
    $configs = [
        'frs' => [
            'table' => 'prosedur_frs',
            'header' => 'Syarat dan Ketentuan FRS',
            'complete_command' => 'prosedur FRS lengkap',
            'fallback' => "Syarat dan ketentuan FRS meliputi: tidak memiliki kendala administrasi akademik/keuangan, berkonsultasi dengan dosen wali, memilih mata kuliah sesuai kurikulum dan semester berjalan, memenuhi prasyarat mata kuliah bila ada, mengikuti batas SKS sesuai ketentuan akademik, lalu menunggu validasi atau persetujuan dosen wali.\n\nUntuk tahapan lengkap, ketik: prosedur FRS lengkap"
        ],
        'kp' => [
            'table' => 'prosedur_kp',
            'header' => 'Syarat dan Ketentuan KP',
            'complete_command' => 'prosedur KP lengkap',
            'fallback' => "Syarat dan ketentuan KP meliputi: memenuhi syarat akademik, menyiapkan administrasi, tempat KP disetujui prodi, melaksanakan KP sesuai aturan kampus dan instansi, melakukan bimbingan, menyusun laporan, lalu mengikuti seminar atau penilaian KP sesuai ketentuan prodi.\n\nUntuk tahapan lengkap, ketik: prosedur KP lengkap"
        ],
        'ta' => [
            'table' => 'prosedur_ta',
            'header' => 'Syarat dan Ketentuan TA',
            'complete_command' => 'prosedur TA lengkap',
            'fallback' => "Syarat dan ketentuan TA meliputi: memenuhi syarat akademik pengajuan Tugas Akhir sesuai ketentuan prodi, tidak memiliki kendala administrasi akademik/keuangan, menyiapkan topik atau judul TA, menyiapkan proposal bila diminta, mendapat persetujuan prodi atau dosen pembimbing, lalu mengikuti bimbingan dan tahapan seminar/sidang sesuai aturan prodi.\n\nUntuk tahapan lengkap, ketik: prosedur TA lengkap"
        ],
    ];

    return $configs[$topic] ?? null;
}

function cyraAnswerProcedureRequirements(mysqli $conn, string $topic): string
{
    $config = cyraRequirementConfig($topic);

    if ($config === null) {
        return cyraFallbackReply();
    }

    return tampilProsedurBagian(
        $conn,
        $config['table'],
        $config['header'],
        cyraRequirementKeywords(),
        $config['fallback'],
        $config['complete_command']
    );
}

function tampilProsedur($conn, $table, $judulHeader, $singkat = false)
{
    $allowedTables = ['prosedur_frs', 'prosedur_kp', 'prosedur_ta'];

    if (!in_array($table, $allowedTables)) {
        return "Data prosedur tidak valid.";
    }

    if (!tableExists($conn, $table)) {
        return defaultProsedurText($table, $judulHeader) ?? "Data $judulHeader belum tersedia.";
    }

    $order = "";

    if (columnExists($conn, $table, 'id_prosedur')) {
        $order = " ORDER BY id_prosedur ASC";
    } elseif (columnExists($conn, $table, 'id_kp')) {
        $order = " ORDER BY id_kp ASC";
    } elseif (columnExists($conn, $table, 'id_ta')) {
        $order = " ORDER BY id_ta ASC";
    } elseif (columnExists($conn, $table, 'id')) {
        $order = " ORDER BY id ASC";
    } elseif (columnExists($conn, $table, 'urutan')) {
        $order = " ORDER BY urutan ASC";
    }

    $q = mysqli_query($conn, "SELECT * FROM `$table` $order");

    if (!$q) {
        return "Query $judulHeader gagal: " . mysqli_error($conn);
    }

    if (mysqli_num_rows($q) == 0) {
        return defaultProsedurText($table, $judulHeader) ?? "Data $judulHeader tidak ditemukan.";
    }

    $text = "$judulHeader:\n\n";
    $no = 1;
    $adaData = false;
    $maxItems = $singkat ? 10 : PHP_INT_MAX;
    $perintahLengkap = [
        'prosedur_frs' => 'prosedur FRS lengkap',
        'prosedur_kp' => 'prosedur KP lengkap',
        'prosedur_ta' => 'prosedur TA lengkap',
    ];

    while ($d = mysqli_fetch_assoc($q)) {
        $judul = trim((string)safeValue($d, ['judul', 'nama_prosedur', 'langkah', 'nama_langkah'], ''));
        $deskripsi = trim((string)safeValue($d, ['deskripsi', 'keterangan', 'isi', 'detail'], ''));
        $judul = cyraNormalizeAnswerText($judul);
        $deskripsi = cyraNormalizeAnswerText($deskripsi);

        if ($judul === '' && $deskripsi === '') {
            continue;
        }

        if ($singkat && isBagianUmumProsedur($judul, $deskripsi)) {
            continue;
        }

        $adaData = true;

        if ($no > $maxItems) {
            $text .= "Untuk melihat semua data, ketik: " . ($perintahLengkap[$table] ?? "$judulHeader lengkap") . "\n";
            break;
        }

        if ($judul !== '' && $deskripsi !== '') {
            $text .= $no++ . ". " . $judul . "\n";
            $text .= "   " . str_replace("\n", "\n   ", $deskripsi) . "\n\n";
        } elseif ($judul !== '') {
            $text .= $no++ . ". " . $judul . "\n\n";
        } else {
            $text .= $no++ . ". " . str_replace("\n", "\n   ", $deskripsi) . "\n\n";
        }
    }

    if (!$adaData) {
        return defaultProsedurText($table, $judulHeader) ?? "$judulHeader belum memiliki data langkah singkat. Silakan isi tabel $table dengan langkah/cara tanpa pengertian dan tujuan.";
    }

    return cyraNormalizeAnswerText($text);
}

function tampilProsedurBagian($conn, $table, $judulHeader, array $keywords, $fallbackText, $completeCommand = 'prosedur KP lengkap')
{
    $allowedTables = ['prosedur_frs', 'prosedur_kp', 'prosedur_ta'];

    if (!in_array($table, $allowedTables)) {
        return "Data prosedur tidak valid.";
    }

    if (!tableExists($conn, $table)) {
        return $fallbackText;
    }

    $order = "";

    if (columnExists($conn, $table, 'id_prosedur')) {
        $order = " ORDER BY id_prosedur ASC";
    } elseif (columnExists($conn, $table, 'id_kp')) {
        $order = " ORDER BY id_kp ASC";
    } elseif (columnExists($conn, $table, 'id_ta')) {
        $order = " ORDER BY id_ta ASC";
    } elseif (columnExists($conn, $table, 'id')) {
        $order = " ORDER BY id ASC";
    } elseif (columnExists($conn, $table, 'urutan')) {
        $order = " ORDER BY urutan ASC";
    }

    $q = mysqli_query($conn, "SELECT * FROM `$table` $order");

    if (!$q) {
        return "Query $judulHeader gagal: " . mysqli_error($conn);
    }

    $text = "$judulHeader:\n\n";
    $no = 1;
    $titleMatches = [];
    $descriptionMatches = [];

    while ($d = mysqli_fetch_assoc($q)) {
        $judul = trim((string)safeValue($d, ['judul', 'nama_prosedur', 'langkah', 'nama_langkah'], ''));
        $deskripsi = trim((string)safeValue($d, ['deskripsi', 'keterangan', 'isi', 'detail'], ''));
        $judulNormalized = normalizeText($judul);
        $deskripsiNormalized = normalizeText($deskripsi);
        $titleMatch = false;
        $descriptionMatch = false;

        foreach ($keywords as $keyword) {
            $normalizedKeyword = normalizeText($keyword);

            if ($normalizedKeyword === '') {
                continue;
            }

            if (strpos($judulNormalized, $normalizedKeyword) !== false) {
                $titleMatch = true;
            }

            if (strpos($deskripsiNormalized, $normalizedKeyword) !== false) {
                $descriptionMatch = true;
            }
        }

        if ((!$titleMatch && !$descriptionMatch) || ($judul === '' && $deskripsi === '')) {
            continue;
        }

        $item = [
            'judul' => $judul,
            'deskripsi' => $deskripsi
        ];

        if ($titleMatch) {
            $titleMatches[] = $item;
        } else {
            $descriptionMatches[] = $item;
        }
    }

    $items = $titleMatches !== [] ? $titleMatches : $descriptionMatches;

    foreach ($items as $item) {
        $judul = $item['judul'];
        $deskripsi = $item['deskripsi'];

        if ($judul !== '' && $deskripsi !== '') {
            $text .= $no++ . ". " . cyraNormalizeAnswerText($judul) . "\n";
            $text .= "   " . str_replace("\n", "\n   ", cyraNormalizeAnswerText($deskripsi)) . "\n\n";
        } elseif ($judul !== '') {
            $text .= $no++ . ". " . cyraNormalizeAnswerText($judul) . "\n\n";
        } else {
            $text .= $no++ . ". " . str_replace("\n", "\n   ", cyraNormalizeAnswerText($deskripsi)) . "\n\n";
        }
    }

    if ($no === 1) {
        return $fallbackText;
    }

    $text .= "Untuk tahapan lengkap, ketik: " . $completeCommand;

    return cyraNormalizeAnswerText($text);
}

/* =========================================================
   MATA KULIAH
========================================================= */
function tampilMataKuliah($conn, $semester = null)
{
    $table = 'mata_kuliah';

    if (!tableExists($conn, $table)) {
        return "Data mata kuliah belum tersedia.";
    }

    $order = orderByExisting($conn, $table, ['semester', 'nama_mata_kuliah', 'mata_kuliah', 'nama_mk', 'nama_matkul']);

    if ($semester !== null && columnExists($conn, $table, 'semester')) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM `$table` WHERE semester = ? $order");

        if (!$stmt) {
            return "Query mata kuliah gagal: " . mysqli_error($conn);
        }

        mysqli_stmt_bind_param($stmt, "i", $semester);
        mysqli_stmt_execute($stmt);
        $q = mysqli_stmt_get_result($stmt);

        $judul = "Daftar Mata Kuliah Semester $semester:\n\n";
        $kosong = "Data mata kuliah semester $semester tidak ditemukan.";
    } else {
        $q = mysqli_query($conn, "SELECT * FROM `$table` $order");

        $judul = "Daftar Semua Mata Kuliah:\n\n";
        $kosong = "Data mata kuliah tidak ditemukan.";
    }

    if (!$q) {
        return "Query mata kuliah gagal: " . mysqli_error($conn);
    }

    if (mysqli_num_rows($q) == 0) {
        return $kosong;
    }

    $text = $judul;

    while ($d = mysqli_fetch_assoc($q)) {
        $mataKuliah = safeValue($d, ['nama_mata_kuliah', 'mata_kuliah', 'nama_mk', 'nama_matkul']);
        $kode = safeValue($d, ['kode_mk', 'kode']);
        $sks = safeValue($d, ['sks']);
        $sem = safeValue($d, ['semester']);

        $text .= "- $mataKuliah\n";
        $text .= "Semester: $sem\n";
        $text .= "Kode: $kode\n";
        $text .= "SKS: $sks\n\n";
    }

    return trim($text);
}

/* =========================================================
   JADWAL KULIAH
========================================================= */
function tampilJadwalKuliah($conn, $semester = null, $hari = null)
{
    $table = 'jadwal_kuliah';

    if (!tableExists($conn, $table)) {
        return "Data jadwal kuliah belum tersedia.";
    }

    $where = [];
    $types = "";
    $values = [];

    if ($semester !== null && columnExists($conn, $table, 'semester')) {
        $where[] = "`semester` = ?";
        $types .= "i";
        $values[] = $semester;
    }

    if ($hari !== null && columnExists($conn, $table, 'hari')) {
        $where[] = "LOWER(REPLACE(`hari`, '''', '')) = ?";
        $types .= "s";
        $values[] = $hari;
    }

    $sql = "SELECT * FROM `$table`";

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= orderByExisting($conn, $table, ['semester', 'hari', 'jam_mulai', 'mata_kuliah', 'nama_mata_kuliah']);

    if (!empty($values)) {
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            return "Query jadwal kuliah gagal: " . mysqli_error($conn);
        }

        bindParams($stmt, $types, $values);
        mysqli_stmt_execute($stmt);
        $q = mysqli_stmt_get_result($stmt);
    } else {
        $q = mysqli_query($conn, $sql);
    }

    if (!$q) {
        return "Query jadwal kuliah gagal: " . mysqli_error($conn);
    }

    if (mysqli_num_rows($q) == 0) {
        if ($semester !== null && $hari !== null) {
            return "Data jadwal kuliah semester $semester hari " . ucfirst($hari) . " tidak ditemukan.";
        }

        if ($semester !== null) {
            return "Data jadwal kuliah semester $semester tidak ditemukan.";
        }

        return "Data jadwal kuliah tidak ditemukan.";
    }

    if ($semester !== null && $hari !== null) {
        $text = "Jadwal Kuliah Semester $semester Hari " . ucfirst($hari) . ":\n\n";
    } elseif ($semester !== null) {
        $text = "Jadwal Kuliah Semester $semester:\n\n";
    } else {
        $text = "Semua Jadwal Kuliah:\n\n";
    }

    while ($d = mysqli_fetch_assoc($q)) {
        $mataKuliah = safeValue($d, ['mata_kuliah', 'nama_mata_kuliah', 'nama_mk', 'nama_matkul']);
        $hariDb = safeValue($d, ['hari']);
        $jamMulai = safeValue($d, ['jam_mulai', 'jam']);
        $jamSelesai = safeValue($d, ['jam_selesai'], '');
        $ruang = safeValue($d, ['ruang', 'ruangan']);
        $sem = safeValue($d, ['semester']);

        $text .= "- $mataKuliah\n";
        $text .= "Semester: $sem\n";
        $text .= "Hari: $hariDb\n";

        if ($jamSelesai !== '') {
            $text .= "Jam: $jamMulai - $jamSelesai\n";
        } else {
            $text .= "Jam: $jamMulai\n";
        }

        $text .= "Ruang: $ruang\n\n";
    }

    return trim($text);
}

function tampilSemesterJadwalKuliahHari($conn, $hari)
{
    $table = 'jadwal_kuliah';
    $hari = normalizeHari($hari);

    if ($hari === null) {
        return "Hari yang dimaksud belum terbaca. Contoh: jadwal hari Senin ada di semester berapa?";
    }

    if (cyraIsHariLiburKuliah($hari)) {
        return cyraJadwalLiburText($hari);
    }

    if (!tableExists($conn, $table)) {
        return "Data jadwal kuliah belum tersedia.";
    }

    if (!columnExists($conn, $table, 'semester') || !columnExists($conn, $table, 'hari')) {
        return "Data jadwal kuliah belum memiliki kolom hari dan semester yang lengkap.";
    }

    $sql = "SELECT semester, COUNT(*) AS jumlah FROM `$table` "
        . "WHERE LOWER(REPLACE(`hari`, '''', '')) = ? "
        . "GROUP BY semester ORDER BY semester ASC";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return "Query semester jadwal hari gagal: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "s", $hari);
    mysqli_stmt_execute($stmt);
    $q = mysqli_stmt_get_result($stmt);

    if (!$q) {
        return "Query semester jadwal hari gagal: " . mysqli_error($conn);
    }

    if (mysqli_num_rows($q) == 0) {
        return "Belum ada jadwal kuliah pada hari " . cyraFormatNamaHari($hari) . ".";
    }

    $semesters = [];
    $details = [];

    while ($row = mysqli_fetch_assoc($q)) {
        $semester = (int)($row['semester'] ?? 0);
        $jumlah = (int)($row['jumlah'] ?? 0);

        if ($semester < 1) {
            continue;
        }

        $semesters[] = $semester;
        $details[] = "- Semester $semester: $jumlah jadwal";
    }

    if ($semesters === []) {
        return "Belum ada jadwal kuliah pada hari " . cyraFormatNamaHari($hari) . ".";
    }

    $text = "Jadwal kuliah hari " . cyraFormatNamaHari($hari) . " tersedia di semester " . implode(', ', $semesters) . ".\n\n";
    $text .= implode("\n", $details);
    $text .= "\n\nKetik salah satu angka semester untuk melihat jadwalnya, atau ketik semua.";

    return cyraNormalizeAnswerText($text);
}

/* =========================================================
   JADWAL UTS / UAS
========================================================= */
function tampilJadwalUjian($conn, $table, $label, $semester = null)
{
    $allowedTables = ['jadwal_uts', 'jadwal_uas'];

    if (!in_array($table, $allowedTables)) {
        return "Data jadwal ujian tidak valid.";
    }

    if (!tableExists($conn, $table)) {
        return "Data jadwal $label belum tersedia.";
    }

    $order = orderByExisting($conn, $table, ['semester', 'tanggal', 'jam_mulai']);

    if ($semester !== null && columnExists($conn, $table, 'semester')) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM `$table` WHERE semester = ? $order");

        if (!$stmt) {
            return "Query jadwal $label gagal: " . mysqli_error($conn);
        }

        mysqli_stmt_bind_param($stmt, "i", $semester);
        mysqli_stmt_execute($stmt);
        $q = mysqli_stmt_get_result($stmt);

        $judul = "Jadwal $label Semester $semester:\n\n";
        $kosong = "Data jadwal $label semester $semester tidak ditemukan.";
    } else {
        $q = mysqli_query($conn, "SELECT * FROM `$table` $order");

        $judul = "Semua Jadwal $label:\n\n";
        $kosong = "Data jadwal $label tidak ditemukan.";
    }

    if (!$q) {
        return "Query jadwal $label gagal: " . mysqli_error($conn);
    }

    if (mysqli_num_rows($q) == 0) {
        return $kosong;
    }

    $text = $judul;

    while ($d = mysqli_fetch_assoc($q)) {
        $mataKuliah = safeValue($d, ['mata_kuliah', 'nama_mata_kuliah', 'nama_mk', 'nama_matkul']);
        $tanggal = formatTanggal(safeValue($d, ['tanggal'], ''));
        $jamMulai = safeValue($d, ['jam_mulai', 'jam']);
        $jamSelesai = safeValue($d, ['jam_selesai'], '');
        $ruang = safeValue($d, ['ruangan', 'ruang']);
        $sem = safeValue($d, ['semester']);

        $text .= "- $mataKuliah\n";
        $text .= "Semester: $sem\n";
        $text .= "Tanggal: $tanggal\n";

        if ($jamSelesai !== '') {
            $text .= "Jam: $jamMulai - $jamSelesai\n";
        } else {
            $text .= "Jam: $jamMulai\n";
        }

        $text .= "Ruang: $ruang\n\n";
    }

    return trim($text);
}

function cyraExtractAcademicCourseKeyword($userText)
{
    $keyword = normalizeText(normalizeTypo($userText));

    $phrases = [
        'jadwal kuliah mata kuliah',
        'jadwal mata kuliah',
        'jadwal kuliah',
        'jadwal uas mata kuliah',
        'jadwal uts mata kuliah',
        'jadwal uas untuk mata kuliah',
        'jadwal uts untuk mata kuliah',
        'mata kuliah',
        'matakuliah',
        'mk',
        'di ruangan mana kuliah',
        'di ruang mana kuliah',
        'ruangan mana kuliah',
        'ruang mana kuliah',
        'kuliah',
        'dilaksanakan',
        'dilaksanakannya',
        'berlangsung',
        'kapan',
        'dimana',
        'di mana',
        'ruangan',
        'ruang',
        'kelas',
        'untuk',
        'hari ini',
        'sekarang',
        'today',
        'ada',
        'apakah ada',
        'semester ini',
        'semester genap',
        'semester ganjil'
    ];

    foreach ($phrases as $phrase) {
        $keyword = str_replace($phrase, ' ', $keyword);
    }

    $keyword = preg_replace('/\bsemester\s*[1-9]\b/', ' ', $keyword);
    $keyword = preg_replace('/\b[1-9]\b/', ' ', $keyword);
    $keyword = preg_replace('/\s+/', ' ', $keyword);

    return normalizeCourseAlias(trim($keyword));
}

function cyraIsCourseScheduleQuestion($userText): bool
{
    $text = normalizeText(normalizeTypo($userText));

    if (containsAny($text, ['hari apa saja', 'hari apa aja'])) {
        return false;
    }

    if (containsAny($text, ['hari ini', 'sekarang', 'today']) && !containsAny($text, ['mata kuliah', 'matakuliah', 'mk'])) {
        return false;
    }

    if (!containsAny($text, ['jadwal', 'kapan', 'hari apa', 'jam berapa'])) {
        return false;
    }

    if (!containsAny($text, ['kuliah', 'mata kuliah', 'matakuliah', 'mk'])) {
        return false;
    }

    $keyword = cyraExtractAcademicCourseKeyword($text);

    return $keyword !== '' && !containsAny($keyword, ['informatika', 'teknik informatika', 'semester']);
}

function cyraIsCourseRoomQuestion($userText): bool
{
    $text = normalizeText(normalizeTypo($userText));

    if (!containsAny($text, ['ruang', 'ruangan', 'kelas'])) {
        return false;
    }

    if (!containsAny($text, ['kuliah', 'mata kuliah', 'matakuliah', 'mk'])) {
        return false;
    }

    $keyword = cyraExtractAcademicCourseKeyword($text);

    return $keyword !== '' && !containsAny($keyword, ['mana', 'dimana', 'di mana']);
}

function cyraIsTaScheduleQuestion($userText): bool
{
    $text = normalizeText(normalizeTypo($userText));

    return containsAny($text, [
        'jadwal ta',
        'ta kapan',
        'kapan ta',
        'jadwal tugas akhir',
        'tugas akhir kapan',
        'skripsi kapan'
    ]) || (
        containsAny($text, ['ta', 'tugas akhir', 'skripsi']) &&
        containsAny($text, ['kapan', 'jadwal', 'tanggal', 'jam berapa'])
    );
}

function cyraTaScheduleAnswer($conn): string
{
    $answer = tampilJadwalKuliahMataKuliah($conn, 'Tugas Akhir', 8);

    return "TA ada di semester 8.\n\n" . $answer;
}

function cyraFormatScheduleRows(array $rows, string $title): string
{
    if ($rows === []) {
        return '';
    }

    $text = $title . ":\n\n";
    $seen = [];
    $no = 1;

    foreach ($rows as $row) {
        $mataKuliah = safeValue($row, ['mata_kuliah', 'nama_mata_kuliah', 'nama_mk', 'nama_matkul']);
        $hari = cyraFormatNamaHari(safeValue($row, ['hari'], ''));
        $jamMulai = cyraFormatJamKuliah(safeValue($row, ['jam_mulai', 'jam'], ''));
        $jamSelesai = cyraFormatJamKuliah(safeValue($row, ['jam_selesai'], ''));
        $ruang = safeValue($row, ['ruang', 'ruangan']);
        $semester = safeValue($row, ['semester']);
        $dosen = cyraDosenDisplay(safeValue($row, ['dosen', 'nama_dosen'], ''));
        $jam = $jamSelesai !== '-' ? "$jamMulai-$jamSelesai" : $jamMulai;
        $key = normalizeText($mataKuliah . '|' . $semester . '|' . $hari . '|' . $jam . '|' . $ruang);

        if (isset($seen[$key])) {
            continue;
        }

        $seen[$key] = true;
        $text .= $no++ . ". $mataKuliah - Semester $semester\n";
        $text .= "   Hari: $hari\n";
        $text .= "   Jam: $jam\n";
        $text .= "   Ruang: $ruang\n";
        $text .= "   Dosen: $dosen\n\n";
    }

    return cyraNormalizeAnswerText($text);
}

function tampilJadwalKuliahMataKuliah($conn, $userText, $semester = null)
{
    $keyword = cyraExtractAcademicCourseKeyword($userText);

    if ($keyword === '') {
        return "Mata kuliah apa yang ingin dicek jadwalnya? Contoh: jadwal kuliah Basis Data.";
    }

    $rows = cyraFindJadwalRowsByMataKuliahKeyword($conn, $keyword, $semester);

    if ($rows === []) {
        $semesterText = $semester !== null ? " semester $semester" : "";
        return "Jadwal kuliah untuk mata kuliah $keyword$semesterText belum tersedia.";
    }

    $title = $semester !== null
        ? "Jadwal kuliah $keyword semester $semester"
        : "Jadwal kuliah $keyword";

    return cyraFormatScheduleRows($rows, $title);
}

function tampilRuangMataKuliah($conn, $userText, $semester = null)
{
    $keyword = cyraExtractAcademicCourseKeyword($userText);

    if ($keyword === '') {
        return "Mata kuliah apa yang ingin dicek ruangnya? Contoh: ruang kuliah Basis Data.";
    }

    $rows = cyraFindJadwalRowsByMataKuliahKeyword($conn, $keyword, $semester);

    if ($rows === []) {
        return "Ruang kuliah untuk mata kuliah $keyword belum tersedia.";
    }

    $text = "Ruang kuliah $keyword:\n\n";
    $seen = [];
    $no = 1;

    foreach ($rows as $row) {
        $mataKuliah = safeValue($row, ['mata_kuliah', 'nama_mata_kuliah', 'nama_mk', 'nama_matkul']);
        $semesterRow = safeValue($row, ['semester']);
        $hari = cyraFormatNamaHari(safeValue($row, ['hari'], ''));
        $jamMulai = cyraFormatJamKuliah(safeValue($row, ['jam_mulai', 'jam'], ''));
        $jamSelesai = cyraFormatJamKuliah(safeValue($row, ['jam_selesai'], ''));
        $ruang = safeValue($row, ['ruang', 'ruangan']);
        $jam = $jamSelesai !== '-' ? "$jamMulai-$jamSelesai" : $jamMulai;
        $key = normalizeText($mataKuliah . '|' . $semesterRow . '|' . $hari . '|' . $jam . '|' . $ruang);

        if (isset($seen[$key])) {
            continue;
        }

        $seen[$key] = true;
        $text .= $no++ . ". $mataKuliah - Semester $semesterRow - $hari, $jam - Ruang: $ruang\n";
    }

    return cyraNormalizeAnswerText($text);
}

function cyraIsHariApaSajaKuliahQuestion($userText): bool
{
    return containsAny($userText, ['hari apa saja', 'hari apa aja', 'hari apa']) &&
        containsAny($userText, ['kuliah', 'jadwal kuliah', 'perkuliahan']) &&
        extractSemester($userText) !== null;
}

function tampilHariKuliahSemester($conn, $semester): string
{
    $table = 'jadwal_kuliah';

    if (!tableExists($conn, $table)) {
        return "Data jadwal kuliah belum tersedia.";
    }

    $stmt = mysqli_prepare($conn, "SELECT hari, COUNT(*) AS jumlah FROM `$table` WHERE semester = ? GROUP BY hari ORDER BY FIELD(LOWER(hari), 'senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'), hari");

    if (!$stmt) {
        return "Query hari kuliah gagal: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "i", $semester);
    mysqli_stmt_execute($stmt);
    $q = mysqli_stmt_get_result($stmt);

    if (!$q || mysqli_num_rows($q) === 0) {
        return "Data hari kuliah semester $semester tidak ditemukan.";
    }

    $parts = [];
    $details = [];

    while ($row = mysqli_fetch_assoc($q)) {
        $hari = cyraFormatNamaHari($row['hari'] ?? '');
        $jumlah = (int)($row['jumlah'] ?? 0);
        $parts[] = $hari;
        $details[] = "- $hari: $jumlah jadwal";
    }

    $text = "Kuliah semester $semester ada pada hari " . implode(', ', $parts) . ".\n\n";
    $text .= implode("\n", $details);

    return cyraNormalizeAnswerText($text);
}

function cyraIsScheduleChangeQuestion($userText): bool
{
    return cyraIsAcademicScheduleQuestion($userText) &&
        containsAny($userText, ['perubahan', 'berubah', 'diubah', 'update jadwal', 'minggu ini']);
}

function cyraScheduleChangeAnswer(): string
{
    return "CYRA belum memiliki data khusus perubahan jadwal kuliah minggu ini. Silakan cek pengumuman resmi prodi/admin akademik. Untuk melihat jadwal yang tersimpan di CYRA, ketik: jadwal kuliah semester 6 atau jadwal kuliah semua.";
}

function cyraIsExamCourseScheduleQuestion($userText, string $label): bool
{
    $text = normalizeText(normalizeTypo($userText));

    if (!containsAny($text, [strtolower($label)])) {
        return false;
    }

    return containsAny($text, ['mata kuliah', 'matakuliah', 'mk']);
}

function cyraExamTableForLabel(string $label): string
{
    return strtolower($label) === 'uts' ? 'jadwal_uts' : 'jadwal_uas';
}

function tampilJadwalUjianMataKuliah($conn, $table, $label, $userText, $semester = null)
{
    $allowedTables = ['jadwal_uts', 'jadwal_uas'];

    if (!in_array($table, $allowedTables, true)) {
        return "Data jadwal ujian tidak valid.";
    }

    if (!tableExists($conn, $table)) {
        return "Data jadwal $label belum tersedia.";
    }

    $keyword = cyraExtractAcademicCourseKeyword($userText);

    if ($keyword === '') {
        return "Mata kuliah apa yang ingin dicek jadwal $label-nya? Contoh: jadwal $label mata kuliah Basis Data.";
    }

    $sql = "SELECT * FROM `$table`";
    $where = [];
    $types = "";
    $values = [];

    if ($semester !== null && columnExists($conn, $table, 'semester')) {
        if ($semester === 'ganjil') {
            $where[] = "MOD(`semester`, 2) = 1";
        } elseif ($semester === 'genap') {
            $where[] = "MOD(`semester`, 2) = 0";
        } else {
            $where[] = "`semester` = ?";
            $types .= "i";
            $values[] = $semester;
        }
    }

    if ($where !== []) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= orderByExisting($conn, $table, ['semester', 'tanggal', 'jam_mulai', 'mata_kuliah']);

    if ($values !== []) {
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            return "Query jadwal $label gagal: " . mysqli_error($conn);
        }

        bindParams($stmt, $types, $values);
        mysqli_stmt_execute($stmt);
        $q = mysqli_stmt_get_result($stmt);
    } else {
        $q = mysqli_query($conn, $sql);
    }

    if (!$q) {
        return "Query jadwal $label gagal: " . mysqli_error($conn);
    }

    $rows = [];

    while ($row = mysqli_fetch_assoc($q)) {
        $mataKuliah = safeValue($row, ['mata_kuliah', 'nama_mata_kuliah', 'nama_mk', 'nama_matkul']);

        if (cyraCourseMatchScore($keyword, $mataKuliah) >= 72) {
            $rows[] = $row;
        }
    }

    if ($rows === []) {
        $semesterText = $semester !== null ? " semester $semester" : "";
        return "Jadwal $label untuk mata kuliah $keyword$semesterText belum tersedia.";
    }

    $title = $semester !== null
        ? "Jadwal $label $keyword semester $semester"
        : "Jadwal $label $keyword";
    $text = $title . ":\n\n";
    $no = 1;

    foreach ($rows as $row) {
        $mataKuliah = safeValue($row, ['mata_kuliah', 'nama_mata_kuliah', 'nama_mk', 'nama_matkul']);
        $tanggal = formatTanggal(safeValue($row, ['tanggal'], ''));
        $jamMulai = safeValue($row, ['jam_mulai', 'jam']);
        $jamSelesai = safeValue($row, ['jam_selesai'], '');
        $ruang = safeValue($row, ['ruangan', 'ruang']);
        $semesterRow = safeValue($row, ['semester']);

        $text .= $no++ . ". $mataKuliah - Semester $semesterRow\n";
        $text .= "   Tanggal: $tanggal\n";
        $text .= $jamSelesai !== ''
            ? "   Jam: $jamMulai - $jamSelesai\n"
            : "   Jam: $jamMulai\n";
        $text .= "   Ruang: $ruang\n\n";
    }

    return cyraNormalizeAnswerText($text);
}

function cyraIsExamPublishedQuestion($userText, string $label): bool
{
    return containsAny($userText, [strtolower($label)]) &&
        containsAny($userText, ['sudah diumumkan', 'sudah keluar', 'sudah ada', 'diumumkan']);
}

function cyraExamPublishedAnswer($conn, string $table, string $label): string
{
    if (!tableExists($conn, $table)) {
        return "Data jadwal $label belum tersedia di CYRA.";
    }

    $q = mysqli_query($conn, "SELECT COUNT(*) AS jumlah FROM `$table`");
    $row = $q ? mysqli_fetch_assoc($q) : null;
    $jumlah = (int)($row['jumlah'] ?? 0);

    if ($jumlah < 1) {
        return "Jadwal $label belum tersedia di CYRA.";
    }

    return "Jadwal $label sudah tersedia di CYRA. Untuk melihat detailnya, ketik: jadwal $label semester 6 atau jadwal $label semua.";
}

function cyraIsWhereToSeeExamScheduleQuestion($userText, string $label): bool
{
    return containsAny($userText, [strtolower($label)]) &&
        containsAny($userText, ['di mana', 'dimana', 'lihat', 'melihat', 'cek', 'akses']);
}

function cyraWhereToSeeExamScheduleAnswer(string $label): string
{
    return "Jadwal $label bisa dilihat melalui CYRA jika datanya sudah tersedia. Ketik: jadwal $label semester 6, jadwal $label semua, atau jadwal $label mata kuliah tertentu.";
}

/* =========================================================
   DOSEN
========================================================= */
function keywordPencarianDosen($userText)
{
    $keyword = normalizeText($userText);
    $keyword = str_replace(
        [
            'info dosen',
            'informasi dosen',
            'data dosen',
            'email dosen',
            'nomor hp dosen',
            'no hp dosen',
            'daftar dosen',
            'siapa dosen',
            'dosen pengampu',
            'bapak dosen',
            'ibu dosen',
            'pak dosen',
            'bu dosen',
            'siapa',
            'bapak',
            'ibu',
            'pak',
            'bu',
            'dosen'
        ],
        '',
        $keyword
    );

    return trim(preg_replace('/\s+/', ' ', $keyword));
}

function formatDataDosen($row)
{
    $namaDosen = safeValue($row, ['nama_dosen', 'nama_pegawai', 'nama']);
    $nidn = safeValue($row, ['nidn', 'nidn_nip', 'nip']);
    $email = safeValue($row, ['email']);
    $keahlian = safeValue($row, ['keahlian']);
    $telepon = safeValue($row, ['telepon', 'no_hp', 'hp']);

    $text = "Data Dosen:\n\n";
    $text .= "Nama: $namaDosen\n";
    $text .= "NIDN/NIP: $nidn\n";
    $text .= "Email: $email\n";
    $text .= "Keahlian: $keahlian\n";
    $text .= "Telepon: $telepon";

    return cyraNormalizeAnswerText($text);
}

function cyraFormatJamKuliah($jam)
{
    $jam = trim((string)$jam);

    if ($jam === '' || $jam === '-') {
        return '-';
    }

    if (preg_match('/^\d{2}:\d{2}/', $jam, $m)) {
        return $m[0];
    }

    $timestamp = strtotime($jam);
    return $timestamp ? date('H:i', $timestamp) : $jam;
}

function cyraFormatNamaHari($hari)
{
    $hari = normalizeHari((string)$hari) ?? normalizeText($hari);
    return $hari !== '' ? ucfirst($hari) : '-';
}

function cyraJadwalLine($row, $nomor)
{
    $mataKuliah = safeValue($row, ['mata_kuliah', 'nama_mata_kuliah', 'nama_mk', 'nama_matkul']);
    $dosen = safeValue($row, ['dosen', 'nama_dosen']);
    $hari = cyraFormatNamaHari(safeValue($row, ['hari'], ''));
    $jamMulai = cyraFormatJamKuliah(safeValue($row, ['jam_mulai', 'jam'], ''));
    $jamSelesai = cyraFormatJamKuliah(safeValue($row, ['jam_selesai'], ''));
    $ruang = safeValue($row, ['ruang', 'ruangan']);
    $semester = safeValue($row, ['semester']);
    $jam = $jamSelesai !== '-' ? "$jamMulai-$jamSelesai" : $jamMulai;

    return $nomor . ". $hari, $jam - $mataKuliah - Semester $semester - Dosen: $dosen - Ruang: $ruang";
}

function cyraJadwalLiburText($hari)
{
    $hari = cyraFormatNamaHari($hari);
    return "Tidak ada jadwal kuliah pada hari $hari karena hari $hari libur.";
}

function cyraIsHariLiburKuliah($hari)
{
    $hari = normalizeHari($hari);
    return in_array($hari, ['kamis', 'jumat'], true);
}

function cyraIsPertanyaanDosenMataKuliah($userText)
{
    $text = normalizeText(normalizeTypo($userText));

    if (cyraIsPertanyaanDaftarDosen($text) && !containsAny($text, ['dosennya siapa', 'dosen mata kuliah', 'dosen pengampu'])) {
        return false;
    }

    if (containsAny($text, [
        'siapa dosen',
        'dosen mata kuliah',
        'dosen pengampu',
        'pengampu mata kuliah',
        'pengajar mata kuliah',
        'pengajar',
        'dosennya siapa',
        'dosen nya siapa',
        'siapa pengajar'
    ])) {
        return true;
    }

    if (preg_match('/^dosen\s+(.+)$/', $text, $m)) {
        $keyword = cyraExtractMataKuliahKeyword($text);

        return $keyword !== '' && (
            str_word_count($keyword) >= 2 ||
            containsAny($keyword, [
                'grafik',
                'komputer',
                'basis data',
                'kerja praktek',
                'tugas akhir',
                'e bisnis',
                'audit',
                'sistem informasi',
                'pemrograman',
                'metodologi',
                'technopreneurship',
                'interaksi'
            ])
        );
    }

    if (
        preg_match('/\bdosen\b.+\bsiapa\b/', $text) ||
        preg_match('/\bsiapa\b.+\bdosen\b/', $text)
    ) {
        $keyword = cyraExtractMataKuliahKeyword($text);
        return $keyword !== '' && !containsAny($keyword, ['informatika', 'prodi', 'program studi']);
    }

    return false;
}

function normalizeCourseAlias($text)
{
    $text = normalizeText(normalizeTypo($text));
    $aliases = [
        'pemweb' => 'pemrograman berbasis web',
        'pemrograman web' => 'pemrograman berbasis web',
        'web programming' => 'pemrograman berbasis web',
        'pemrograman berbasis web' => 'pemrograman berbasis web',
        'basisdata' => 'basis data',
        'basis data' => 'basis data',
        'iot' => 'iot',
        'mobile computing' => 'mobile computing',
        'mobile' => 'mobile computing'
    ];

    foreach ($aliases as $from => $to) {
        if (strpos($text, $from) !== false) {
            return $to;
        }
    }

    return trim($text);
}

function cyraExtractMataKuliahKeyword($userText)
{
    $keyword = normalizeText(normalizeTypo($userText));

    $phrases = [
        'siapa dosen mata kuliah',
        'siapa dosen pengampu mata kuliah',
        'siapa dosen pengampu',
        'dosen mata kuliah',
        'dosen pengampu mata kuliah',
        'dosen pengampu',
        'pengampu mata kuliah',
        'pengajar mata kuliah',
        'dosennya siapa',
        'dosen nya siapa',
        'dosennya',
        'mata kuliah',
        'siapa saja',
        'siapa dosen',
        'siapa',
        'dosen',
        'pengampu',
        'pengajar',
        'yang mengajar',
        'mengajar',
        'diampu',
        'ampu',
        'adalah',
        'itu'
    ];

    foreach ($phrases as $phrase) {
        $keyword = str_replace($phrase, ' ', $keyword);
    }

    $keyword = preg_replace('/\bsemester\s*[1-8]\b/', ' ', $keyword);
    $keyword = preg_replace('/\b[1-8]\b/', ' ', $keyword);
    $keyword = preg_replace('/\s+/', ' ', $keyword);

    return normalizeCourseAlias(trim($keyword));
}

function cyraLikeParam($value)
{
    return '%' . $value . '%';
}

function cyraCourseWords($text)
{
    $words = preg_split('/\s+/', normalizeText($text), -1, PREG_SPLIT_NO_EMPTY);
    $ignore = array_flip(['mata', 'kuliah', 'mk', 'matkul', 'berbasis', 'dasar', 'dasar-dasar']);
    $result = [];

    foreach ($words as $word) {
        if (strlen($word) < 2 || isset($ignore[$word])) {
            continue;
        }

        $result[$word] = true;
    }

    return array_keys($result);
}

function cyraIsDosenBelumDitentukan($dosen)
{
    $dosen = normalizeText((string)$dosen);
    return $dosen === '' || in_array($dosen, [
        '-',
        'null',
        'belum ditentukan',
        'dosen belum ditentukan',
        'belum tersedia',
        'tidak ada'
    ], true);
}

function cyraDosenDisplay($dosen)
{
    return cyraIsDosenBelumDitentukan($dosen) ? 'Dosen belum ditentukan' : trim((string)$dosen);
}

function cyraCourseMatchScore($keyword, $courseName)
{
    $keyword = normalizeText($keyword);
    $courseName = normalizeText($courseName);

    if ($keyword === '' || $courseName === '') {
        return 0;
    }

    if ($keyword === $courseName) {
        return 100;
    }

    if (strpos($courseName, $keyword) !== false || strpos($keyword, $courseName) !== false) {
        return 92;
    }

    $keywordWords = cyraCourseWords($keyword);
    $courseWords = cyraCourseWords($courseName);

    if ($keywordWords === [] || $courseWords === []) {
        return 0;
    }

    $matches = array_intersect($keywordWords, $courseWords);
    $coverageKeyword = count($matches) / count($keywordWords);
    $coverageCourse = count($matches) / count($courseWords);
    $score = ($coverageKeyword * 75) + ($coverageCourse * 25);

    if (count($matches) >= 2) {
        $score += 10;
    }

    return min(100, $score);
}

function cyraBestMataKuliahName($conn, $keyword)
{
    if (!tableExists($conn, 'mata_kuliah')) {
        return null;
    }

    $nameColumn = null;

    foreach (['nama_mata_kuliah', 'mata_kuliah', 'nama_mk', 'nama_matkul'] as $column) {
        if (columnExists($conn, 'mata_kuliah', $column)) {
            $nameColumn = $column;
            break;
        }
    }

    if ($nameColumn === null) {
        return null;
    }

    $q = mysqli_query($conn, "SELECT `$nameColumn` AS nama_mata_kuliah FROM `mata_kuliah`");

    if (!$q) {
        return null;
    }

    $bestName = null;
    $bestScore = 0;

    while ($row = mysqli_fetch_assoc($q)) {
        $name = $row['nama_mata_kuliah'] ?? '';
        $score = cyraCourseMatchScore($keyword, $name);

        if ($score > $bestScore) {
            $bestScore = $score;
            $bestName = $name;
        }
    }

    return $bestScore >= 72 ? $bestName : null;
}

function cyraFindJadwalRowsByMataKuliahKeyword($conn, $keyword, $semester = null)
{
    $table = 'jadwal_kuliah';
    $keyword = normalizeCourseAlias($keyword);
    $sql = "SELECT mata_kuliah, dosen, semester, hari, jam_mulai, jam_selesai, ruang FROM `$table`";
    $where = [];
    $types = "";
    $values = [];

    if ($keyword !== '') {
        $where[] = "LOWER(`mata_kuliah`) LIKE ?";
        $types .= "s";
        $values[] = cyraLikeParam($keyword);
    }

    if ($semester !== null && columnExists($conn, $table, 'semester')) {
        if ($semester === 'ganjil') {
            $where[] = "MOD(`semester`, 2) = 1";
        } elseif ($semester === 'genap') {
            $where[] = "MOD(`semester`, 2) = 0";
        } else {
            $where[] = "`semester` = ?";
            $types .= "i";
            $values[] = $semester;
        }
    }

    if ($where !== []) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY semester ASC, mata_kuliah ASC";

    if ($values !== []) {
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            return [];
        }

        bindParams($stmt, $types, $values);
        mysqli_stmt_execute($stmt);
        $q = mysqli_stmt_get_result($stmt);
    } else {
        $q = mysqli_query($conn, $sql);
    }

    if (!$q) {
        return [];
    }

    $rows = [];

    while ($row = mysqli_fetch_assoc($q)) {
        $rows[] = $row;
    }

    if ($rows !== []) {
        return $rows;
    }

    $sql = "SELECT mata_kuliah, dosen, semester, hari, jam_mulai, jam_selesai, ruang FROM `$table`";

    if ($semester !== null && columnExists($conn, $table, 'semester')) {
        $stmt = mysqli_prepare($conn, $sql . " WHERE `semester` = ? ORDER BY semester ASC, mata_kuliah ASC");

        if (!$stmt) {
            return [];
        }

        mysqli_stmt_bind_param($stmt, "i", $semester);
        mysqli_stmt_execute($stmt);
        $q = mysqli_stmt_get_result($stmt);
    } else {
        $q = mysqli_query($conn, $sql . " ORDER BY semester ASC, mata_kuliah ASC");
    }

    if (!$q) {
        return [];
    }

    while ($row = mysqli_fetch_assoc($q)) {
        $mataKuliah = safeValue($row, ['mata_kuliah', 'nama_mata_kuliah', 'nama_mk', 'nama_matkul']);

        if (cyraCourseMatchScore($keyword, $mataKuliah) >= 72) {
            $rows[] = $row;
        }
    }

    return $rows;
}

function cyraFormatBelumAdaDosenPengampu($mataKuliah, $semester = null)
{
    $semesterText = ($semester !== null && $semester !== '' && $semester !== '-')
        ? " pada Semester $semester"
        : "";

    return "Mata kuliah $mataKuliah ditemukan$semesterText, tetapi dosen pengampunya belum ditentukan.";
}

function tampilDosenMataKuliah($conn, $userText, $params = [])
{
    $table = 'jadwal_kuliah';

    if (!tableExists($conn, $table)) {
        return cyraDatabaseErrorReply();
    }

    $keyword = cyraExtractMataKuliahKeyword($userText);

    if ($keyword === '') {
        return "Mata kuliah apa yang ingin dicek dosen pengampunya? Contoh: siapa dosen mata kuliah Pemrograman Web?";
    }

    $semester = extractSemester($userText, $params);
    $matchedScheduleRows = cyraFindJadwalRowsByMataKuliahKeyword($conn, $keyword, $semester);

    if ($matchedScheduleRows === []) {
        $bestCourseName = cyraBestMataKuliahName($conn, $keyword);

        if ($bestCourseName !== null) {
            return cyraFormatBelumAdaDosenPengampu($bestCourseName);
        }

        return "Maaf, CYRA belum menemukan mata kuliah tersebut.";
    }

    $rows = [];

    foreach ($matchedScheduleRows as $row) {
        $mataKuliah = safeValue($row, ['mata_kuliah', 'nama_mata_kuliah', 'nama_mk', 'nama_matkul']);
        $dosen = safeValue($row, ['dosen', 'nama_dosen'], '');
        $semesterRow = safeValue($row, ['semester'], '');
        $key = normalizeText($mataKuliah . '|' . $dosen . '|' . $semesterRow);
        $rows[$key] = [
            'mata_kuliah' => $mataKuliah,
            'dosen' => $dosen,
            'semester' => $semesterRow
        ];
    }

    $rows = array_values($rows);

    if (count($rows) === 1) {
        $row = $rows[0];
        if (cyraIsDosenBelumDitentukan($row['dosen'])) {
            return cyraFormatBelumAdaDosenPengampu($row['mata_kuliah'], $row['semester']);
        }

        return "Dosen pengampu mata kuliah " . $row['mata_kuliah'] . " adalah Bapak/Ibu " . $row['dosen'] . ".";
    }

    $text = "Ditemukan beberapa mata kuliah yang cocok:\n\n";

    foreach ($rows as $index => $row) {
        $text .= ($index + 1) . ". " . $row['mata_kuliah'] . " - Semester " . $row['semester'] . " - Dosen: " . cyraDosenDisplay($row['dosen']) . "\n";
    }

    return trim($text);
}

function cyraExtractDosenKeywordUntukMataKuliah($userText)
{
    $keyword = normalizeText(normalizeTypo($userText));

    $phrases = [
        'mata kuliah yang diampu oleh',
        'mata kuliah yang diajar oleh',
        'mata kuliah yang mengajar',
        'mata kuliah dosen',
        'mata kuliah pak',
        'mata kuliah bu',
        'matakuliah',
        'mata kuliah',
        'daftar mata kuliah',
        'mk',
        'matkul',
        'dosen',
        'pengampu',
        'mengajar',
        'diajar',
        'diampu',
        'oleh',
        'pak',
        'bu',
        'bapak',
        'ibu',
        'semester',
        'apa saja',
        'yang ada di',
        'yang tersedia',
        'yang ada',
        'daftar',
        'list'
    ];

    foreach ($phrases as $phrase) {
        $keyword = str_replace($phrase, ' ', $keyword);
    }

    $keyword = preg_replace('/\bsemester\s*[1-8]\b/', ' ', $keyword);
    $keyword = preg_replace('/\b[1-8]\b/', ' ', $keyword);
    $keyword = preg_replace('/\s+/', ' ', $keyword);

    return trim($keyword);
}

function cyraIsPertanyaanMataKuliahDosen($userText): bool
{
    $text = normalizeText(normalizeTypo($userText));

    if (!containsAny($text, ['siapa', 'dosen', 'pengampu', 'pengajar', 'diajar', 'diampu', 'mengajar', 'pak', 'bu', 'bapak', 'ibu'])) {
        return false;
    }

    if (!containsAny($text, ['mata kuliah', 'matakuliah', 'matkul', 'mk'])) {
        return false;
    }

    if (containsAny($text, ['semester', 'semua', 'daftar semua', 'tersedia']) && cyraExtractDosenKeywordUntukMataKuliah($text) === '') {
        return false;
    }

    return cyraExtractDosenKeywordUntukMataKuliah($text) !== '';
}

function cyraBestDosenRow($conn, string $keyword, int $minimumScore = 55): ?array
{
    $table = 'dosen';

    if (!tableExists($conn, $table)) {
        return null;
    }

    $order = orderByExisting($conn, $table, ['nama_dosen', 'nama_pegawai', 'nama']);
    $q = mysqli_query($conn, "SELECT * FROM `$table` $order");

    if (!$q || mysqli_num_rows($q) == 0) {
        return null;
    }

    $bestRow = null;
    $bestScore = 0;

    while ($d = mysqli_fetch_assoc($q)) {
        $namaDosen = safeValue($d, ['nama_dosen', 'nama_pegawai', 'nama']);
        $score = scoreKemiripanNamaDosen($keyword, $namaDosen);

        if ($score > $bestScore) {
            $bestScore = $score;
            $bestRow = $d;
        }
    }

    return ($bestRow && $bestScore >= $minimumScore) ? $bestRow : null;
}

function cyraFindJadwalRowsByDosenKeyword($conn, string $keyword): array
{
    $table = 'jadwal_kuliah';

    if (!tableExists($conn, $table)) {
        return [];
    }

    $sql = "SELECT mata_kuliah, dosen, semester, hari, jam_mulai, jam_selesai, ruang FROM `$table`"
        . orderByExisting($conn, $table, ['semester', 'mata_kuliah', 'hari', 'jam_mulai']);
    $q = mysqli_query($conn, $sql);

    if (!$q) {
        return [];
    }

    $rows = [];
    $seen = [];

    while ($row = mysqli_fetch_assoc($q)) {
        $dosen = safeValue($row, ['dosen', 'nama_dosen'], '');

        if (cyraIsDosenBelumDitentukan($dosen) || scoreKemiripanNamaDosen($keyword, $dosen) < 55) {
            continue;
        }

        $mataKuliah = safeValue($row, ['mata_kuliah', 'nama_mata_kuliah', 'nama_mk', 'nama_matkul']);
        $semester = safeValue($row, ['semester'], '');
        $key = normalizeText($mataKuliah . '|' . $semester . '|' . $dosen);

        if (isset($seen[$key])) {
            continue;
        }

        $seen[$key] = true;
        $rows[] = $row;
    }

    return $rows;
}

function tampilMataKuliahDosen($conn, $userText): string
{
    $keyword = cyraExtractDosenKeywordUntukMataKuliah($userText);

    if ($keyword === '') {
        return "Nama dosennya siapa? Contoh: mata kuliah Taufiqur Rohman.";
    }

    $rows = cyraFindJadwalRowsByDosenKeyword($conn, $keyword);
    $bestDosen = cyraBestDosenRow($conn, $keyword);
    $namaDosen = $bestDosen ? safeValue($bestDosen, ['nama_dosen', 'nama_pegawai', 'nama']) : strtoupper($keyword);

    if ($rows === []) {
        return "Belum ada mata kuliah yang tercatat di jadwal kuliah untuk Bapak/Ibu $namaDosen.";
    }

    $text = "Mata kuliah yang diampu Bapak/Ibu $namaDosen:\n\n";

    foreach ($rows as $index => $row) {
        $mataKuliah = safeValue($row, ['mata_kuliah', 'nama_mata_kuliah', 'nama_mk', 'nama_matkul']);
        $semester = safeValue($row, ['semester']);
        $hari = cyraFormatNamaHari(safeValue($row, ['hari'], ''));
        $jamMulai = cyraFormatJamKuliah(safeValue($row, ['jam_mulai', 'jam'], ''));
        $jamSelesai = cyraFormatJamKuliah(safeValue($row, ['jam_selesai'], ''));
        $ruang = safeValue($row, ['ruang', 'ruangan']);
        $jam = $jamSelesai !== '-' ? "$jamMulai-$jamSelesai" : $jamMulai;

        $text .= ($index + 1) . ". $mataKuliah - Semester $semester\n";
        $text .= "   Hari: $hari\n";
        $text .= "   Jam: $jam\n";
        $text .= "   Ruang: $ruang\n\n";
    }

    return cyraNormalizeAnswerText($text);
}

function cyraExtractRuangKeyword($userText)
{
    $text = cleanText(normalizeTypo($userText));

    if (preg_match('/\b(?:di\s+)?(?:ruang|ruangan|kelas)\s+([a-z0-9][a-z0-9\s.\-]*)/i', $text, $m)) {
        $ruang = trim($m[1]);
        $ruang = preg_replace('/\b(mana|dimana|di mana|jadwal|kuliah|mata|hari|semester|semua|ada|tidak|nggak|gak|senin|selasa|rabu|kamis|jumat|sabtu|minggu|dilaksanakan|berlangsung)\b/', ' ', $ruang);
        $ruang = preg_replace('/\s+/', ' ', $ruang);
        $ruang = trim($ruang, " \t\n\r\0\x0B.-");

        if ($ruang === '' || preg_match('/^[1-8]$/', $ruang)) {
            return '';
        }

        return $ruang;
    }

    return '';
}

function cyraIsPertanyaanRuang($userText)
{
    return cyraExtractRuangKeyword($userText) !== '';
}

function tampilJadwalKuliahRuang($conn, $userText)
{
    $table = 'jadwal_kuliah';

    if (!tableExists($conn, $table)) {
        return "Data jadwal kuliah belum tersedia.";
    }

    $ruang = cyraExtractRuangKeyword($userText);

    if ($ruang === '') {
        return "Ruang mana yang ingin dicek jadwal kuliahnya? Contoh: jadwal kuliah di ruang A1.";
    }

    $ruangKey = preg_replace('/[^a-z0-9]/', '', normalizeText($ruang));

    if ($ruangKey === '') {
        return "Ruang mana yang ingin dicek jadwal kuliahnya? Contoh: jadwal kuliah di ruang A1.";
    }

    $like = cyraLikeParam($ruangKey);
    $sql = "SELECT * FROM `$table` WHERE LOWER(REPLACE(REPLACE(REPLACE(`ruang`, ' ', ''), '.', ''), '-', '')) LIKE ?"
        . orderByExisting($conn, $table, ['hari', 'jam_mulai', 'semester', 'mata_kuliah']);
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return "Query jadwal ruang gagal: " . mysqli_error($conn);
    }

    mysqli_stmt_bind_param($stmt, "s", $like);
    mysqli_stmt_execute($stmt);
    $q = mysqli_stmt_get_result($stmt);

    if (!$q) {
        return "Query jadwal ruang gagal: " . mysqli_error($conn);
    }

    if (mysqli_num_rows($q) == 0) {
        return "Belum ada jadwal kuliah yang tercatat di ruang " . strtoupper($ruang) . ".";
    }

    $text = "Berikut jadwal kuliah di ruang " . strtoupper($ruang) . ":\n\n";
    $no = 1;

    while ($row = mysqli_fetch_assoc($q)) {
        $text .= cyraJadwalLine($row, $no++) . "\n";
    }

    return trim($text);
}

function tampilJadwalKuliahHari($conn, $hari, $semester = null)
{
    $table = 'jadwal_kuliah';
    $hari = normalizeHari($hari);

    if ($hari === null) {
        return "Hari yang dimaksud belum terbaca. Contoh: jadwal kuliah hari Senin.";
    }

    if (cyraIsHariLiburKuliah($hari)) {
        return cyraJadwalLiburText($hari);
    }

    if (!tableExists($conn, $table)) {
        return "Data jadwal kuliah belum tersedia.";
    }

    $where = ["LOWER(REPLACE(`hari`, '''', '')) = ?"];
    $types = "s";
    $values = [$hari];

    if ($semester !== null && columnExists($conn, $table, 'semester')) {
        if ($semester === 'ganjil') {
            $where[] = "MOD(`semester`, 2) = 1";
        } elseif ($semester === 'genap') {
            $where[] = "MOD(`semester`, 2) = 0";
        } else {
            $where[] = "`semester` = ?";
            $types .= "i";
            $values[] = $semester;
        }
    }

    $sql = "SELECT * FROM `$table` WHERE " . implode(" AND ", $where)
        . orderByExisting($conn, $table, ['semester', 'jam_mulai', 'mata_kuliah']);
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return "Query jadwal hari gagal: " . mysqli_error($conn);
    }

    bindParams($stmt, $types, $values);
    mysqli_stmt_execute($stmt);
    $q = mysqli_stmt_get_result($stmt);

    if (!$q) {
        return "Query jadwal hari gagal: " . mysqli_error($conn);
    }

    if (mysqli_num_rows($q) == 0) {
        if ($semester !== null) {
            return "Belum ada jadwal kuliah semester $semester pada hari " . cyraFormatNamaHari($hari) . ".";
        }

        return "Belum ada jadwal kuliah pada hari " . cyraFormatNamaHari($hari) . ".";
    }

    $text = $semester !== null
        ? "Berikut jadwal kuliah semester $semester pada hari " . cyraFormatNamaHari($hari) . ":\n\n"
        : "Berikut jadwal kuliah pada hari " . cyraFormatNamaHari($hari) . ":\n\n";
    $no = 1;

    while ($row = mysqli_fetch_assoc($q)) {
        $text .= cyraJadwalLine($row, $no++) . "\n";
    }

    return trim($text);
}

function normalisasiFonetikNamaDosen(string $text): string
{
    $text = normalizeText($text);
    $text = str_replace(
        ['q', 'v', 'ph', 'p'],
        ['k', 'f', 'f', 'f'],
        $text
    );

    return trim(preg_replace('/\s+/', ' ', $text));
}

function namaDosenTanpaSpasi(string $text, bool $fonetik = false): string
{
    $text = $fonetik ? normalisasiFonetikNamaDosen($text) : normalizeText($text);

    return preg_replace('/\s+/', '', $text);
}

function skorKataNama(string $keywordWord, string $nameWord): float
{
    similar_text($keywordWord, $nameWord, $wordScore);

    $distance = levenshtein($keywordWord, $nameWord);
    $maxLength = max(strlen($keywordWord), strlen($nameWord), 1);
    $distanceScore = (1 - min($distance, $maxLength) / $maxLength) * 100;

    return max($wordScore, $distanceScore);
}

function scoreKemiripanNamaDosen(string $keyword, string $namaDosen): float
{
    $keyword = normalizeText($keyword);
    $namaNorm = normalizeText($namaDosen);

    if ($keyword === '' || $namaNorm === '') {
        return 0;
    }

    similar_text($keyword, $namaNorm, $score);

    if (strpos($namaNorm, $keyword) !== false) {
        $score += 60;
    }

    $keywordFlat = namaDosenTanpaSpasi($keyword);
    $nameFlat = namaDosenTanpaSpasi($namaNorm);
    $keywordPhoneticFlat = namaDosenTanpaSpasi($keyword, true);
    $namePhoneticFlat = namaDosenTanpaSpasi($namaNorm, true);

    if (strlen($keywordFlat) >= 4 && strpos($nameFlat, $keywordFlat) !== false) {
        $score += 90;
    }

    if (strlen($keywordPhoneticFlat) >= 4 && strpos($namePhoneticFlat, $keywordPhoneticFlat) !== false) {
        $score += 85;
    }

    if (strlen($keywordPhoneticFlat) >= 5) {
        $flatDistance = levenshtein($keywordPhoneticFlat, substr($namePhoneticFlat, 0, strlen($keywordPhoneticFlat)));
        if ($flatDistance <= 1) {
            $score += 55;
        }
    }

    $keywordWords = faqKeywords($keyword);
    $nameWords = faqKeywords($namaNorm);

    foreach ($keywordWords as $keywordWord) {
        foreach ($nameWords as $nameWord) {
            $bestWordScore = skorKataNama($keywordWord, $nameWord);
            $keywordPhonetic = normalisasiFonetikNamaDosen($keywordWord);
            $namePhonetic = normalisasiFonetikNamaDosen($nameWord);
            $bestPhoneticScore = skorKataNama($keywordPhonetic, $namePhonetic);

            if ($keywordWord === $nameWord) {
                $score += 45;
            } elseif (strlen($keywordPhonetic) >= 4 && strpos($namePhonetic, $keywordPhonetic) === 0) {
                $score += 62;
            } elseif ($bestPhoneticScore >= 82 && strlen($keywordPhonetic) >= 4) {
                $score += 46;
            } elseif ($bestWordScore >= 82 && strlen($keywordWord) >= 4) {
                $score += 38;
            } elseif ($bestWordScore >= 72 && strlen($keywordWord) >= 5) {
                $score += 24;
            }
        }
    }

    return min(150, $score);
}

function cariJawabanDosen($conn, $userText, $minimumScore = 55)
{
    $table = 'dosen';

    if (!tableExists($conn, $table)) {
        return null;
    }

    $keyword = keywordPencarianDosen($userText);

    if ($keyword === '') {
        return null;
    }

    $order = orderByExisting($conn, $table, ['nama_dosen', 'nama_pegawai', 'nama']);
    $q = mysqli_query($conn, "SELECT * FROM `$table` $order");

    if (!$q || mysqli_num_rows($q) == 0) {
        return null;
    }

    $bestRow = null;
    $bestScore = 0;

    while ($d = mysqli_fetch_assoc($q)) {
        $namaDosen = safeValue($d, ['nama_dosen', 'nama_pegawai', 'nama']);
        $percent = scoreKemiripanNamaDosen($keyword, $namaDosen);

        if ($percent > $bestScore) {
            $bestScore = $percent;
            $bestRow = $d;
        }
    }

    return ($bestRow && $bestScore >= $minimumScore) ? formatDataDosen($bestRow) : null;
}

function tampilDosen($conn, $userText = '')
{
    $table = 'dosen';

    if (!tableExists($conn, $table)) {
        return "Data dosen belum tersedia.";
    }

    $order = orderByExisting($conn, $table, ['nama_dosen', 'nama_pegawai', 'nama']);
    $q = mysqli_query($conn, "SELECT * FROM `$table` $order");

    if (!$q) {
        return "Query dosen gagal: " . mysqli_error($conn);
    }

    if (mysqli_num_rows($q) == 0) {
        return "Data dosen tidak ditemukan.";
    }

    $matchedDosen = cariJawabanDosen($conn, $userText);

    if ($matchedDosen !== null) {
        return $matchedDosen;
    }

    mysqli_data_seek($q, 0);

    $text = "Daftar Dosen:\n\n";

    while ($d = mysqli_fetch_assoc($q)) {
        $namaDosen = safeValue($d, ['nama_dosen', 'nama_pegawai', 'nama']);
        $email = safeValue($d, ['email'], '');

        $text .= "- $namaDosen";

        if ($email !== '') {
            $text .= " - $email";
        }

        $text .= "\n";
    }

    return trim($text);
}

/* =========================================================
   FAQ
========================================================= */
function tampilFAQ($conn, $userText)
{
    $answer = cariJawabanFAQ($conn, $userText);

    if ($answer !== null) {
        return $answer;
    }

    return "Maaf, data FAQ untuk pertanyaan tersebut belum tersedia.";
}

function cariJawabanFAQ($conn, $userText, $minimumScore = 78, ?string $requiredCategory = null)
{
    if (!tableExists($conn, 'faq')) {
        return null;
    }

    $normalizedUserText = normalizeText($userText);
    $keywords = faqKeywords($normalizedUserText);
    $isOfficialLinkQuestion = cyraIsOfficialLinkQuestion($normalizedUserText);

    if (strlen($normalizedUserText) < 4) {
        return null;
    }

    if (count($keywords) < 2 && !$isOfficialLinkQuestion) {
        return null;
    }

    $order = columnExists($conn, 'faq', 'id_faq') ? "ORDER BY id_faq DESC" : "";
    $q = mysqli_query($conn, "SELECT * FROM `faq` $order");

    if (!$q) {
        return null;
    }

    if (mysqli_num_rows($q) == 0) {
        return null;
    }

    $bestAnswer = null;
    $bestScore = 0;

    while ($d = mysqli_fetch_assoc($q)) {
        $pertanyaan = $d['pertanyaan'] ?? '';
        $jawaban = $d['jawaban'] ?? '';
        $kategori = $d['kategori'] ?? '';

        if ($requiredCategory !== null && normalizeText($kategori) !== normalizeText($requiredCategory)) {
            continue;
        }

        $score = faqMatchScore($userText, $pertanyaan, $kategori);

        if ($score > $bestScore && trim((string)$jawaban) !== '') {
            $bestScore = $score;
            $bestAnswer = $jawaban;
        }
    }

    return $bestScore >= $minimumScore ? $bestAnswer : null;
}
