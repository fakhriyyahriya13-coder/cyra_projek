<?php

require_once __DIR__ . '/Text.php';
require_once __DIR__ . '/DatabaseHelpers.php';
require_once __DIR__ . '/AcademicAnswers.php';

function cyraLocalDatabaseConnection(): ?mysqli
{
    static $connection = null;
    static $loaded = false;

    if (!$loaded) {
        $loaded = true;
        require dirname(__DIR__, 3) . '/config/database.php';
        $connection = isset($conn) && $conn instanceof mysqli ? $conn : null;
    }

    return $connection;
}

function cyraSetLocalPending(string $type, array $params = []): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $_SESSION['cyra_local_pending'] = [
        'type' => $type,
        'params' => $params,
        'created_at' => time()
    ];
}

function cyraGetLocalPending(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['cyra_local_pending'])) {
        return null;
    }

    $pending = $_SESSION['cyra_local_pending'];
    $createdAt = (int)($pending['created_at'] ?? 0);

    if ($createdAt > 0 && (time() - $createdAt) > 600) {
        cyraClearLocalPending();
        return null;
    }

    return is_array($pending) ? $pending : null;
}

function cyraClearLocalPending(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        unset($_SESSION['cyra_local_pending']);
    }
}

function cyraIsEmptyDataAnswer(string $answer): bool
{
    return stripos($answer, 'tidak ditemukan') !== false || stripos($answer, 'belum tersedia') !== false;
}

function cyraKeepActiveContext(string $answer, string $type, array $params = []): string
{
    cyraSetLocalPending($type, $params);

    if (cyraIsEmptyDataAnswer($answer)) {
        if (!empty($params['mata_kuliah'])) {
            return trim($answer) . "\n\nCoba cek penulisan nama mata kuliah atau sebutkan mata kuliah lain.";
        }

        return trim($answer) . "\n\nCoba ketik semester lain, misalnya 4, atau ketik semua.";
    }

    return $answer;
}

function cyraWantsScheduleSemesterByDay(string $userText): bool
{
    return extractHari($userText) !== null && containsAny($userText, [
        'semester berapa',
        'disemester berapa',
        'di semester berapa',
        'semester apa',
        'semester apa saja',
        'semester mana',
        'ada disemester',
        'ada di semester'
    ]);
}

function cyraKpRequirementFallback(): string
{
    $config = cyraRequirementConfig('kp');
    return $config['fallback'] ?? cyraFallbackReply();
}

function cyraAnswerKpRequirements(mysqli $conn): string
{
    return cyraAnswerProcedureRequirements($conn, 'kp');
}

function cyraIsKpRequirementQuestion(string $userText): bool
{
    return cyraProcedureRequirementTopic($userText) === 'kp' && cyraIsRequirementPhrase($userText);
}

function cyraIsProcedureFollowUpQuestion(string $userText): bool
{
    return containsAny($userText, [
        'syarat',
        'persyaratan',
        'ketentuan',
        'aturan',
        'kewajiban',
        'apa saja',
        'jelaskan',
        'detail',
        'lengkap',
        'semua'
    ]);
}

function cyraAnswerPendingContext(mysqli $conn, string $userText): ?string
{
    $pending = cyraGetLocalPending();

    if (!$pending) {
        return null;
    }

    $type = $pending['type'] ?? '';
    $params = $pending['params'] ?? [];
    $semester = extractSemester($userText);
    $showAll = isSemua($userText);

    if ($type !== 'pilih_data_semester') {
        if (
            $type !== 'uts' &&
            containsAny($userText, ['uts', 'jadwal uts'])
        ) {
            return null;
        }

        if (
            $type !== 'uas' &&
            containsAny($userText, ['uas', 'jadwal uas'])
        ) {
            return null;
        }

        if (
            $type !== 'jadwal_kuliah' &&
            containsAny($userText, ['jadwal kuliah', 'jadwal kelas', 'kuliah hari', 'jadwal hari'])
        ) {
            return null;
        }

        if (
            $type !== 'mata_kuliah' &&
            containsAny($userText, ['mata kuliah', 'matakuliah', 'matkul'])
        ) {
            return null;
        }

        if (containsAny($userText, ['cyra', 'faq', 'dosen', 'pengampu', 'frs', 'kp', 'kerja praktik', 'kerja praktek', 'ta', 'tugas akhir', 'skripsi'])) {
            return null;
        }
    }

    if (in_array($type, ['prosedur_kp', 'prosedur_frs', 'prosedur_ta'], true)) {
        if (containsAny($userText, ['jadwal', 'uts', 'uas', 'dosen', 'mata kuliah', 'matakuliah', 'pmb', 'pendaftaran', 'website'])) {
            return null;
        }

        if ($type !== 'prosedur_kp' && containsAny($userText, ['kp', 'kerja praktik', 'kerja praktek'])) {
            return null;
        }

        if ($type !== 'prosedur_frs' && containsAny($userText, ['frs'])) {
            return null;
        }

        if ($type !== 'prosedur_ta' && containsAny($userText, ['ta', 'tugas akhir', 'skripsi'])) {
            return null;
        }

        if (!cyraIsProcedureFollowUpQuestion($userText)) {
            return null;
        }

        if (cyraIsRequirementPhrase($userText)) {
            $topic = str_replace('prosedur_', '', $type);
            return cyraKeepActiveContext(cyraAnswerProcedureRequirements($conn, $topic), $type);
        }

        if ($type === 'prosedur_kp') {
            return cyraKeepActiveContext(tampilProsedur($conn, 'prosedur_kp', 'Cara Pengajuan KP', !isSemua($userText) && !containsAny($userText, ['lengkap', 'detail'])), 'prosedur_kp');
        }

        if ($type === 'prosedur_frs') {
            return cyraKeepActiveContext(tampilProsedur($conn, 'prosedur_frs', 'Cara Pengisian FRS', !isSemua($userText) && !containsAny($userText, ['lengkap', 'detail'])), 'prosedur_frs');
        }

        if ($type === 'prosedur_ta') {
            return cyraKeepActiveContext(tampilProsedur($conn, 'prosedur_ta', 'Cara Pengajuan TA', !isSemua($userText) && !containsAny($userText, ['lengkap', 'detail'])), 'prosedur_ta');
        }
    }

    if ($type === 'pilih_data_semester') {
        $selectedSemester = (int)($params['semester'] ?? 0);

        if ($selectedSemester < 1) {
            cyraClearLocalPending();
            return null;
        }

        if (containsAny($userText, ['uts'])) {
            return cyraKeepActiveContext(tampilJadwalUjian($conn, 'jadwal_uts', 'UTS', $selectedSemester), 'uts');
        }

        if (containsAny($userText, ['uas'])) {
            return cyraKeepActiveContext(tampilJadwalUjian($conn, 'jadwal_uas', 'UAS', $selectedSemester), 'uas');
        }

        if (containsAny($userText, ['mata kuliah', 'matakuliah', 'mk'])) {
            return cyraKeepActiveContext(tampilMataKuliah($conn, $selectedSemester), 'mata_kuliah');
        }

        if (containsAny($userText, ['jadwal', 'kuliah', 'jadwal kuliah'])) {
            return cyraKeepActiveContext(tampilJadwalKuliah($conn, $selectedSemester, null), 'jadwal_kuliah', ['hari' => null]);
        }

        return "Semester $selectedSemester mau lihat data apa? Ketik jadwal kuliah, mata kuliah, UTS, atau UAS.";
    }

    if (!$semester && !$showAll) {
        return null;
    }

    if ($type === 'jadwal_kuliah') {
        $hari = $params['hari'] ?? null;

        if (!empty($params['mata_kuliah'])) {
            $answer = $showAll
                ? tampilJadwalKuliahMataKuliah($conn, $params['mata_kuliah'], null)
                : tampilJadwalKuliahMataKuliah($conn, $params['mata_kuliah'], $semester);

            return cyraKeepActiveContext($answer, 'jadwal_kuliah', $params);
        }

        $answer = $showAll
            ? tampilJadwalKuliah($conn, null, $hari)
            : tampilJadwalKuliah($conn, $semester, $hari);

        return cyraKeepActiveContext($answer, 'jadwal_kuliah', ['hari' => $hari]);
    }

    if ($type === 'uts') {
        if (!empty($params['mata_kuliah'])) {
            $answer = $showAll
                ? tampilJadwalUjianMataKuliah($conn, 'jadwal_uts', 'UTS', $params['mata_kuliah'], null)
                : tampilJadwalUjianMataKuliah($conn, 'jadwal_uts', 'UTS', $params['mata_kuliah'], $semester);

            return cyraKeepActiveContext($answer, 'uts', $params);
        }

        $answer = $showAll
            ? tampilJadwalUjian($conn, 'jadwal_uts', 'UTS', null)
            : tampilJadwalUjian($conn, 'jadwal_uts', 'UTS', $semester);

        return cyraKeepActiveContext($answer, 'uts');
    }

    if ($type === 'uas') {
        if (!empty($params['mata_kuliah'])) {
            $answer = $showAll
                ? tampilJadwalUjianMataKuliah($conn, 'jadwal_uas', 'UAS', $params['mata_kuliah'], null)
                : tampilJadwalUjianMataKuliah($conn, 'jadwal_uas', 'UAS', $params['mata_kuliah'], $semester);

            return cyraKeepActiveContext($answer, 'uas', $params);
        }

        $answer = $showAll
            ? tampilJadwalUjian($conn, 'jadwal_uas', 'UAS', null)
            : tampilJadwalUjian($conn, 'jadwal_uas', 'UAS', $semester);

        return cyraKeepActiveContext($answer, 'uas');
    }

    if ($type === 'mata_kuliah') {
        $answer = $showAll
            ? tampilMataKuliah($conn, null)
            : tampilMataKuliah($conn, $semester);

        return cyraKeepActiveContext($answer, 'mata_kuliah');
    }

    return null;
}

function cyraLocalAnswer(string $message): ?string
{
    $userText = normalizeTypo(cleanText($message));

    if ($userText === '') {
        return null;
    }

    if (cyraIsNonAcademicScheduleQuestion($userText)) {
        cyraClearLocalPending();
        return cyraFallbackReply();
    }

    if (cyraIsOutOfScopeCasualQuestion($userText)) {
        cyraClearLocalPending();
        return cyraFallbackReply();
    }

    if (in_array(normalizeText($userText), [
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
    ], true)) {
        cyraClearLocalPending();
        return "Halo, selamat datang di CYRA Teknik Informatika. Silakan tanya tentang jadwal kuliah, UTS, UAS, dosen, mata kuliah, FRS, KP, TA, atau pendaftaran Prodi Teknik Informatika.";
    }

    if (cyraIsThanksExpression($userText)) {
        cyraClearLocalPending();
        return "Sama-sama. Senang bisa membantu.";
    }

    if (cyraIsPertanyaanTentangCyra($userText) || containsAny($userText, ['kamu siapa', 'bot ini apa', 'chatbot ini apa'])) {
        cyraClearLocalPending();
        return cyraJawabanTentangCyra($userText);
    }

    if (cyraIsPertanyaanKontakProdi($userText)) {
        cyraClearLocalPending();
        return cyraJawabanKontakProdi();
    }

    if (cyraIsPertanyaanUmumAkademik($userText)) {
        cyraClearLocalPending();
        return cyraJawabanUmumAkademik($userText);
    }

    if (cyraIsPertanyaanFAQMasukCyra($userText)) {
        cyraClearLocalPending();
        return cyraJawabanFAQMasukCyra();
    }

    if (cyraIsPertanyaanFAQAdmin($userText)) {
        cyraClearLocalPending();
        return cyraJawabanFAQAdmin();
    }

    $conn = cyraLocalDatabaseConnection();

    if (!$conn) {
        return 'Koneksi database gagal.';
    }

    $pendingAnswer = cyraAnswerPendingContext($conn, $userText);

    if ($pendingAnswer !== null) {
        return $pendingAnswer;
    }

    if (cyraIsBareTodayQuestion($userText)) {
        $hari = getHariIni();
        $answer = tampilJadwalKuliahHari($conn, $hari, null);

        if (cyraIsHariLiburKuliah($hari)) {
            cyraClearLocalPending();
            return $answer;
        }

        return cyraKeepActiveContext($answer, 'jadwal_kuliah', ['hari' => $hari]);
    }

    if (cyraIsExamRegistrationQuestion($userText)) {
        cyraClearLocalPending();
        return cyraExamRegistrationAnswer($userText);
    }

    if (cyraIsOfficialLinkQuestion($userText)) {
        $faqAnswer = cariJawabanFAQKategori($conn, $userText, 'Umum', 60);

        if ($faqAnswer !== null) {
            cyraClearLocalPending();
            return $faqAnswer;
        }
    }

    if (isTanyaPendaftaran($userText)) {
        cyraClearLocalPending();
        return tampilPendaftaranTeknikInformatika($conn);
    }

    if (containsAny($userText, ['faq'])) {
        cyraClearLocalPending();
        return tampilFAQ($conn, $userText);
    }

    if (containsAny($userText, ['apa itu frs', 'frs itu apa', 'pengertian frs', 'arti frs'])) {
        cyraClearLocalPending();
        return pengertianFRS();
    }

    if (containsAny($userText, ['apa itu kp', 'kp itu apa', 'pengertian kp', 'arti kp', 'apa itu kerja praktik', 'apa itu kerja praktek'])) {
        cyraClearLocalPending();
        return pengertianKP();
    }

    if (containsAny($userText, ['apa itu ta', 'ta itu apa', 'pengertian ta', 'arti ta', 'apa itu tugas akhir', 'tugas akhir itu apa'])) {
        cyraClearLocalPending();
        return pengertianTA();
    }

    if (cyraIsScheduleChangeQuestion($userText)) {
        cyraClearLocalPending();
        return cyraScheduleChangeAnswer();
    }

    if (cyraIsHariApaSajaKuliahQuestion($userText)) {
        return cyraKeepActiveContext(tampilHariKuliahSemester($conn, extractSemester($userText)), 'jadwal_kuliah');
    }

    if (cyraIsWhereToSeeExamScheduleQuestion($userText, 'UTS')) {
        cyraClearLocalPending();
        return cyraWhereToSeeExamScheduleAnswer('UTS');
    }

    if (cyraIsWhereToSeeExamScheduleQuestion($userText, 'UAS')) {
        cyraClearLocalPending();
        return cyraWhereToSeeExamScheduleAnswer('UAS');
    }

    if (cyraIsExamPublishedQuestion($userText, 'UTS')) {
        cyraClearLocalPending();
        return cyraExamPublishedAnswer($conn, 'jadwal_uts', 'UTS');
    }

    if (cyraIsExamPublishedQuestion($userText, 'UAS')) {
        cyraClearLocalPending();
        return cyraExamPublishedAnswer($conn, 'jadwal_uas', 'UAS');
    }

    if (cyraIsExamCourseScheduleQuestion($userText, 'UTS')) {
        $semester = extractSemester($userText);

        if ($semester) {
            return cyraKeepActiveContext(tampilJadwalUjianMataKuliah($conn, 'jadwal_uts', 'UTS', $userText, $semester), 'uts', ['mata_kuliah' => cyraExtractAcademicCourseKeyword($userText)]);
        }

        return cyraKeepActiveContext(tampilJadwalUjianMataKuliah($conn, 'jadwal_uts', 'UTS', $userText), 'uts', ['mata_kuliah' => cyraExtractAcademicCourseKeyword($userText)]);
    }

    if (cyraIsExamCourseScheduleQuestion($userText, 'UAS')) {
        $semester = extractSemester($userText);

        if ($semester) {
            return cyraKeepActiveContext(tampilJadwalUjianMataKuliah($conn, 'jadwal_uas', 'UAS', $userText, $semester), 'uas', ['mata_kuliah' => cyraExtractAcademicCourseKeyword($userText)]);
        }

        return cyraKeepActiveContext(tampilJadwalUjianMataKuliah($conn, 'jadwal_uas', 'UAS', $userText), 'uas', ['mata_kuliah' => cyraExtractAcademicCourseKeyword($userText)]);
    }

    if (cyraIsTaScheduleQuestion($userText)) {
        cyraClearLocalPending();
        return cyraTaScheduleAnswer($conn);
    }

    if (cyraIsCourseRoomQuestion($userText)) {
        cyraClearLocalPending();
        return tampilRuangMataKuliah($conn, $userText, extractSemester($userText));
    }

    if (cyraIsCourseScheduleQuestion($userText)) {
        $semester = extractSemester($userText);

        return cyraKeepActiveContext(
            tampilJadwalKuliahMataKuliah($conn, $userText, $semester),
            'jadwal_kuliah',
            ['mata_kuliah' => cyraExtractAcademicCourseKeyword($userText)]
        );
    }

    if (cyraIsProcedureRequirementQuestion($userText)) {
        $topic = cyraProcedureRequirementTopic($userText);
        return cyraKeepActiveContext(cyraAnswerProcedureRequirements($conn, $topic), 'prosedur_' . $topic);
    }

    if (containsAny($userText, ['frs']) && isTanyaCara($userText)) {
        $singkat = !containsAny($userText, ['lengkap', 'detail', 'semua']);
        return cyraKeepActiveContext(tampilProsedur($conn, 'prosedur_frs', 'Cara Pengisian FRS', $singkat), 'prosedur_frs');
    }

    if (cyraIsKpRequirementQuestion($userText)) {
        return cyraKeepActiveContext(cyraAnswerKpRequirements($conn), 'prosedur_kp');
    }

    if (containsAny($userText, ['kp', 'kerja praktek', 'kerja praktik']) && isTanyaCara($userText)) {
        $singkat = !containsAny($userText, ['lengkap', 'detail', 'semua']);
        return cyraKeepActiveContext(tampilProsedur($conn, 'prosedur_kp', 'Cara Pengajuan KP', $singkat), 'prosedur_kp');
    }

    if (containsAny($userText, ['ta', 'tugas akhir', 'skripsi']) && isTanyaCara($userText)) {
        $singkat = !containsAny($userText, ['lengkap', 'detail', 'semua']);
        return cyraKeepActiveContext(tampilProsedur($conn, 'prosedur_ta', 'Cara Pengajuan TA', $singkat), 'prosedur_ta');
    }

    if (cyraIsPertanyaanDosenMataKuliah($userText)) {
        cyraClearLocalPending();
        return tampilDosenMataKuliah($conn, $userText);
    }

    if (cyraIsPertanyaanMataKuliahDosen($userText)) {
        cyraClearLocalPending();
        return tampilMataKuliahDosen($conn, $userText);
    }

    if (containsAny($userText, ['jadwal uts', 'uts'])) {
        $semester = extractSemester($userText);

        if ($semester) {
            return cyraKeepActiveContext(tampilJadwalUjian($conn, 'jadwal_uts', 'UTS', $semester), 'uts');
        }

        cyraSetLocalPending('uts');
        return "Mau lihat jadwal UTS semester berapa? Ketik angka semester, misalnya 6, atau ketik semua.";
    }

    if (containsAny($userText, ['jadwal uas', 'uas'])) {
        $semester = extractSemester($userText);

        if ($semester) {
            return cyraKeepActiveContext(tampilJadwalUjian($conn, 'jadwal_uas', 'UAS', $semester), 'uas');
        }

        cyraSetLocalPending('uas');
        return "Mau lihat jadwal UAS semester berapa? Ketik angka semester, misalnya 6, atau ketik semua.";
    }

    if (cyraIsPertanyaanRuang($userText) && !containsAny($userText, ['uts', 'uas'])) {
        cyraClearLocalPending();
        return tampilJadwalKuliahRuang($conn, $userText);
    }

    if (cyraIsAcademicScheduleQuestion($userText)) {
        $semester = extractSemester($userText);
        $hari = extractHari($userText);

        if ($hari) {
            if (cyraWantsScheduleSemesterByDay($userText)) {
                return cyraKeepActiveContext(tampilSemesterJadwalKuliahHari($conn, $hari), 'jadwal_kuliah', ['hari' => $hari]);
            }

            $answer = tampilJadwalKuliahHari($conn, $hari, $semester);

            if (cyraIsHariLiburKuliah($hari)) {
                cyraClearLocalPending();
                return $answer;
            }

            return cyraKeepActiveContext($answer, 'jadwal_kuliah', ['hari' => $hari]);
        }

        if (!$semester && isSemua($userText)) {
            return cyraKeepActiveContext(tampilJadwalKuliah($conn, null, null), 'jadwal_kuliah', ['hari' => null]);
        }

        if ($semester) {
            return cyraKeepActiveContext(tampilJadwalKuliah($conn, $semester, $hari), 'jadwal_kuliah', ['hari' => $hari]);
        }

        cyraSetLocalPending('jadwal_kuliah', ['hari' => $hari]);

        if ($hari) {
            return "Mau lihat jadwal kuliah hari " . ucfirst($hari) . " untuk semester berapa? Ketik angka semester, misalnya 4, atau ketik semua.";
        }

        return "Mau lihat jadwal kuliah semester berapa? Ketik angka semester, misalnya 6, atau ketik semua.";
    }

    if (containsAny($userText, ['mata kuliah', 'matakuliah', 'mk tersedia', 'daftar mk'])) {
        $semester = extractSemester($userText);

        if (!$semester && isSemua($userText)) {
            return cyraKeepActiveContext(tampilMataKuliah($conn, null), 'mata_kuliah');
        }

        if ($semester) {
            return cyraKeepActiveContext(tampilMataKuliah($conn, $semester), 'mata_kuliah');
        }

        cyraSetLocalPending('mata_kuliah');
        return "Mau lihat mata kuliah semester berapa? Ketik angka semester, misalnya 6, atau ketik semua.";
    }

    if (cyraIsPertanyaanDaftarDosen($userText) || containsAny($userText, ['dosen', 'pengampu', 'nidn', 'nip'])) {
        cyraClearLocalPending();
        return tampilDosen($conn, $userText);
    }

    $nameLikeText = preg_match('/^[a-zA-Z.\'\s]{3,40}$/', trim($userText));

    if ($nameLikeText) {
        $dosenAnswer = cariJawabanDosen($conn, $userText);

        if ($dosenAnswer !== null) {
            cyraClearLocalPending();
            return $dosenAnswer;
        }
    }

    if (containsAny($userText, ['siapa', 'pak', 'bu', 'bapak', 'ibu'])) {
        $dosenAnswer = cariJawabanDosen($conn, $userText);

        if ($dosenAnswer !== null) {
            cyraClearLocalPending();
            return $dosenAnswer;
        }
    }

    $faqAnswer = cariJawabanFAQ($conn, $userText);

    if ($faqAnswer !== null) {
        cyraClearLocalPending();
        return $faqAnswer;
    }

    $semester = extractSemester($userText);

    if ($semester && preg_match('/^(semester\s*)?[1-9]$/', normalizeText($userText))) {
        cyraSetLocalPending('pilih_data_semester', ['semester' => $semester]);
        return "Semester $semester mau lihat data apa? Ketik jadwal kuliah, mata kuliah, UTS, atau UAS.";
    }

    return cyraFallbackReply();
}
