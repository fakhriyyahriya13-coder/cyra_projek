<?php
/*
 * Shared Dialogflow client helper for CYRA chat endpoints.
 */

function cyraHasSupportedComposerPhp(): bool
{
    return PHP_VERSION_ID >= 80200;
}

function cyraComposerPhpRequirementMessage(): string
{
    return 'Dependency Composer CYRA membutuhkan PHP minimal 8.2. Versi PHP yang sedang berjalan: ' . PHP_VERSION . '. Gunakan XAMPP/PHP 8.2 atau lebih baru.';
}

if (cyraHasSupportedComposerPhp()) {
    require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
}

function cyraDialogflowProjectId(): string
{
    return getenv('CYRA_DIALOGFLOW_PROJECT_ID') ?: 'chatbot-kampus-lxgv';
}

function cyraDialogflowCredentialsPath(): string
{
    $credentialsPath = realpath(dirname(__DIR__, 3) . '/key.json');

    if ($credentialsPath === false || !file_exists($credentialsPath)) {
        throw new RuntimeException('Credentials Dialogflow tidak ditemukan.');
    }

    return $credentialsPath;
}

function cyraDialogflowCredentials(): array|string
{
    $credentialsJson = trim((string)getenv('CYRA_DIALOGFLOW_CREDENTIALS_JSON'));
    $credentialsBase64 = trim((string)getenv('CYRA_DIALOGFLOW_CREDENTIALS_BASE64'));

    if ($credentialsJson === '' && $credentialsBase64 !== '') {
        $decoded = base64_decode($credentialsBase64, true);

        if ($decoded === false) {
            throw new RuntimeException('CYRA_DIALOGFLOW_CREDENTIALS_BASE64 tidak valid.');
        }

        $credentialsJson = $decoded;
    }

    if ($credentialsJson !== '') {
        $credentials = json_decode($credentialsJson, true);

        if (!is_array($credentials) || empty($credentials['project_id']) || empty($credentials['private_key'])) {
            throw new RuntimeException('Environment credentials Dialogflow tidak valid.');
        }

        return $credentials;
    }

    return cyraDialogflowCredentialsPath();
}

function cyraDetectIntent(
    string $message,
    string $sessionId,
    string $languageCode = 'id',
    array $options = []
): array {
    if (!cyraHasSupportedComposerPhp()) {
        throw new RuntimeException(cyraComposerPhpRequirementMessage());
    }

    $message = trim($message);
    $sessionId = trim($sessionId);

    if ($message === '') {
        throw new InvalidArgumentException('Pesan tidak boleh kosong.');
    }

    if ($sessionId === '') {
        $sessionId = bin2hex(random_bytes(16));
    }

    $fallbackReply = $options['fallback_reply'] ?? (function_exists('cyraFallbackReply') ? cyraFallbackReply() : 'Maaf, CYRA belum menemukan informasi tersebut.');
    $emptyReply = $options['empty_reply'] ?? $fallbackReply;

    $sessionClient = null;

    try {
        $sessionClient = new Google\Cloud\Dialogflow\V2\SessionsClient([
            'credentials' => cyraDialogflowCredentials()
        ]);

        $session = $sessionClient->sessionName(cyraDialogflowProjectId(), $sessionId);

        $textInput = new Google\Cloud\Dialogflow\V2\TextInput();
        $textInput->setText($message);
        $textInput->setLanguageCode($languageCode);

        $queryInput = new Google\Cloud\Dialogflow\V2\QueryInput();
        $queryInput->setText($textInput);

        $response = $sessionClient->detectIntent($session, $queryInput);
        $queryResult = $response->getQueryResult();

        $intent = $queryResult->getIntent();
        $intentName = $intent ? $intent->getDisplayName() : 'Tidak diketahui';
        $reply = trim((string) $queryResult->getFulfillmentText());

        if ($reply === '') {
            $reply = $intentName === 'Default Fallback Intent' ? $fallbackReply : $emptyReply;
        }

        return [
            'reply' => $reply,
            'intent' => $intentName,
            'confidence' => $queryResult->getIntentDetectionConfidence()
        ];
    } finally {
        if ($sessionClient instanceof Google\Cloud\Dialogflow\V2\SessionsClient) {
            $sessionClient->close();
        }
    }
}
