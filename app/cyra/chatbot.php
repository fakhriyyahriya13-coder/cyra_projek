<?php
require_once __DIR__ . '/../Services/Cyra/Dialogflow.php';
require_once __DIR__ . '/../Services/Cyra/LocalAnswer.php';

// ================= HEADER =================
header('Content-Type: application/json; charset=utf-8');

// ================= SESSION =================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty(session_id())) {
    session_regenerate_id(true);
}

function jsonResponse(array $data, int $statusCode = 200): void
{
    if (isset($data['reply'])) {
        $data['reply'] = cyraNormalizeAnswerText($data['reply']);
    }

    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ================= AMBIL INPUT =================
$rawInput = file_get_contents("php://input");
$jsonData = json_decode($rawInput, true);

$message = '';

if (is_array($jsonData) && isset($jsonData['message'])) {
    $message = trim((string) $jsonData['message']);
} elseif (isset($_POST['message'])) {
    $message = trim((string) $_POST['message']);
}

if ($message === '') {
    jsonResponse([
        'reply' => 'Pesan tidak boleh kosong.'
    ], 400);
}

try {
    $localReply = cyraLocalAnswer($message);

    if ($localReply !== null) {
        jsonResponse([
            'reply' => $localReply,
            'intent' => 'Local CYRA'
        ]);
    }

    $fallbackReply = cyraFallbackReply();
    $result = cyraDetectIntent($message, session_id(), 'id', [
        'fallback_reply' => $fallbackReply,
        'empty_reply' => $fallbackReply
    ]);

    if (trim((string)($result['reply'] ?? '')) === '' || strpos((string)$result['reply'], 'Dialogflow/webhook') !== false) {
        $result['reply'] = $fallbackReply;
    }

    jsonResponse($result);
} catch (Throwable $e) {
    $localReply = cyraLocalAnswer($message);

    if ($localReply !== null) {
        jsonResponse([
            'reply' => $localReply
        ]);
    }

    jsonResponse([
        'reply' => cyraFallbackReply(),
        'error' => $e->getMessage()
    ], 500);
}
