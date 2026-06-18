<?php

require_once dirname(__DIR__) . '/app/Services/Cyra/Text.php';
require_once dirname(__DIR__) . '/app/Services/Cyra/DatabaseHelpers.php';
require_once dirname(__DIR__) . '/app/Services/Cyra/UqOfficialWebsite.php';
require_once dirname(__DIR__) . '/config/database.php';

try {
    if (!tableExists($conn, 'faq')) {
        $schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');

        if ($schema !== false && trim($schema) !== '') {
            mysqli_multi_query($conn, $schema);

            do {
                if ($result = mysqli_store_result($conn)) {
                    mysqli_free_result($result);
                }
            } while (mysqli_more_results($conn) && mysqli_next_result($conn));
        }
    }

    $data = uqOfficialWebsiteData(true);
    uqSaveOfficialWebsiteToDatabase($conn, $data);

    echo 'Berhasil sinkron data website resmi UQ.' . PHP_EOL;
    echo 'Sumber: ' . ($data['source_url'] ?? uqOfficialWebsiteUrl()) . PHP_EOL;
    echo 'Jumlah link: ' . count($data['items'] ?? []) . PHP_EOL;
    echo 'Cache: ' . uqOfficialWebsiteCachePath() . PHP_EOL;
    echo 'Database: tabel faq kategori Umum' . PHP_EOL;
} catch (Throwable $e) {
    echo 'Gagal sinkron data website resmi UQ: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
