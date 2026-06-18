<?php
require_once dirname(__DIR__, 2) . '/Foundation/Paths.php';

function uqOfficialWebsiteUrl(): string
{
    return 'https://www.uqgresik.ac.id/index.html';
}

function uqOfficialWebsiteCachePath(): string
{
    return cyraRuntimePath('app/uq_official_links.json');
}

function uqNormalizeWhitespace(string $text): string
{
    return trim(preg_replace('/\s+/', ' ', $text));
}

function uqResolveUrl(string $href, string $baseUrl): string
{
    $href = trim($href);

    if ($href === '') {
        return '';
    }

    if (preg_match('/^https?:\/\//i', $href)) {
        return $href;
    }

    $base = parse_url($baseUrl);
    $scheme = $base['scheme'] ?? 'https';
    $host = $base['host'] ?? 'www.uqgresik.ac.id';

    if (strpos($href, '//') === 0) {
        return $scheme . ':' . $href;
    }

    if ($href[0] === '/') {
        return $scheme . '://' . $host . $href;
    }

    $path = $base['path'] ?? '/';
    $dir = rtrim(str_replace('\\', '/', dirname($path)), '/');

    return $scheme . '://' . $host . ($dir !== '' ? $dir . '/' : '/') . $href;
}

function uqFindNearestHeading(DOMElement $link, DOMXPath $xpath): string
{
    $node = $link;

    while ($node instanceof DOMElement) {
        $headings = $xpath->query('.//h1|.//h2|.//h3|.//h4|.//h5|.//h6', $node);

        if ($headings && $headings->length > 0) {
            for ($i = 0; $i < $headings->length; $i++) {
                $heading = uqNormalizeWhitespace($headings->item($i)->textContent ?? '');

                if ($heading !== '') {
                    return $heading;
                }
            }
        }

        $node = $node->parentNode instanceof DOMElement ? $node->parentNode : null;
    }

    return '';
}

function uqFetchOfficialWebsiteLinks(string $url = ''): array
{
    $url = $url !== '' ? $url : uqOfficialWebsiteUrl();
    $context = stream_context_create([
        'http' => [
            'timeout' => 15,
            'user_agent' => 'CYRA Bot/1.0 (+http://localhost/cyra)'
        ]
    ]);

    $html = @file_get_contents($url, false, $context);

    if ($html === false || trim($html) === '') {
        throw new RuntimeException('Gagal mengambil data dari website resmi UQ.');
    }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $links = $xpath->query('//a[@href]');
    $items = [];
    $seen = [];

    foreach ($links as $link) {
        if (!$link instanceof DOMElement) {
            continue;
        }

        $href = trim($link->getAttribute('href'));

        if ($href === '' || strpos($href, '#') === 0 || stripos($href, 'javascript:') === 0) {
            continue;
        }

        $resolvedUrl = uqResolveUrl($href, $url);
        $text = uqNormalizeWhitespace($link->textContent ?? '');
        $title = uqNormalizeWhitespace($link->getAttribute('title'));
        $heading = uqFindNearestHeading($link, $xpath);
        $label = $heading !== '' ? $heading : ($title !== '' ? $title : $text);
        $action = $text !== '' ? $text : ($title !== '' ? $title : 'Buka link');

        if ($label === '' || $resolvedUrl === '') {
            continue;
        }

        $key = strtolower($label . '|' . $resolvedUrl);

        if (isset($seen[$key])) {
            continue;
        }

        $seen[$key] = true;
        $items[] = [
            'label' => $label,
            'action' => $action,
            'url' => $resolvedUrl
        ];
    }

    return [
        'source_url' => $url,
        'fetched_at' => date('c'),
        'items' => $items
    ];
}

function uqSaveOfficialWebsiteCache(array $data): void
{
    $cachePath = uqOfficialWebsiteCachePath();
    $cacheDir = dirname($cachePath);

    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0777, true);
    }

    file_put_contents($cachePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function uqLoadOfficialWebsiteCache(): ?array
{
    $cachePath = uqOfficialWebsiteCachePath();

    if (!file_exists($cachePath)) {
        return null;
    }

    $data = json_decode((string) file_get_contents($cachePath), true);

    return is_array($data) ? $data : null;
}

function uqSaveOfficialWebsiteToDatabase(mysqli $conn, array $data): void
{
    if (!tableExists($conn, 'faq')) {
        return;
    }

    $items = $data['items'] ?? [];

    foreach ($items as $item) {
        $label = trim((string)($item['label'] ?? ''));
        $url = trim((string)($item['url'] ?? ''));

        if ($label === '' || $url === '') {
            continue;
        }

        $answer = $label . ":\n" . $url;
        $category = 'Umum';
        $existingId = 0;
        $select = mysqli_prepare($conn, "SELECT id_faq FROM faq WHERE pertanyaan = ? AND LOWER(kategori) = 'umum' LIMIT 1");

        if (!$select) {
            throw new RuntimeException('Query cek FAQ link UQ gagal: ' . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($select, 's', $label);
        mysqli_stmt_execute($select);
        $result = mysqli_stmt_get_result($select);

        if ($result && ($row = mysqli_fetch_assoc($result))) {
            $existingId = (int)($row['id_faq'] ?? 0);
        }

        if ($existingId > 0) {
            $update = mysqli_prepare($conn, "UPDATE faq SET jawaban = ?, kategori = ? WHERE id_faq = ?");

            if (!$update) {
                throw new RuntimeException('Query update FAQ link UQ gagal: ' . mysqli_error($conn));
            }

            mysqli_stmt_bind_param($update, 'ssi', $answer, $category, $existingId);
            mysqli_stmt_execute($update);
        } else {
            $insert = mysqli_prepare($conn, "INSERT INTO faq (pertanyaan, jawaban, kategori) VALUES (?, ?, ?)");

            if (!$insert) {
                throw new RuntimeException('Query simpan FAQ link UQ gagal: ' . mysqli_error($conn));
            }

            mysqli_stmt_bind_param($insert, 'sss', $label, $answer, $category);
            mysqli_stmt_execute($insert);
        }
    }
}

function uqLoadOfficialWebsiteFromDatabase(?mysqli $conn): ?array
{
    if (!$conn || !tableExists($conn, 'faq')) {
        return null;
    }

    $q = mysqli_query($conn, "SELECT pertanyaan, jawaban FROM faq WHERE LOWER(kategori) = 'umum' AND jawaban REGEXP 'https?://' ORDER BY pertanyaan ASC, id_faq ASC");

    if (!$q || mysqli_num_rows($q) === 0) {
        return null;
    }

    $items = [];
    $sourceUrl = uqOfficialWebsiteUrl();
    $fetchedAt = date('c');

    while ($row = mysqli_fetch_assoc($q)) {
        $answer = (string)($row['jawaban'] ?? '');
        $url = '';

        if (preg_match('/https?:\/\/\S+/i', $answer, $match)) {
            $url = rtrim($match[0], ".,;)");
        }

        if ($url === '') {
            continue;
        }

        $items[] = [
            'label' => $row['pertanyaan'] ?? '',
            'action' => 'Buka link',
            'url' => $url
        ];
    }

    if ($items === []) {
        return null;
    }

    return [
        'source_url' => $sourceUrl,
        'fetched_at' => $fetchedAt,
        'items' => $items
    ];
}

function uqOfficialWebsiteData(bool $forceRefresh = false, ?mysqli $conn = null): array
{
    if (!$forceRefresh) {
        $databaseData = uqLoadOfficialWebsiteFromDatabase($conn);

        if ($databaseData) {
            return $databaseData;
        }
    }

    $cache = uqLoadOfficialWebsiteCache();
    $cacheAge = 0;

    if ($cache && isset($cache['fetched_at'])) {
        $cacheAge = time() - strtotime($cache['fetched_at']);
    }

    if (!$forceRefresh && $cache && $cacheAge > 0 && $cacheAge < 86400) {
        return $cache;
    }

    try {
        $fresh = uqFetchOfficialWebsiteLinks();
        uqSaveOfficialWebsiteCache($fresh);
        return $fresh;
    } catch (Throwable $e) {
        if ($cache) {
            return $cache;
        }

        throw $e;
    }
}

function tampilWebsiteResmiUQ(string $userText = ''): string
{
    $conn = function_exists('cyraLocalDatabaseConnection') ? cyraLocalDatabaseConnection() : null;
    $data = uqOfficialWebsiteData(false, $conn);
    $items = $data['items'] ?? [];
    $keyword = normalizeText(str_replace([
        'link',
        'website',
        'web',
        'situs',
        'resmi',
        'universitas',
        'qomaruddin',
        'uq'
    ], '', $userText));

    $filtered = [];
    $generalRequest = $keyword === '';
    $preferredGeneralKeywords = [
        'website utama',
        'siakad',
        'pmb',
        'teknik informatika',
        'if uqgresik'
    ];

    foreach ($items as $item) {
        $haystack = normalizeText(($item['label'] ?? '') . ' ' . ($item['action'] ?? '') . ' ' . ($item['url'] ?? ''));

        if ($generalRequest) {
            foreach ($preferredGeneralKeywords as $preferredKeyword) {
                if (strpos($haystack, normalizeText($preferredKeyword)) !== false) {
                    $filtered[] = $item;
                    break;
                }
            }

            continue;
        }

        if (strpos($haystack, $keyword) !== false) {
            $filtered[] = $item;
        }
    }

    if (empty($filtered)) {
        $filtered = $items;
    }

    if (empty($filtered)) {
        return 'Data website resmi UQ belum tersedia.';
    }

    $text = "Data link resmi terkait Teknik Informatika Universitas Qomaruddin:\n\n";
    $maxItems = min(count($filtered), 12);

    for ($i = 0; $i < $maxItems; $i++) {
        $item = $filtered[$i];
        $text .= ($i + 1) . '. ' . ($item['label'] ?? '-') . "\n";
        $text .= ($item['url'] ?? '-') . "\n\n";
    }

    if (count($filtered) > $maxItems) {
        $text .= 'Dan ' . (count($filtered) - $maxItems) . " link lainnya.\n";
    }

    $text .= 'Sumber: ' . ($data['source_url'] ?? uqOfficialWebsiteUrl());

    return trim($text);
}
