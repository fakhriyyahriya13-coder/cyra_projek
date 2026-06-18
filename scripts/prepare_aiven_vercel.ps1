param(
    [switch]$SkipMigration
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$phpPath = 'C:\xampp\php\php.exe'
$outputDirectory = Join-Path $projectRoot 'deploy-local'
$outputPath = Join-Path $outputDirectory 'vercel-environment.txt'

Set-Location -LiteralPath $projectRoot

function Read-RequiredValue {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Label,
        [string]$Default = ''
    )

    while ($true) {
        $prompt = if ($Default -ne '') { "$Label [$Default]" } else { $Label }
        $value = Read-Host $prompt

        if ([string]::IsNullOrWhiteSpace($value)) {
            $value = $Default
        }

        if (-not [string]::IsNullOrWhiteSpace($value)) {
            return $value.Trim()
        }

        Write-Host "$Label wajib diisi." -ForegroundColor Yellow
    }
}

function Convert-SecureStringToPlainText {
    param(
        [Parameter(Mandatory = $true)]
        [Security.SecureString]$Value
    )

    $pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($Value)

    try {
        return [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer)
    } finally {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer)
    }
}

function Convert-FileToBase64 {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Path
    )

    if (-not (Test-Path -LiteralPath $Path -PathType Leaf)) {
        throw "File tidak ditemukan: $Path"
    }

    return [Convert]::ToBase64String([IO.File]::ReadAllBytes($Path))
}

Write-Host ''
Write-Host 'PERSIAPAN AIVEN MYSQL + VERCEL UNTUK CYRA' -ForegroundColor Cyan
Write-Host 'Jangan gunakan service Kafka. Pilih service MySQL di Aiven.' -ForegroundColor Yellow
Write-Host ''

if (-not (Test-Path -LiteralPath $phpPath -PathType Leaf)) {
    throw "PHP XAMPP tidak ditemukan di $phpPath"
}

$dbHost = Read-RequiredValue 'Aiven MySQL Host'
$dbPort = Read-RequiredValue 'Aiven MySQL Port'
$dbUser = Read-RequiredValue 'Aiven MySQL User' 'avnadmin'
$dbName = Read-RequiredValue 'Aiven Database Name' 'defaultdb'
$dbPasswordSecure = Read-Host 'Aiven MySQL Password' -AsSecureString
$dbPassword = Convert-SecureStringToPlainText $dbPasswordSecure

if ([string]::IsNullOrWhiteSpace($dbPassword)) {
    throw 'Password Aiven wajib diisi.'
}

$caDefault = Join-Path $env:USERPROFILE 'Downloads\ca.pem'
$caPath = Read-RequiredValue 'Lokasi CA certificate Aiven' $caDefault
$keyDefault = Join-Path $projectRoot 'key.json'
$keyPath = Read-RequiredValue 'Lokasi key.json Dialogflow' $keyDefault
$dialogflowProject = Read-RequiredValue 'Dialogflow Project ID' 'chatbot-kampus-lxgv'

$caBase64 = Convert-FileToBase64 $caPath
$dialogflowBase64 = Convert-FileToBase64 $keyPath

$env:DB_HOST = $dbHost
$env:DB_PORT = $dbPort
$env:DB_USER = $dbUser
$env:DB_PASS = $dbPassword
$env:DB_NAME = $dbName
$env:DB_SSL = 'true'
$env:DB_SSL_CA_BASE64 = $caBase64
$env:CYRA_DIALOGFLOW_PROJECT_ID = $dialogflowProject
$env:CYRA_DIALOGFLOW_CREDENTIALS_BASE64 = $dialogflowBase64

Write-Host ''
Write-Host 'Menguji koneksi Aiven...' -ForegroundColor Cyan

& $phpPath -r "require 'config/database.php'; if (!isset(`$conn) || !`$conn instanceof mysqli) { exit(1); } echo 'Koneksi Aiven berhasil.', PHP_EOL;"

if ($LASTEXITCODE -ne 0) {
    throw 'Koneksi Aiven gagal. Periksa Host, Port, User, Password, Database, dan ca.pem.'
}

if (-not $SkipMigration) {
    Write-Host ''
    $answer = Read-Host 'Salin seluruh database CYRA lokal ke Aiven sekarang? (y/n)'

    if ($answer.Trim().ToLowerInvariant() -in @('y', 'yes', 'ya')) {
        & $phpPath (Join-Path $projectRoot 'scripts\migrate_mysql_to_aiven.php')

        if ($LASTEXITCODE -ne 0) {
            throw 'Migrasi database gagal.'
        }
    }
}

if (-not (Test-Path -LiteralPath $outputDirectory)) {
    New-Item -ItemType Directory -Path $outputDirectory | Out-Null
}

$environmentLines = @(
    "DB_HOST=$dbHost"
    "DB_PORT=$dbPort"
    "DB_USER=$dbUser"
    "DB_PASS=$dbPassword"
    "DB_NAME=$dbName"
    'DB_SSL=true'
    "DB_SSL_CA_BASE64=$caBase64"
    "CYRA_DIALOGFLOW_PROJECT_ID=$dialogflowProject"
    "CYRA_DIALOGFLOW_CREDENTIALS_BASE64=$dialogflowBase64"
)

$environmentLines | Set-Content -LiteralPath $outputPath -Encoding UTF8

Write-Host ''
Write-Host 'SELESAI' -ForegroundColor Green
Write-Host "Nilai siap-tempel ke Vercel disimpan di:"
Write-Host $outputPath -ForegroundColor Cyan
Write-Host ''
Write-Host 'File tersebut berisi password dan credential.' -ForegroundColor Yellow
Write-Host 'Jangan upload, commit, atau kirim file itu kepada siapa pun.' -ForegroundColor Yellow

$dbPassword = $null
$dbPasswordSecure = $null
