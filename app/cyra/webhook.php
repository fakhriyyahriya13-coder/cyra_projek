<?php
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../Foundation/Database.php';
require_once __DIR__ . '/../Services/Cyra/Response.php';
require_once __DIR__ . '/../Services/Cyra/Text.php';
require_once __DIR__ . '/../Services/Cyra/Context.php';
require_once __DIR__ . '/../Services/Cyra/DatabaseHelpers.php';
require_once __DIR__ . '/../Services/Cyra/AcademicAnswers.php';

try {
    $conn = cyraRequireDatabase();
} catch (Throwable $e) {
    jsonResponse(cyraDatabaseErrorReply());
}

/* =========================================================
   VALIDASI REQUEST
========================================================= */
if (!isset($conn) || !$conn) {
    jsonResponse(cyraDatabaseErrorReply());
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse("Webhook aktif. Gunakan request POST dari Dialogflow.");
}

/* =========================================================
   AMBIL DATA DIALOGFLOW
========================================================= */
$rawInput = file_get_contents('php://input');
writeLog("RAW INPUT: " . $rawInput);

$request = json_decode($rawInput, true);

if (!$request) {
    jsonResponse("Request tidak valid.");
}

$intent = $request['queryResult']['intent']['displayName'] ?? '';
$rawUserText = cleanText($request['queryResult']['queryText'] ?? '');
$userText = normalizeTypo($rawUserText);
$params = $request['queryResult']['parameters'] ?? [];
$contextData = getActiveContextData($request);
$contextType = $contextData['type'] ?? null;
$contextParams = $contextData['parameters'] ?? [];

writeLog("Intent: $intent | Raw Query: $rawUserText | Normalized Query: $userText | Context: $contextType | Context Params: " . json_encode($contextParams, JSON_UNESCAPED_UNICODE) . " | Params: " . json_encode($params, JSON_UNESCAPED_UNICODE));

$response = cyraFallbackReply();

/* ---------- PRIORITAS 1: JADWAL NON-AKADEMIK LANGSUNG FALLBACK ---------- */
if (cyraIsNonAcademicScheduleQuestion($userText)) {
    clearPendingContext($request);
    jsonResponse(cyraFallbackReply());
}

if (cyraIsOutOfScopeCasualQuestion($userText)) {
    clearPendingContext($request);
    jsonResponse(cyraFallbackReply());
}

/* =========================================================
   JAWABAN LANJUTAN BERDASARKAN CONTEXT
   Contoh:
   User: uas tanggal berapa
   CYRA: Mau lihat jadwal UAS semester berapa?
   User: 7 / semester 7 / semua
========================================================= */
if (intentIn($intent, ['Pilih Semester']) || preg_match('/^(semester\s*)?[1-8]$/', $userText)) {
    $semester = extractSemester($userText, $params);

    if (!$semester || $semester < 1 || $semester > 8) {
        jsonResponse("Semester tidak valid. Silakan masukkan angka semester 1 sampai 8.");
    }

    $hariContext = null;

    if (isset($contextParams['hari']) && $contextParams['hari'] !== '') {
        $hariContext = normalizeHari($contextParams['hari']);
    }

    if ($contextType === 'jadwal_kuliah') {
        clearPendingContext($request);
        if ($hariContext && cyraIsHariLiburKuliah($hariContext)) {
            jsonResponse(cyraJadwalLiburText($hariContext));
        }
        if (!empty($contextParams['mata_kuliah'])) {
            jsonResponse(tampilJadwalKuliahMataKuliah($conn, $contextParams['mata_kuliah'], $semester));
        }
        jsonResponse(tampilJadwalKuliah($conn, $semester, $hariContext));
    } elseif ($contextType === 'uts') {
        clearPendingContext($request);
        if (!empty($contextParams['mata_kuliah'])) {
            jsonResponse(tampilJadwalUjianMataKuliah($conn, 'jadwal_uts', 'UTS', $contextParams['mata_kuliah'], $semester));
        }
        jsonResponse(tampilJadwalUjian($conn, 'jadwal_uts', 'UTS', $semester));
    } elseif ($contextType === 'uas') {
        clearPendingContext($request);
        if (!empty($contextParams['mata_kuliah'])) {
            jsonResponse(tampilJadwalUjianMataKuliah($conn, 'jadwal_uas', 'UAS', $contextParams['mata_kuliah'], $semester));
        }
        jsonResponse(tampilJadwalUjian($conn, 'jadwal_uas', 'UAS', $semester));
    } elseif ($contextType === 'mata_kuliah') {
        clearPendingContext($request);
        jsonResponse(tampilMataKuliah($conn, $semester));
    }

    jsonResponse("Semester $semester untuk data apa? Contoh: jadwal kuliah semester $semester, UTS semester $semester, atau UAS semester $semester.");
}

if (isSemua($userText) && $contextType) {
    clearPendingContext($request);

    if ($contextType === 'jadwal_kuliah') {
        $hariContext = isset($contextParams['hari']) ? normalizeHari($contextParams['hari']) : null;

        if (!empty($contextParams['mata_kuliah'])) {
            jsonResponse(tampilJadwalKuliahMataKuliah($conn, $contextParams['mata_kuliah'], null));
        }

        if ($hariContext) {
            jsonResponse(tampilJadwalKuliahHari($conn, $hariContext, null));
        }

        jsonResponse(tampilJadwalKuliah($conn, null, null));
    } elseif ($contextType === 'uts') {
        if (!empty($contextParams['mata_kuliah'])) {
            jsonResponse(tampilJadwalUjianMataKuliah($conn, 'jadwal_uts', 'UTS', $contextParams['mata_kuliah'], null));
        }
        jsonResponse(tampilJadwalUjian($conn, 'jadwal_uts', 'UTS', null));
    } elseif ($contextType === 'uas') {
        if (!empty($contextParams['mata_kuliah'])) {
            jsonResponse(tampilJadwalUjianMataKuliah($conn, 'jadwal_uas', 'UAS', $contextParams['mata_kuliah'], null));
        }
        jsonResponse(tampilJadwalUjian($conn, 'jadwal_uas', 'UAS', null));
    } elseif ($contextType === 'mata_kuliah') {
        jsonResponse(tampilMataKuliah($conn, null));
    }
}

/* =========================================================
   ROUTING UTAMA
========================================================= */

/* ---------- PRIORITAS 2: IDENTITAS, FUNGSI, DAN FITUR CYRA ---------- */
if (
    cyraIsPertanyaanTentangCyra($userText) ||
    containsAny($userText, ['kamu siapa', 'bot ini apa', 'chatbot ini apa'])
) {
    clearPendingContext($request);
    $response = cyraJawabanTentangCyra($userText);
}

elseif (cyraIsPertanyaanKontakProdi($userText)) {
    clearPendingContext($request);
    $response = cyraJawabanKontakProdi();
}

elseif (cyraIsPertanyaanUmumAkademik($userText)) {
    clearPendingContext($request);
    $response = cyraJawabanUmumAkademik($userText);
}

/* ---------- PRIORITAS 3: HUBUNGAN FAQ ADMIN KE CYRA ---------- */
elseif (cyraIsPertanyaanFAQMasukCyra($userText)) {
    clearPendingContext($request);
    $response = cyraJawabanFAQMasukCyra();
}

/* ---------- PRIORITAS 3: FAQ ADMIN TAMBAH/UBAH/HAPUS ---------- */
elseif (cyraIsPertanyaanFAQAdmin($userText)) {
    clearPendingContext($request);
    $response = cyraJawabanFAQAdmin();
}

/* ---------- PRIORITAS 4: JADWAL HARI INI TANPA KATA JADWAL ---------- */
elseif (cyraIsBareTodayQuestion($userText)) {
    clearPendingContext($request);
    $response = tampilJadwalKuliahHari($conn, getHariIni(), null);
}

/* ---------- PRIORITAS 4: DOSEN PENGAMPU MATA KULIAH ---------- */
elseif (cyraIsPertanyaanDosenMataKuliah($userText)) {
    clearPendingContext($request);
    $response = tampilDosenMataKuliah($conn, $userText, $params);
}

/* ---------- PRIORITAS 4: MATA KULIAH BERDASARKAN DOSEN ---------- */
elseif (cyraIsPertanyaanMataKuliahDosen($userText)) {
    clearPendingContext($request);
    $response = tampilMataKuliahDosen($conn, $userText);
}

/* ---------- DOSEN UMUM ---------- */
elseif (cyraIsPertanyaanDaftarDosen($userText)) {
    clearPendingContext($request);
    $response = tampilDosen($conn, $userText);
}

/* ---------- SAPAAN ---------- */
elseif (
    intentIn($intent, ['Sapaan', 'Default Welcome Intent']) ||
    in_array(normalizeText($userText), [
        'halo',
        'hallo',
        'hai',
        'hello',
        'hi',
        'pagi',
        'siang',
        'sore',
        'malam',
        'selamat pagi',
        'selamat siang',
        'selamat sore',
        'selamat malam',
        'assalamualaikum'
    ])
) {
    $response = "Halo, selamat datang di CYRA Teknik Informatika. Silakan tanya tentang jadwal kuliah, dosen, mata kuliah, UTS, UAS, FRS, KP, TA, atau pendaftaran Prodi Teknik Informatika.";
}

/* ---------- TERIMA KASIH ---------- */
elseif (
    intentIn($intent, ['Terima Kasih']) ||
    cyraIsThanksExpression($userText)
) {
    $response = "Sama-sama. Senang bisa membantu.";
}

/* ---------- FAQ EKSPLISIT: JANGAN BIARKAN FAQ NYASAR KE UTS/UAS/TA ---------- */
elseif (containsAny($userText, ['faq'])) {
    clearPendingContext($request);
    $response = tampilFAQ($conn, $userText);
}

/* ---------- LINK / WEBSITE RESMI DARI FAQ KATEGORI UMUM ---------- */
elseif (cyraIsOfficialLinkQuestion($userText) && ($faqAnswer = cariJawabanFAQKategori($conn, $userText, 'Umum', 60)) !== null) {
    clearPendingContext($request);
    $response = $faqAnswer;
}

/* ---------- PENDAFTARAN UTS/UAS ---------- */
elseif (cyraIsExamRegistrationQuestion($userText)) {
    clearPendingContext($request);
    $response = cyraExamRegistrationAnswer($userText);
}

/* ---------- PENDAFTARAN TEKNIK INFORMATIKA ---------- */
elseif (isTanyaPendaftaran($userText)) {
    $response = tampilPendaftaranTeknikInformatika($conn);
}

/* ---------- PRIORITAS 8: PENGERTIAN FRS ---------- */
elseif (
    containsAny($userText, ['apa itu frs', 'frs itu apa', 'pengertian frs', 'arti frs'])
) {
    $response = pengertianFRS();
}

/* ---------- PRIORITAS 8: PENGERTIAN KP ---------- */
elseif (
    containsAny($userText, ['apa itu kp', 'kp itu apa', 'pengertian kp', 'arti kp', 'apa itu kerja praktik', 'apa itu kerja praktek'])
) {
    $response = pengertianKP();
}

/* ---------- PRIORITAS 8: PENGERTIAN TA ---------- */
elseif (
    containsAny($userText, ['apa itu ta', 'ta itu apa', 'pengertian ta', 'arti ta', 'apa itu tugas akhir', 'tugas akhir itu apa'])
) {
    $response = pengertianTA();
}

/* ---------- PRIORITAS 5: PERUBAHAN JADWAL ---------- */
elseif (cyraIsScheduleChangeQuestion($userText)) {
    clearPendingContext($request);
    $response = cyraScheduleChangeAnswer();
}

/* ---------- PRIORITAS 5: HARI KULIAH PER SEMESTER ---------- */
elseif (cyraIsHariApaSajaKuliahQuestion($userText)) {
    $response = tampilHariKuliahSemester($conn, extractSemester($userText, $params));
}

/* ---------- PRIORITAS 5: JADWAL / RUANG MATA KULIAH ---------- */
elseif (cyraIsCourseRoomQuestion($userText)) {
    clearPendingContext($request);
    $response = tampilRuangMataKuliah($conn, $userText, extractSemester($userText, $params));
}

elseif (cyraIsCourseScheduleQuestion($userText)) {
    $semester = extractSemester($userText, $params);
    $response = tampilJadwalKuliahMataKuliah($conn, $userText, $semester);
}

/* ---------- PRIORITAS 5: JADWAL KULIAH AKADEMIK BERDASARKAN RUANG ---------- */
elseif (
    cyraIsPertanyaanRuang($userText) &&
    !containsAny($userText, ['uts', 'uas'])
) {
    clearPendingContext($request);
    $response = tampilJadwalKuliahRuang($conn, $userText);
}

/* ---------- PRIORITAS 5: JADWAL KULIAH AKADEMIK BERDASARKAN HARI ---------- */
elseif (
    ($hari = extractHari($userText, $params)) &&
    cyraIsAcademicScheduleQuestion($userText) &&
    !containsAny($userText, ['uts', 'uas'])
) {
    clearPendingContext($request);
    $semester = extractSemester($userText, $params);
    $response = tampilJadwalKuliahHari($conn, $hari, $semester);
}

/* ---------- PRIORITAS 5: JADWAL KULIAH SEMUA ---------- */
elseif (
    intentIn($intent, ['Jadwal Kuliah Semua']) ||
    (
        cyraIsAcademicScheduleQuestion($userText) &&
        isSemua($userText)
    )
) {
    $response = tampilJadwalKuliah($conn, null, null);
}

/* ---------- PRIORITAS 5: JADWAL KULIAH UMUM ---------- */
elseif (
    (intentIn($intent, ['Jadwal Kuliah']) && cyraIsAcademicScheduleQuestion($userText)) ||
    cyraIsAcademicScheduleQuestion($userText)
) {
    $semester = extractSemester($userText, $params);
    $hari = extractHari($userText, $params);

    if ($hari) {
        clearPendingContext($request);
        $response = tampilJadwalKuliahHari($conn, $hari, $semester);
    } elseif (!$semester && isSemua($userText)) {
        $response = tampilJadwalKuliah($conn, null, null);
    } elseif (!$semester) {
        jsonResponse(
            "Mau lihat jadwal kuliah semester berapa? Ketik angka semester, misalnya 6, atau ketik semua.",
            makeContext($request, 'ctx_jadwal_kuliah', 5, ['hari' => $hari])
        );
    } else {
        $response = tampilJadwalKuliah($conn, $semester, $hari);
    }
}

/* ---------- PRIORITAS 6: JADWAL UTS SEMUA ---------- */
elseif (
    containsAny($userText, ['uts']) &&
    isSemua($userText)
) {
    $response = tampilJadwalUjian($conn, 'jadwal_uts', 'UTS', null);
}

/* ---------- PRIORITAS 6: JADWAL UAS SEMUA ---------- */
elseif (
    containsAny($userText, ['uas']) &&
    isSemua($userText)
) {
    $response = tampilJadwalUjian($conn, 'jadwal_uas', 'UAS', null);
}

/* ---------- PRIORITAS 6: INFO UMUM / JADWAL UJIAN PER MATA KULIAH ---------- */
elseif (cyraIsWhereToSeeExamScheduleQuestion($userText, 'UTS')) {
    clearPendingContext($request);
    $response = cyraWhereToSeeExamScheduleAnswer('UTS');
}

elseif (cyraIsWhereToSeeExamScheduleQuestion($userText, 'UAS')) {
    clearPendingContext($request);
    $response = cyraWhereToSeeExamScheduleAnswer('UAS');
}

elseif (cyraIsExamPublishedQuestion($userText, 'UTS')) {
    clearPendingContext($request);
    $response = cyraExamPublishedAnswer($conn, 'jadwal_uts', 'UTS');
}

elseif (cyraIsExamPublishedQuestion($userText, 'UAS')) {
    clearPendingContext($request);
    $response = cyraExamPublishedAnswer($conn, 'jadwal_uas', 'UAS');
}

elseif (cyraIsExamCourseScheduleQuestion($userText, 'UTS')) {
    $semester = extractSemester($userText, $params);
    $response = tampilJadwalUjianMataKuliah($conn, 'jadwal_uts', 'UTS', $userText, $semester);
}

elseif (cyraIsExamCourseScheduleQuestion($userText, 'UAS')) {
    $semester = extractSemester($userText, $params);
    $response = tampilJadwalUjianMataKuliah($conn, 'jadwal_uas', 'UAS', $userText, $semester);
}

elseif (cyraIsTaScheduleQuestion($userText)) {
    clearPendingContext($request);
    $response = cyraTaScheduleAnswer($conn);
}

/* ---------- PRIORITAS 6: JADWAL UTS ---------- */
elseif (
    containsAny($userText, ['jadwal uts', 'uts'])
) {
    $semester = extractSemester($userText, $params);

    if (!$semester) {
        jsonResponse(
            "Mau lihat jadwal UTS semester berapa? Ketik angka semester, misalnya 6, atau ketik semua.",
            makeContext($request, 'ctx_uts')
        );
    }

    $response = tampilJadwalUjian($conn, 'jadwal_uts', 'UTS', $semester);
}

/* ---------- PRIORITAS 6: JADWAL UAS ---------- */
elseif (
    containsAny($userText, ['jadwal uas', 'uas'])
) {
    $semester = extractSemester($userText, $params);

    if (!$semester) {
        jsonResponse(
            "Mau lihat jadwal UAS semester berapa? Ketik angka semester, misalnya 6, atau ketik semua.",
            makeContext($request, 'ctx_uas')
        );
    }

    $response = tampilJadwalUjian($conn, 'jadwal_uas', 'UAS', $semester);
}

/* ---------- PRIORITAS 8: SYARAT FRS / KP / TA ---------- */
elseif (cyraIsProcedureRequirementQuestion($userText)) {
    $topic = cyraProcedureRequirementTopic($userText);
    $response = cyraAnswerProcedureRequirements($conn, $topic);
}

/* ---------- PRIORITAS 8: PROSEDUR FRS ---------- */
elseif (
    containsAny($userText, ['frs']) &&
    (isTanyaCara($userText) || intentIn($intent, ['Prosedur FRS']))
) {
    $singkat = !containsAny($userText, ['lengkap', 'detail', 'semua']);
    $response = tampilProsedur($conn, 'prosedur_frs', 'Cara Pengisian FRS', $singkat);
}

/* ---------- PRIORITAS 8: PROSEDUR KP ---------- */
elseif (
    containsAny($userText, ['kp', 'kerja praktek', 'kerja praktik']) &&
    (isTanyaCara($userText) || intentIn($intent, ['Prosedur KP']))
) {
    $singkat = !containsAny($userText, ['lengkap', 'detail', 'semua']);
    $response = tampilProsedur($conn, 'prosedur_kp', 'Cara Pengajuan KP', $singkat);
}

/* ---------- PRIORITAS 8: PROSEDUR TA ---------- */
elseif (
    containsAny($userText, ['ta', 'tugas akhir', 'skripsi']) &&
    (isTanyaCara($userText) || intentIn($intent, ['Prosedur TA']))
) {
    $singkat = !containsAny($userText, ['lengkap', 'detail', 'semua']);
    $response = tampilProsedur($conn, 'prosedur_ta', 'Cara Pengajuan TA', $singkat);
}

/* ---------- PRIORITAS 7: MATA KULIAH ---------- */
elseif (
    intentIn($intent, ['MataKuliah', 'Mata Kuliah']) ||
    containsAny($userText, ['mata kuliah', 'matakuliah', 'mk tersedia', 'daftar mk'])
) {
    $semester = extractSemester($userText, $params);

    if (!$semester && isSemua($userText)) {
        $response = tampilMataKuliah($conn, null);
    } elseif (!$semester) {
        jsonResponse(
            "Mau lihat mata kuliah semester berapa? Ketik angka semester, misalnya 6, atau ketik semua.",
            makeContext($request, 'ctx_mata_kuliah')
        );
    } else {
        $response = tampilMataKuliah($conn, $semester);
    }
}

/* ---------- JAWABAN LANJUTAN: SEMUA ---------- */
elseif (
    intentIn($intent, ['Pilih Semua']) ||
    isSemua($userText)
) {
    if ($contextType === 'jadwal_kuliah') {
        $hariContext = isset($contextParams['hari']) ? normalizeHari($contextParams['hari']) : null;
        $response = $hariContext
            ? tampilJadwalKuliahHari($conn, $hariContext, null)
            : tampilJadwalKuliah($conn, null, null);
    } elseif ($contextType === 'uts') {
        $response = tampilJadwalUjian($conn, 'jadwal_uts', 'UTS', null);
    } elseif ($contextType === 'uas') {
        $response = tampilJadwalUjian($conn, 'jadwal_uas', 'UAS', null);
    } elseif ($contextType === 'mata_kuliah') {
        $response = tampilMataKuliah($conn, null);
    } else {
        $response = "Silakan sebutkan data yang ingin ditampilkan, misalnya: semua jadwal kuliah, semua jadwal UTS, semua jadwal UAS, atau semua mata kuliah.";
    }
}

/* ---------- DOSEN ---------- */
elseif (
    intentIn($intent, ['Dosen', 'Informasi Dosen']) ||
    containsAny($userText, ['dosen', 'pengampu', 'nidn', 'nip'])
) {
    $response = tampilDosen($conn, $userText);
}

/* ---------- PRIORITAS 9: FAQ DATABASE ---------- */
elseif (isIntent($intent, 'FAQ')) {
    $response = tampilFAQ($conn, $userText);
}

/* ---------- PRIORITAS 10: FALLBACK ---------- */
else {
    $response = cyraFallbackReply();
}

if (trim((string)$response) === '' || strpos((string)$response, 'Dialogflow/webhook') !== false) {
    $response = cyraFallbackReply();
}

jsonResponse($response);
