<?php
/*
 * Webhook response and logging helpers.
 * Extracted from app/cyra/webhook.php to keep the webhook endpoint small.
 */
require_once dirname(__DIR__, 2) . '/Foundation/Paths.php';

/* =========================================================
   LOG
========================================================= */
function writeLog($text)
{
    $logDir = cyraRuntimePath('logs');

    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }

    file_put_contents(
        $logDir . '/webhook.log',
        "[" . date('Y-m-d H:i:s') . "] " . $text . PHP_EOL,
        FILE_APPEND
    );
}

/* =========================================================
   RESPONSE
========================================================= */
function jsonResponse($text, $outputContexts = [])
{
    if (function_exists('cyraNormalizeAnswerText')) {
        $text = cyraNormalizeAnswerText($text);
    } else {
        $text = str_replace(["\r\n", "\r"], "\n", (string)$text);
        $text = preg_replace("/[ \t]+\n/", "\n", $text);
        $text = preg_replace("/\n[ \t]*\n[ \t]*\n+/", "\n\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        $text = trim($text);
    }

    if ($text === '') {
        $text = "Maaf, jawaban belum tersedia.";
    }

    writeLog("FINAL RESPONSE: " . $text);

    $response = [
        "fulfillmentText" => $text,
        "fulfillmentMessages" => [
            [
                "text" => [
                    "text" => [$text]
                ]
            ]
        ]
    ];

    if (!empty($outputContexts)) {
        $response["outputContexts"] = $outputContexts;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
