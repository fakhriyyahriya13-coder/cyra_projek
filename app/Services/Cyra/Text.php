<?php
/*
 * Text normalization, intent, parameter, date, and matching helpers.
 * Extracted from app/cyra/webhook.php to keep the webhook endpoint small.
 */

/* =========================================================
   HELPER TEKS
========================================================= */
function cleanText($text)
{
    $text = strtolower(trim((string)$text));
    $text = str_replace("\\", "", $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return $text;
}

function normalizeText($text)
{
    $text = strtolower((string)$text);
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

function normalizeTypo($text)
{
    $text = cleanText($text);

    $replace = [
        // typo umum
        'hati ini' => 'hari ini',
        'hri ini' => 'hari ini',
        'hari ni' => 'hari ini',
        'skrg' => 'sekarang',
        'skrng' => 'sekarang',
        'sekrang' => 'sekarang',
        'pendaftran' => 'pendaftaran',
        'pendaptaran' => 'pendaftaran',
        'pendafataran' => 'pendaftaran',
        'prosdur' => 'prosedur',
        'gimana' => 'bagaimana',
        'gmn' => 'bagaimana',
        'kapn' => 'kapan',
        'syrat' => 'syarat',
        'syrt' => 'syarat',

        // typo jadwal/kuliah
        'jafwal' => 'jadwal',
        'jadwwal' => 'jadwal',
        'jadal' => 'jadwal',
        'jadwal kulih' => 'jadwal kuliah',
        'jadwal kuliha' => 'jadwal kuliah',
        'kuliha' => 'kuliah',
        'kulih' => 'kuliah',
        'matkul' => 'mata kuliah',
        'matakuliah' => 'mata kuliah',
        'pemweb' => 'pemrograman berbasis web',
        'pemrograman web' => 'pemrograman berbasis web',
        'web programming' => 'pemrograman berbasis web',
        'basisdata' => 'basis data',

        // semester
        'smester' => 'semester',
        'smt' => 'semester',
        'smstr' => 'semester',

        // hari dan dosen
        'senen' => 'senin',
        "jum'at" => 'jumat',
        "jum’at" => 'jumat',
        'dosn' => 'dosen',
        'dosenya' => 'dosennya',
        'dosen nya' => 'dosennya',

        // ujian
        'u a s' => 'uas',
        'u a t' => 'uts',
        'u t s' => 'uts',
        'ujian tengah semester' => 'uts',
        'ujian tengah smester' => 'uts',
        'ujian akhir semester' => 'uas',
        'ujian akhir smester' => 'uas',
        'uas tanggal berapa' => 'jadwal uas',
        'uts tanggal berapa' => 'jadwal uts',
        'kapan uas' => 'jadwal uas',
        'kapan uts' => 'jadwal uts',
        'kapan ta' => 'jadwal ta',
        'ta kapan' => 'jadwal ta',

        // kerja praktik
        'kerja prakter' => 'kerja praktek',
        'kerja peraktek' => 'kerja praktek',
        'kerja praktik' => 'kerja praktek',
    ];

    $text = str_replace(array_keys($replace), array_values($replace), $text);
    $text = preg_replace('/\badwal\b/', 'jadwal', $text);
    $text = preg_replace('/\bendaftar\b/', 'mendaftar', $text);
    $text = preg_replace('/\bkulia\b/', 'kuliah', $text);
    $text = preg_replace('/\s+/', ' ', $text);

    return trim($text);
}

function cyraIsThanksExpression($text)
{
    $text = normalizeText(normalizeTypo($text));

    if ($text === '') {
        return false;
    }

    $text = preg_replace('/\b(ok|oke|okay|siap|baik|ya|iya|y|sip|min|cyra)\b/', ' ', $text);
    $text = trim(preg_replace('/\s+/', ' ', $text));

    return containsAny($text, [
        'terima kasih',
        'terimakasih',
        'trimakasih',
        'trima kasih',
        'makasih',
        'makasi',
        'makasii',
        'makacih',
        'mksh',
        'mksih',
        'trims',
        'thanks',
        'thank',
        'thank you',
        'thankyou',
        'thx',
        'tq',
        'ty',
        'tencu',
        'tengkyu',
        'tenkyu',
        'tengkiu',
        'suwun',
        'matur suwun'
    ]);
}

function cyraFallbackReply()
{
    return "Maaf, CYRA hanya membantu info akademik Informatika: jadwal kuliah, UTS/UAS, dosen, mata kuliah, FAQ, serta prosedur FRS/KP/TA.";
}

function cyraDatabaseErrorReply()
{
    return "Maaf, CYRA sedang mengalami kendala saat mengambil data. Silakan coba lagi nanti.";
}

function cyraNormalizeAnswerText($text)
{
    $text = str_replace(["\r\n", "\r"], "\n", (string)$text);
    $text = preg_replace("/[ \t]+\n/", "\n", $text);
    $text = preg_replace("/\n[ \t]*\n[ \t]*\n+/", "\n\n", $text);
    $text = preg_replace("/\n{3,}/", "\n\n", $text);

    return trim($text);
}

function hasAcademicContext($text)
{
    return containsAny($text, [
        'jadwal',
        'jadwal hari',
        'kuliah',
        'perkuliahan',
        'hari ini',
        'senin',
        'selasa',
        'rabu',
        'kamis',
        'jumat',
        'sabtu',
        'minggu',
        'mata kuliah',
        'matkul',
        'semester',
        'ruang',
        'kelas',
        'dosen',
        'uts',
        'uas',
        'frs',
        'kp',
        'kerja praktik',
        'kerja praktek',
        'ta',
        'tugas akhir',
        'prodi',
        'informatika',
        'cyra'
    ]);
}

function hasNonAcademicScheduleContext($text)
{
    return containsAny($text, [
        'kereta',
        'kereta api',
        'pesawat',
        'tiket',
        'bus',
        'kapal',
        'travel',
        'konser',
        'bioskop',
        'film',
        'motor',
        'mobil'
    ]);
}

function hasStrongAcademicContext($text)
{
    return containsAny($text, [
        'kuliah',
        'perkuliahan',
        'mata kuliah',
        'matkul',
        'semester',
        'dosen',
        'uts',
        'uas',
        'frs',
        'kp',
        'kerja praktik',
        'kerja praktek',
        'ta',
        'tugas akhir',
        'prodi',
        'informatika',
        'cyra'
    ]);
}

function cyraIsNonAcademicScheduleQuestion($text)
{
    return containsAny($text, ['jadwal']) &&
        hasNonAcademicScheduleContext($text) &&
        !hasStrongAcademicContext($text);
}

function cyraIsOfficialLinkQuestion($text)
{
    $text = normalizeText(normalizeTypo($text));

    if (containsAny($text, ['siakad', 'pmb', 'pendaftaran mahasiswa baru', 'lppm', 'lpm', 'pasca uq', 'pascasarjana uq'])) {
        return true;
    }

    if (!containsAny($text, ['website', 'web', 'situs', 'link', 'url', 'tautan'])) {
        return false;
    }

    return containsAny($text, [
        'website',
        'web',
        'situs',
        'link',
        'url',
        'tautan',
        'kampus',
        'uq',
        'qomaruddin',
        'universitas qomaruddin',
        'siakad',
        'pmb',
        'pendaftaran mahasiswa baru',
        'lppm',
        'lpm',
        'informatika uq',
        'teknik informatika uq',
        'website informatika',
        'pasca uq',
        'pascasarjana uq'
    ]);
}

function cyraIsAcademicScheduleQuestion($text)
{
    $hasScheduleKeyword = containsAny($text, [
        'jadwal',
        'jadwal kuliah',
        'jadwal kelas',
        'jadwal semester',
        'jadwal hari',
        'kuliah hari',
        'perkuliahan hari'
    ]);

    if (!$hasScheduleKeyword) {
        return false;
    }

    if (hasNonAcademicScheduleContext($text) && !hasStrongAcademicContext($text)) {
        return false;
    }

    return hasAcademicContext($text);
}

function containsAny($text, $keywords)
{
    $normText = normalizeText($text);

    foreach ($keywords as $keyword) {
        $normKeyword = normalizeText($keyword);

        if ($normKeyword === '') {
            continue;
        }

        if (strpos($normKeyword, ' ') !== false) {
            if (strpos($normText, $normKeyword) !== false) {
                return true;
            }
        } else {
            if (preg_match('/\b' . preg_quote($normKeyword, '/') . '\b/', $normText)) {
                return true;
            }
        }
    }

    return false;
}

function isSemua($text)
{
    return containsAny($text, [
        'semua',
        'semuanya',
        'seluruh',
        'all',
        'keseluruhan',
        'semua data',
        'tampilkan semua',
        'lihat semua',
        'daftar semua'
    ]);
}

function isTanyaCara($text)
{
    return containsAny($text, [
        'cara',
        'prosedur',
        'langkah',
        'alur',
        'bagaimana',
        'tahapan',
        'panduan',
        'tutorial'
    ]);
}

function isTanyaPendaftaran($text)
{
    if (containsAny($text, [
        'kp',
        'kerja praktek',
        'kerja praktik',
        'frs',
        'ta',
        'tugas akhir',
        'skripsi',
        'uts',
        'uas'
    ])) {
        return false;
    }

    return containsAny($text, [
        'pendaftaran',
        'cara daftar',
        'prosedur daftar',
        'daftar masuk',
        'masuk universitas',
        'masuk kampus',
        'pmb',
        'mahasiswa baru',
        'calon mahasiswa'
    ]);
}

function getParam($params, $key)
{
    if (!isset($params[$key])) {
        return null;
    }

    if (is_array($params[$key])) {
        return $params[$key][0] ?? null;
    }

    return $params[$key];
}

function isIntent($intent, $name)
{
    return strtolower(trim($intent)) === strtolower(trim($name));
}

function intentIn($intent, $names)
{
    foreach ($names as $name) {
        if (isIntent($intent, $name)) {
            return true;
        }
    }

    return false;
}

function safeValue($row, $keys, $default = '-')
{
    foreach ($keys as $key) {
        if (isset($row[$key]) && trim((string)$row[$key]) !== '') {
            return $row[$key];
        }
    }

    return $default;
}

function extractSemester($text, $params = [])
{
    $semester = getParam($params, 'semester');

    if ($semester !== null && $semester !== '' && is_numeric($semester)) {
        return (int)$semester;
    }

    $number = getParam($params, 'number');

    if ($number !== null && $number !== '' && is_numeric($number)) {
        return (int)$number;
    }

    if (strpos(strtolower($text), 'ganjil') !== false) {
        return 'ganjil';
    }

    if (strpos(strtolower($text), 'genap') !== false) {
        return 'genap';
    }

    if (preg_match('/semester\s*([1-9])/i', $text, $m)) {
        return (int)$m[1];
    }

    if (preg_match('/\b([1-9])\b/', $text, $m)) {
        return (int)$m[1];
    }

    return null;
}

function getHariIni()
{
    $map = [
        'monday'    => 'senin',
        'tuesday'   => 'selasa',
        'wednesday' => 'rabu',
        'thursday'  => 'kamis',
        'friday'    => 'jumat',
        'saturday'  => 'sabtu',
        'sunday'    => 'minggu'
    ];

    return $map[strtolower(date('l'))] ?? '';
}

function normalizeHari($hari)
{
    $hari = cleanText($hari);
    $hari = str_replace("'", "", $hari);

    if ($hari === 'hari ini' || $hari === 'sekarang' || $hari === 'today') {
        return getHariIni();
    }

    if ($hari === 'ahad') {
        return 'minggu';
    }

    if (in_array($hari, ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'])) {
        return $hari;
    }

    return null;
}

function extractHari($text, $params = [])
{
    $text = cleanText($text);

    if (
        strpos($text, 'hari ini') !== false ||
        strpos($text, 'sekarang') !== false ||
        strpos($text, 'today') !== false
    ) {
        return getHariIni();
    }

    $daftarHari = [
        "jum'at" => 'jumat',
        'jumat'  => 'jumat',
        'senin'  => 'senin',
        'selasa' => 'selasa',
        'rabu'   => 'rabu',
        'kamis'  => 'kamis',
        'sabtu'  => 'sabtu',
        'minggu' => 'minggu',
        'ahad'   => 'minggu'
    ];

    foreach ($daftarHari as $kata => $hasil) {
        if (strpos($text, $kata) !== false) {
            return $hasil;
        }
    }

    $hari = getParam($params, 'hari');

    if ($hari !== null && $hari !== '') {
        return normalizeHari($hari);
    }

    return null;
}

function formatTanggal($tanggal)
{
    if (!$tanggal || $tanggal === '0000-00-00' || $tanggal === '-') {
        return '-';
    }

    return date('d-m-Y', strtotime($tanggal));
}

function faqMatch($userText, $question)
{
    return faqMatchScore($userText, $question) >= 78;
}

function faqStopWords()
{
    return [
        'apa',
        'itu',
        'adalah',
        'bagaimana',
        'cara',
        'kapan',
        'siapa',
        'dimana',
        'di',
        'ke',
        'dari',
        'dan',
        'atau',
        'yang',
        'untuk',
        'dengan',
        'saya',
        'mahasiswa',
        'kampus',
        'cyra',
        'tentang',
        'info',
        'informasi',
        'prosedur',
        'langkah',
        'alur'
    ];
}

function faqKeywords($text)
{
    $text = normalizeText($text);
    $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    $stopWords = array_flip(faqStopWords());
    $keywords = [];

    foreach ($words as $word) {
        if (strlen($word) < 2 || isset($stopWords[$word])) {
            continue;
        }

        $keywords[$word] = true;
    }

    return array_keys($keywords);
}

function faqMatchScore($userText, $question, $category = '')
{
    $userText = normalizeText($userText);
    $question = normalizeText($question);
    $category = normalizeText($category);

    if ($userText === '' || $question === '') {
        return 0;
    }

    if (strpos($userText, $question) !== false || strpos($question, $userText) !== false) {
        return 100;
    }

    similar_text($userText, $question, $percent);
    $score = $percent;

    $userKeywords = faqKeywords($userText);
    $questionKeywords = faqKeywords($question . ' ' . $category);

    if ($userKeywords !== [] && $questionKeywords !== []) {
        $matches = array_intersect($userKeywords, $questionKeywords);
        $coverageUser = count($matches) / max(count($userKeywords), 1);
        $coverageQuestion = count($matches) / max(count($questionKeywords), 1);
        $score = max($score, (($coverageUser * 65) + ($coverageQuestion * 35)));

        if (count($matches) >= 2) {
            $score += 12;
        }

        if (count($matches) === 0) {
            $score = min($score, 35);
        }
    }

    return min(100, $score);
}
