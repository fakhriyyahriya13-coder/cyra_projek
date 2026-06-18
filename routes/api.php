<?php
/*
 * API/webhook route map for future routing.
 * Current endpoints still run directly.
 */

return [
    'POST /chatbot' => 'app/cyra/chatbot.php',
    'POST /dialogflow/webhook' => 'app/cyra/webhook.php',
];
