const chatBox = document.getElementById("chat-box");
const input = document.getElementById("user-input");
const sendBtn = document.getElementById("send-btn");
const cyraFallbackReply = "Maaf, CYRA hanya membantu info akademik Informatika: jadwal kuliah, UTS/UAS, dosen, mata kuliah, FAQ, serta prosedur FRS/KP/TA.";

function normalizeChatText(text) {
    return String(text || "")
        .replace(/\r\n?/g, "\n")
        .replace(/[ \t]+\n/g, "\n")
        .replace(/\n[ \t]*\n[ \t]*\n+/g, "\n\n")
        .replace(/\n{3,}/g, "\n\n")
        .trim();
}

// ================= TAMBAH PESAN =================
function addMessage(sender, text) {
    const bubble = document.createElement("div");
    bubble.className = sender === "user" ? "user-message" : "bot-message";
    bubble.innerText = normalizeChatText(text);

    chatBox.appendChild(bubble);
    chatBox.scrollTop = chatBox.scrollHeight;
}

// ================= TYPING =================
function addTyping() {
    removeTyping();

    const bubble = document.createElement("div");
    bubble.className = "bot-message";
    bubble.id = "typing";
    bubble.innerText = "Mengetik...";

    chatBox.appendChild(bubble);
    chatBox.scrollTop = chatBox.scrollHeight;
}

function removeTyping() {
    const typing = document.getElementById("typing");
    if (typing) typing.remove();
}

// ================= KIRIM PESAN =================
function sendMessage() {
    const message = input.value.trim();

    if (message === "") return;

    addMessage("user", message);
    input.value = "";
    input.focus();

    addTyping();

    fetch("app/cyra/chatbot.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8"
        },
        body: "message=" + encodeURIComponent(message)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error("HTTP Error: " + response.status);
        }
        return response.json();
    })
    .then(data => {
        removeTyping();

        console.log("Response dari chatbot.php:", data);

        const botReply =
            data.reply ||
            data.fulfillmentText ||
            data.queryResult?.fulfillmentText ||
            data.queryResult?.fulfillmentMessages?.[0]?.text?.text?.[0] ||
            cyraFallbackReply;

        addMessage("bot", botReply.includes("Dialogflow/webhook") ? cyraFallbackReply : botReply);
    })
    .catch(error => {
        console.error("Error:", error);
        removeTyping();
        addMessage("bot", cyraFallbackReply);
    });
}

// ================= EVENT =================
sendBtn.addEventListener("click", sendMessage);

input.addEventListener("keypress", function (e) {
    if (e.key === "Enter") {
        sendMessage();
    }
});
