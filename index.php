<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/app/Services/Cyra/Dialogflow.php';
require_once __DIR__ . '/app/Services/Cyra/LocalAnswer.php';

/* ================= SESSION ================= */
if (!isset($_SESSION['df_session_id']) || empty($_SESSION['df_session_id'])) {
    $_SESSION['df_session_id'] = bin2hex(random_bytes(16));
}

/* ================= WELCOME MESSAGE ================= */
function defaultWelcomeMessage(): array
{
    return [
        [
            'sender' => 'bot',
            'message' => "Halo, selamat datang di CYRA Teknik Informatika.\nSilakan tanya jadwal kuliah, UTS/UAS, dosen, mata kuliah, FAQ, atau prosedur FRS/KP/TA."
        ]
    ];
}

function cyraChatMessageHtml($message): string
{
    $text = htmlspecialchars(cyraNormalizeAnswerText($message), ENT_QUOTES, 'UTF-8');
    $text = preg_replace_callback('/(^|\n)( {2,})/m', function ($matches) {
        return $matches[1] . str_repeat('&nbsp;', strlen($matches[2]));
    }, $text);

    return nl2br($text);
}

/* ================= INIT CHAT ================= */
if (!isset($_SESSION['chat_history']) || !is_array($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = defaultWelcomeMessage();
}

/* ================= AJAX ================= */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $rawInput = file_get_contents("php://input");
    $jsonData = json_decode($rawInput, true);

    if (!is_array($jsonData)) {
        echo json_encode([
            'status' => 'error',
            'reply' => 'Request tidak valid.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $action = trim($jsonData['action'] ?? 'send');

    if ($action === 'clear_chat') {
        $_SESSION['chat_history'] = defaultWelcomeMessage();
        $_SESSION['df_session_id'] = bin2hex(random_bytes(16));
        unset($_SESSION['cyra_local_pending']);

        echo json_encode([
            'status' => 'ok',
            'history' => $_SESSION['chat_history']
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $userText = trim($jsonData['message'] ?? '');

    if ($userText === '') {
        echo json_encode([
            'status' => 'error',
            'reply' => 'Pesan tidak boleh kosong.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $_SESSION['chat_history'][] = [
        'sender' => 'user',
        'message' => $userText
    ];

    $localAnswer = cyraLocalAnswer($userText);

    if ($localAnswer !== null) {
        $responseMessage = $localAnswer;
    } else {
        try {
        $fallbackReply = cyraFallbackReply();
        $dialogflowResult = cyraDetectIntent($userText, $_SESSION['df_session_id'], 'id', [
            'fallback_reply' => $fallbackReply,
            'empty_reply' => $fallbackReply
        ]);

        $responseMessage = $dialogflowResult['reply'];

        if (trim((string)$responseMessage) === '' || strpos((string)$responseMessage, 'Dialogflow/webhook') !== false) {
            $responseMessage = $fallbackReply;
        }
        } catch (Throwable $e) {
        if (function_exists('cyraComposerPhpRequirementMessage') && !cyraHasSupportedComposerPhp()) {
            $responseMessage = cyraLocalAnswer($userText) ?? cyraComposerPhpRequirementMessage();
        } else {
            $responseMessage = cyraLocalAnswer($userText) ?? cyraFallbackReply();
        }
        }
    }

    $responseMessage = cyraNormalizeAnswerText($responseMessage);

    $_SESSION['chat_history'][] = [
        'sender' => 'bot',
        'message' => $responseMessage
    ];

    $connLog = cyraLocalDatabaseConnection();
    if ($connLog) {
        $sessionId = $_SESSION['df_session_id'] ?? '';
        $stmtLog = $connLog->prepare("INSERT INTO chat_logs (session_id, user_message, bot_response) VALUES (?, ?, ?)");
        if ($stmtLog) {
            $stmtLog->bind_param("sss", $sessionId, $userText, $responseMessage);
            $stmtLog->execute();
            $stmtLog->close();
        }
    }

    echo json_encode([
        'status' => 'ok',
        'reply' => $responseMessage
    ], JSON_UNESCAPED_UNICODE);

    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYRA</title>

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-W81FJQZHDP"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }

        gtag("js", new Date());
        gtag("config", "G-W81FJQZHDP");
    </script>

    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-soft: #dbeafe;
            --sky: #0ea5e9;
            --green: #22c55e;
            --text: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --bg: #eef5ff;
            --card: rgba(255, 255, 255, 0.96);
            --bot: #ffffff;
            --user: linear-gradient(135deg, #2563eb, #0ea5e9);
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Arial, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.18), transparent 30%),
                radial-gradient(circle at bottom right, rgba(14, 165, 233, 0.14), transparent 28%),
                linear-gradient(135deg, #eff6ff, #f8fbff);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px;
        }

        .chat-container {
            width: 100%;
            max-width: 460px;
            height: 88vh;
            max-height: 760px;
            min-height: 570px;
            background: var(--card);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(14px);
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            background:
                radial-gradient(circle at 12% 10%, rgba(255, 255, 255, 0.28), transparent 26%),
                linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .header-logo {
            width: 46px;
            height: 46px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
            flex-shrink: 0;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.25);
        }

        .header-text {
            min-width: 0;
        }

        .header-text h1 {
            margin: 0;
            font-size: 22px;
            line-height: 1;
            font-weight: 900;
            letter-spacing: 0.3px;
        }

        .header-text p {
            margin: 5px 0 0;
            font-size: 12px;
            opacity: 0.92;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .status {
            margin-top: 5px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 700;
            color: #dcfce7;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--green);
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.20);
        }

        .clear-btn {
            border: none;
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
            padding: 9px 11px;
            border-radius: 13px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 800;
            transition: 0.2s ease;
            flex-shrink: 0;
        }

        .clear-btn:hover {
            background: rgba(255, 255, 255, 0.28);
            transform: translateY(-1px);
        }

        .chat-box {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            background:
                linear-gradient(rgba(248, 251, 255, 0.94), rgba(244, 248, 255, 0.96)),
                radial-gradient(circle at 18px 18px, rgba(37, 99, 235, 0.06) 2px, transparent 2px);
            background-size: auto, 28px 28px;
            display: flex;
            flex-direction: column;
            gap: 9px;
            scroll-behavior: smooth;
        }

        .chat-box::-webkit-scrollbar {
            width: 7px;
        }

        .chat-box::-webkit-scrollbar-track {
            background: transparent;
        }

        .chat-box::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
        }

        .message-row {
            display: flex;
            width: 100%;
            align-items: flex-end;
            gap: 7px;
            animation: fadeInUp 0.18s ease;
        }

        .message-row.bot {
            justify-content: flex-start;
        }

        .message-row.user {
            justify-content: flex-end;
        }

        .avatar {
            width: 32px;
            height: 32px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 15px;
            box-shadow: 0 7px 16px rgba(15, 23, 42, 0.09);
        }

        .message-row.bot .avatar {
            background: linear-gradient(135deg, #dbeafe, #ffffff);
            border: 1px solid #bfdbfe;
        }

        .message-row.user .avatar {
            background: linear-gradient(135deg, #dcfce7, #ffffff);
            border: 1px solid #bbf7d0;
        }

        .message-content {
            max-width: 78%;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .message-row.user .message-content {
            align-items: flex-end;
        }

        .sender-label {
            font-size: 10.5px;
            color: #64748b;
            font-weight: 800;
            padding: 0 4px;
        }

        .bubble {
            width: fit-content;
            max-width: 100%;
            padding: 10px 12px;
            border-radius: 17px;
            font-size: 13.6px;
            line-height: 1.48;
            white-space: normal;
            word-break: normal;
            overflow-wrap: anywhere;
            box-shadow: 0 7px 16px rgba(15, 23, 42, 0.06);
        }

        .bubble p,
        .bubble ul,
        .bubble ol {
            margin: 0 0 6px;
            padding-left: 18px;
        }

        .bubble li {
            margin: 0 0 3px;
        }

        .bubble p:last-child,
        .bubble ul:last-child,
        .bubble ol:last-child,
        .bubble li:last-child {
            margin-bottom: 0;
        }

        .message-row.bot .bubble {
            background: var(--bot);
            color: var(--text);
            border: 1px solid var(--line);
            border-bottom-left-radius: 6px;
        }

        .message-row.user .bubble {
            background: var(--user);
            color: #fff;
            border-bottom-right-radius: 6px;
            box-shadow: 0 10px 22px rgba(37, 99, 235, 0.22);
        }

        .typing-bubble {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            min-width: 54px;
            justify-content: center;
            padding: 12px;
        }

        .typing-dot {
            width: 7px;
            height: 7px;
            background: #94a3b8;
            border-radius: 50%;
            animation: blink 1.2s infinite;
        }

        .typing-dot:nth-child(2) {
            animation-delay: 0.18s;
        }

        .typing-dot:nth-child(3) {
            animation-delay: 0.36s;
        }

        .chat-input-area {
            padding: 12px;
            background: rgba(255, 255, 255, 0.95);
            border-top: 1px solid var(--line);
        }

        .chat-input-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #fff;
            border: 1px solid #cfe0f7;
            border-radius: 20px;
            padding: 8px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
            transition: 0.2s ease;
        }

        .chat-input-wrap:focus-within {
            border-color: #60a5fa;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.11);
        }

        .chat-input-wrap input {
            flex: 1;
            border: none;
            outline: none;
            background: transparent;
            font-size: 14px;
            color: var(--text);
            padding: 0 7px;
            min-width: 0;
        }

        .chat-input-wrap input::placeholder {
            color: #94a3b8;
        }

        .icon-btn {
            width: 43px;
            height: 43px;
            border: none;
            border-radius: 16px;
            cursor: pointer;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            transition: 0.2s ease;
            flex-shrink: 0;
        }

        .icon-btn:hover {
            transform: translateY(-1px);
        }

        .icon-btn:disabled {
            opacity: 0.55;
            cursor: not-allowed;
            transform: none;
        }

        .send-btn {
            background: linear-gradient(135deg, #2563eb, #0ea5e9);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.22);
        }

        @keyframes blink {
            0%, 100% {
                opacity: 0.25;
                transform: translateY(0);
            }

            50% {
                opacity: 1;
                transform: translateY(-2px);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 520px) {
            body {
                padding: 0;
                background: #f8fbff;
            }

            .chat-container {
                max-width: none;
                width: 100%;
                height: 100vh;
                min-height: 100vh;
                max-height: none;
                border-radius: 0;
                border: none;
            }

            .chat-header {
                padding: 15px 13px;
            }

            .header-logo {
                width: 44px;
                height: 44px;
                border-radius: 15px;
            }

            .header-text h1 {
                font-size: 21px;
            }

            .header-text p {
                max-width: 190px;
                font-size: 11.5px;
            }

            .message-content {
                max-width: 82%;
            }

            .bubble {
                font-size: 13.2px;
            }

            .avatar {
                width: 31px;
                height: 31px;
            }

            .chat-box {
                padding: 13px 11px;
            }
        }
    </style>
</head>

<body>

    <section class="chat-container">
        <div class="chat-header">
            <div class="header-left">
                <div class="header-logo">🤖</div>
                <div class="header-text">
                    <h1>CYRA</h1>
                    <p>Cyber Assistant for Informatics</p>
                    <div class="status">
                        <span class="status-dot"></span>
                        Online
                    </div>
                </div>
            </div>

            <button type="button" class="clear-btn" id="clear-btn">Hapus</button>
        </div>

        <div id="chat-box" class="chat-box">
            <?php foreach ($_SESSION['chat_history'] as $chat): ?>
                <div class="message-row <?= $chat['sender'] === 'user' ? 'user' : 'bot'; ?>">
                    <?php if ($chat['sender'] === 'bot'): ?>
                        <div class="avatar">🤖</div>
                    <?php endif; ?>

                    <div class="message-content">
                        <div class="sender-label"><?= $chat['sender'] === 'user' ? 'Anda' : 'CYRA'; ?></div>
                        <div class="bubble"><?= cyraChatMessageHtml($chat['message']); ?></div>
                    </div>

                    <?php if ($chat['sender'] === 'user'): ?>
                        <div class="avatar">👤</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="chat-input-area">
            <div class="chat-input-wrap">
                <input type="text" id="user-input" placeholder="Tulis pertanyaan Anda..." autocomplete="off">
                <button id="send-btn" class="icon-btn send-btn" type="button" aria-label="Kirim">➤</button>
            </div>
        </div>
    </section>

    <script>
        const chatBox = document.getElementById("chat-box");
        const input = document.getElementById("user-input");
        const sendBtn = document.getElementById("send-btn");
        const clearBtn = document.getElementById("clear-btn");

        /*
            Pending topic dipakai agar jawaban angka seperti "8" tetap dipahami.
            Contoh:
            User tulis: "jadwal uas"
            Bot bertanya: "semester berapa?"
            User jawab: "8"
            Yang dikirim ke Dialogflow: "jadwal uas semester 8"
            Yang tampil di chat tetap: "8"
        */
        let pendingTopic = null;

        function scrollToBottom() {
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        function escapeHtml(text) {
            const div = document.createElement("div");
            div.textContent = text;
            return div.innerHTML;
        }

        function normalizeChatText(text) {
            return String(text || "")
                .replace(/\r\n?/g, "\n")
                .replace(/[ \t]+\n/g, "\n")
                .replace(/\n[ \t]*\n[ \t]*\n+/g, "\n\n")
                .replace(/\n{3,}/g, "\n\n")
                .trim();
        }

        function preserveLeadingIndent(html) {
            return html.replace(/(^|\n)( {2,})/gm, function (_, newline, spaces) {
                return newline + "&nbsp;".repeat(spaces.length);
            });
        }

        function nl2br(text) {
            return preserveLeadingIndent(escapeHtml(normalizeChatText(text))).replace(/\n/g, "<br>");
        }

        function createMessageRow(sender, text) {
            const row = document.createElement("div");
            row.className = "message-row " + (sender === "user" ? "user" : "bot");

            if (sender === "bot") {
                const avatar = document.createElement("div");
                avatar.className = "avatar";
                avatar.textContent = "🤖";
                row.appendChild(avatar);
            }

            const content = document.createElement("div");
            content.className = "message-content";

            const label = document.createElement("div");
            label.className = "sender-label";
            label.textContent = sender === "user" ? "Anda" : "CYRA";

            const bubble = document.createElement("div");
            bubble.className = "bubble";
            bubble.innerHTML = nl2br(text);

            content.appendChild(label);
            content.appendChild(bubble);
            row.appendChild(content);

            if (sender === "user") {
                const avatar = document.createElement("div");
                avatar.className = "avatar";
                avatar.textContent = "👤";
                row.appendChild(avatar);
            }

            return row;
        }

        function addMessage(sender, text) {
            chatBox.appendChild(createMessageRow(sender, text));
            scrollToBottom();
        }

        function renderHistory(history) {
            chatBox.innerHTML = "";

            history.forEach(chat => {
                chatBox.appendChild(createMessageRow(chat.sender, chat.message));
            });

            scrollToBottom();
        }

        function addTyping() {
            removeTyping();

            const row = document.createElement("div");
            row.className = "message-row bot";
            row.id = "typing-row";

            const avatar = document.createElement("div");
            avatar.className = "avatar";
            avatar.textContent = "🤖";

            const content = document.createElement("div");
            content.className = "message-content";

            const label = document.createElement("div");
            label.className = "sender-label";
            label.textContent = "CYRA";

            const bubble = document.createElement("div");
            bubble.className = "bubble typing-bubble";
            bubble.innerHTML = `
                <span class="typing-dot"></span>
                <span class="typing-dot"></span>
                <span class="typing-dot"></span>
            `;

            content.appendChild(label);
            content.appendChild(bubble);
            row.appendChild(avatar);
            row.appendChild(content);

            chatBox.appendChild(row);
            scrollToBottom();
        }

        function removeTyping() {
            const typingRow = document.getElementById("typing-row");
            if (typingRow) {
                typingRow.remove();
            }
        }

        function normalizeMessage(text) {
            return text.toLowerCase().trim().replace(/\s+/g, " ");
        }

        function messageNeedsSemester(text) {
            const msg = normalizeMessage(text);

            if (msg.includes("semester") || msg.includes("semua")) {
                return null;
            }

            if (msg === "jadwal uas" || msg === "uas") {
                return "jadwal uas";
            }

            if (msg === "jadwal uts" || msg === "uts") {
                return "jadwal uts";
            }

            if (msg === "jadwal kuliah" || msg === "jadwal kelas") {
                return "jadwal kuliah";
            }

            if (msg === "mata kuliah" || msg === "matakuliah" || msg === "mk") {
                return "mata kuliah";
            }

            return null;
        }

        function buildMessageForDialogflow(displayMessage) {
            const msg = normalizeMessage(displayMessage);

            if (pendingTopic !== null) {
                if (/^[1-9]$/.test(msg)) {
                    const fixedMessage = pendingTopic + " semester " + msg;
                    pendingTopic = null;
                    return fixedMessage;
                }

                if (msg === "semua" || msg === "all") {
                    const fixedMessage = "semua " + pendingTopic;
                    pendingTopic = null;
                    return fixedMessage;
                }
            }

            const topic = messageNeedsSemester(displayMessage);

            if (topic !== null) {
                pendingTopic = topic;
            } else {
                pendingTopic = null;
            }

            return displayMessage;
        }

        async function sendMessage(customMessage = null) {
            const message = customMessage !== null ? customMessage.trim() : input.value.trim();

            if (message === "") {
                return;
            }

            const messageForDialogflow = buildMessageForDialogflow(message);

            addMessage("user", message);

            input.value = "";
            input.focus();
            sendBtn.disabled = true;

            addTyping();

            try {
                const response = await fetch(window.location.href, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        action: "send",
                        message: messageForDialogflow
                    })
                });

                const data = await response.json();

                removeTyping();
                addMessage("bot", data.reply || "Maaf, tidak ada respons.");
            } catch (error) {
                removeTyping();
                addMessage("bot", "Terjadi kesalahan saat menghubungi server.");
                console.error(error);
            } finally {
                sendBtn.disabled = false;
            }
        }

        async function clearChat() {
            const yakin = confirm("Hapus semua riwayat chat?");
            if (!yakin) {
                return;
            }

            try {
                const response = await fetch(window.location.href, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        action: "clear_chat"
                    })
                });

                const data = await response.json();

                if (data.status === "ok" && Array.isArray(data.history)) {
                    renderHistory(data.history);
                    input.focus();
                }
            } catch (error) {
                console.error(error);
                alert("Gagal menghapus chat.");
            }
        }

        sendBtn.addEventListener("click", () => sendMessage());
        clearBtn.addEventListener("click", clearChat);

        input.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                sendMessage();
            }
        });

        scrollToBottom();
        input.focus();
    </script>

</body>

</html>
