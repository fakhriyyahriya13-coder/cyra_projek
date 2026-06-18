<?php

require_once dirname(__DIR__) . '/app/Services/Cyra/Text.php';
require_once dirname(__DIR__) . '/config/database.php';

$datasetPath = $argv[1] ?? 'D:\\TA\\DATA\\dataset_cyra.xlsx';
$fresh = !in_array('--append', $argv, true);

if (!extension_loaded('zip')) {
    fwrite(STDERR, "Extension PHP zip belum aktif.\n");
    exit(1);
}

if (!is_file($datasetPath)) {
    fwrite(STDERR, "File dataset tidak ditemukan: $datasetPath\n");
    exit(1);
}

if (!isset($conn) || !$conn instanceof mysqli) {
    fwrite(STDERR, "Koneksi database gagal.\n");
    exit(1);
}

class CyraXlsxReader
{
    private ZipArchive $zip;
    private array $sharedStrings = [];
    private array $sheetPaths = [];

    public function __construct(string $path)
    {
        $this->zip = new ZipArchive();

        if ($this->zip->open($path) !== true) {
            throw new RuntimeException("Gagal membuka file Excel: $path");
        }

        $this->loadSharedStrings();
        $this->loadSheetPaths();
    }

    public function close(): void
    {
        $this->zip->close();
    }

    public function sheetRows(string $sheetName): array
    {
        if (!isset($this->sheetPaths[$sheetName])) {
            return [];
        }

        $dom = $this->domFromZip($this->sheetPaths[$sheetName]);
        $xpath = $this->spreadsheetXpath($dom);
        $rows = [];

        foreach ($xpath->query('//x:sheetData/x:row') as $row) {
            $values = [];

            foreach ($xpath->query('./x:c', $row) as $cell) {
                $index = $this->columnNumber($cell->getAttribute('r')) - 1;
                $type = $cell->getAttribute('t');
                $value = '';

                if ($type === 's') {
                    $v = $xpath->query('./x:v', $cell)->item(0);
                    $value = $this->sharedStrings[(int)($v ? $v->textContent : 0)] ?? '';
                } elseif ($type === 'inlineStr') {
                    $t = $xpath->query('.//x:t', $cell)->item(0);
                    $value = $t ? $t->textContent : '';
                } else {
                    $v = $xpath->query('./x:v', $cell)->item(0);
                    $value = $v ? $v->textContent : '';
                }

                $values[$index] = trim((string)$value);
            }

            if ($values !== []) {
                ksort($values);
                $rows[] = $values;
            }
        }

        return $this->assocRows($rows);
    }

    private function assocRows(array $rows): array
    {
        if (count($rows) < 2) {
            return [];
        }

        $headers = [];

        foreach ($rows[0] as $index => $header) {
            $headers[$index] = normalizeText($header);
        }

        $assoc = [];

        foreach (array_slice($rows, 1) as $row) {
            $item = [];
            $hasValue = false;

            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $value = trim((string)($row[$index] ?? ''));
                $item[$header] = $value;

                if ($value !== '') {
                    $hasValue = true;
                }
            }

            if ($hasValue) {
                $assoc[] = $item;
            }
        }

        return $assoc;
    }

    private function loadSharedStrings(): void
    {
        if ($this->zip->locateName('xl/sharedStrings.xml') === false) {
            return;
        }

        $dom = $this->domFromZip('xl/sharedStrings.xml');
        $xpath = $this->spreadsheetXpath($dom);

        foreach ($xpath->query('//x:si') as $si) {
            $text = '';

            foreach ($xpath->query('.//x:t', $si) as $t) {
                $text .= $t->textContent;
            }

            $this->sharedStrings[] = $text;
        }
    }

    private function loadSheetPaths(): void
    {
        $relsDom = $this->domFromZip('xl/_rels/workbook.xml.rels');
        $relsXpath = new DOMXPath($relsDom);
        $relsXpath->registerNamespace('pkg', 'http://schemas.openxmlformats.org/package/2006/relationships');

        $relMap = [];

        foreach ($relsXpath->query('//pkg:Relationship') as $relationship) {
            $target = ltrim($relationship->getAttribute('Target'), '/');

            if (strpos($target, 'xl/') !== 0) {
                $target = 'xl/' . $target;
            }

            $relMap[$relationship->getAttribute('Id')] = $target;
        }

        $workbookDom = $this->domFromZip('xl/workbook.xml');
        $workbookXpath = $this->spreadsheetXpath($workbookDom);

        foreach ($workbookXpath->query('//x:sheets/x:sheet') as $sheet) {
            $name = $sheet->getAttribute('name');
            $rid = $sheet->getAttributeNS(
                'http://schemas.openxmlformats.org/officeDocument/2006/relationships',
                'id'
            );

            if (isset($relMap[$rid])) {
                $this->sheetPaths[$name] = $relMap[$rid];
            }
        }
    }

    private function domFromZip(string $name): DOMDocument
    {
        $content = $this->zip->getFromName($name);

        if ($content === false) {
            throw new RuntimeException("File XML tidak ditemukan di Excel: $name");
        }

        $dom = new DOMDocument();
        $dom->loadXML($content);

        return $dom;
    }

    private function spreadsheetXpath(DOMDocument $dom): DOMXPath
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        return $xpath;
    }

    private function columnNumber(string $cellReference): int
    {
        preg_match('/^([A-Z]+)/', $cellReference, $match);
        $letters = $match[1] ?? 'A';
        $number = 0;

        foreach (str_split($letters) as $letter) {
            $number = ($number * 26) + (ord($letter) - 64);
        }

        return $number;
    }
}

function runSchema(mysqli $conn): void
{
    $schemaPath = dirname(__DIR__) . '/database/schema.sql';
    $schema = file_get_contents($schemaPath);

    if ($schema === false || trim($schema) === '') {
        throw new RuntimeException("File schema tidak ditemukan: $schemaPath");
    }

    if (!mysqli_multi_query($conn, $schema)) {
        throw new RuntimeException('Gagal menjalankan schema: ' . mysqli_error($conn));
    }

    do {
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    } while (mysqli_more_results($conn) && mysqli_next_result($conn));
}

function truncateTables(mysqli $conn, array $tables): void
{
    mysqli_query($conn, 'SET FOREIGN_KEY_CHECKS=0');

    foreach ($tables as $table) {
        mysqli_query($conn, "TRUNCATE TABLE `$table`");
    }

    mysqli_query($conn, 'SET FOREIGN_KEY_CHECKS=1');
}

function insertRows(mysqli $conn, string $table, array $columns, array $rows): int
{
    if ($rows === []) {
        return 0;
    }

    $columnSql = implode(', ', array_map(fn ($column) => "`$column`", array_keys($columns)));
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $types = str_repeat('s', count($columns));
    $stmt = mysqli_prepare($conn, "INSERT INTO `$table` ($columnSql) VALUES ($placeholders)");

    if (!$stmt) {
        throw new RuntimeException("Prepare insert $table gagal: " . mysqli_error($conn));
    }

    $count = 0;

    foreach ($rows as $row) {
        $values = [];
        $skip = false;

        foreach ($columns as $dbColumn => $source) {
            if (is_callable($source)) {
                $value = $source($row);
            } else {
                $value = valueFromRow($row, (array)$source);
            }

            $values[] = $value;
        }

        foreach ($values as $value) {
            if (trim((string)$value) !== '') {
                $skip = false;
                break;
            }

            $skip = true;
        }

        if ($skip) {
            continue;
        }

        mysqli_stmt_bind_param($stmt, $types, ...$values);

        if (!mysqli_stmt_execute($stmt)) {
            throw new RuntimeException("Insert $table gagal: " . mysqli_stmt_error($stmt));
        }

        $count++;
    }

    mysqli_stmt_close($stmt);

    return $count;
}

function valueFromRow(array $row, array $keys, string $default = ''): string
{
    foreach ($keys as $key) {
        $normalized = normalizeText($key);

        if (isset($row[$normalized]) && trim((string)$row[$normalized]) !== '') {
            return trim((string)$row[$normalized]);
        }
    }

    return $default;
}

function requiredValue(array $row, array $keys, string $default = '-'): string
{
    $value = valueFromRow($row, $keys);

    return $value !== '' ? $value : $default;
}

try {
    runSchema($conn);

    $reader = new CyraXlsxReader($datasetPath);
    $tables = [
        'jadwal_uas',
        'jadwal_uts',
        'jadwal_kuliah',
        'mata_kuliah',
        'dosen',
        'faq',
        'prosedur_frs',
        'prosedur_kp',
        'prosedur_ta',
    ];

    if ($fresh) {
        truncateTables($conn, $tables);
    }

    $result = [];
    $result['jadwal_uas'] = insertRows($conn, 'jadwal_uas', [
        'mata_kuliah' => ['mata_kuliah'],
        'tanggal' => ['tanggal'],
        'jam_mulai' => ['jam_mulai'],
        'jam_selesai' => ['jam_selesai'],
        'ruang' => ['ruang'],
        'semester' => ['semester'],
    ], $reader->sheetRows('jadwal_uas'));

    $result['jadwal_uts'] = insertRows($conn, 'jadwal_uts', [
        'mata_kuliah' => ['mata_kuliah'],
        'tanggal' => ['tanggal'],
        'jam_mulai' => ['jam_mulai'],
        'jam_selesai' => ['jam_selesai'],
        'ruang' => ['ruang'],
        'semester' => ['semester'],
    ], $reader->sheetRows('jadwal_uts'));

    $result['jadwal_kuliah'] = insertRows($conn, 'jadwal_kuliah', [
        'semester' => ['semester'],
        'mata_kuliah' => ['mata_kuliah'],
        'dosen' => ['dosen'],
        'hari' => ['hari'],
        'jam_mulai' => ['jam_mulai'],
        'jam_selesai' => ['jam_selesai'],
        'ruang' => ['ruang'],
    ], $reader->sheetRows('jadwal_kuliah'));

    $result['mata_kuliah'] = insertRows($conn, 'mata_kuliah', [
        'semester' => ['semester'],
        'kode_mk' => ['kode_mk', 'kode_matkul'],
        'nama_mata_kuliah' => ['nama_mata_kuliah', 'nama_matkul', 'mata_kuliah'],
        'sks' => ['sks'],
    ], $reader->sheetRows('mata_kuliah'));

    $result['dosen'] = insertRows($conn, 'dosen', [
        'nidn' => fn ($row) => requiredValue($row, ['nidn', 'nip', 'nidn_nip']),
        'nama_dosen' => fn ($row) => requiredValue($row, ['nama_dosen', 'nama']),
        'email' => ['email'],
        'no_hp' => ['no_hp', 'telepon', 'hp'],
        'keahlian' => ['keahlian'],
    ], $reader->sheetRows('dosen'));

    $result['faq'] = insertRows($conn, 'faq', [
        'pertanyaan' => ['pertanyaan'],
        'jawaban' => ['jawaban'],
        'kategori' => fn ($row) => requiredValue($row, ['kategori'], 'UMUM'),
    ], $reader->sheetRows('faq'));

    $result['prosedur_frs'] = insertRows($conn, 'prosedur_frs', [
        'judul' => ['judul'],
        'deskripsi' => ['deskripsi'],
    ], $reader->sheetRows('prosedur_frs'));

    $result['prosedur_kp'] = insertRows($conn, 'prosedur_kp', [
        'judul' => ['judul'],
        'deskripsi' => ['deskripsi'],
    ], $reader->sheetRows('prosedur_kp'));

    $result['prosedur_ta'] = insertRows($conn, 'prosedur_ta', [
        'judul' => ['judul'],
        'deskripsi' => ['deskripsi'],
    ], $reader->sheetRows('prosedur_ta'));

    $reader->close();

    echo 'Berhasil import dataset CYRA.' . PHP_EOL;
    echo 'File: ' . $datasetPath . PHP_EOL;
    echo 'Mode: ' . ($fresh ? 'fresh/import ulang tabel akademik' : 'append/tambah data') . PHP_EOL;

    foreach ($result as $table => $count) {
        echo '- ' . $table . ': ' . $count . ' data' . PHP_EOL;
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'Gagal import dataset CYRA: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
