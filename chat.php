<?php
require 'vendor/autoload.php';
require 'db.php';
session_start();

/**
 * chat.php
 * Full chat endpoint + UI for POPI chat assistant.
 *
 * Changes in this version:
 * - Ensures chat history is associated with a user account when a guest logs in:
 *     - attachSessionHistoryToUser() will move rows for the current session into the logged-in user's history.
 *     - This runs once per session after login and sets a session flag to avoid repeating.
 * - Keeps support for guests using session_id (DB migration must have been applied) and legacy {SESSION:...} prefix.
 * - Stores and fetches history by user_id for logged-in users.
 * - Robust Ollama client (tries /api/generate then falls back to /v1/chat/completions).
 *
 * Requirements:
 * - chat_history table should have a nullable session_id column (VARCHAR(128)).
 * - If migration hasn't been run, legacy messages with {SESSION:...} will still be handled by the attach function.
 */

/* ------------------------------
   Helpers
   ------------------------------ */

/**
 * Return the current user's id (int) or null for guest.
 * Prefer session user_id if already set (e.g., after login), otherwise try to resolve via session email.
 */
function getCurrentUserId($pdo) {
    // If session already contains user_id (set during login), return it.
    if (!empty($_SESSION['user_id'])) {
        return (int) $_SESSION['user_id'];
    }

    // If session contains user_email, look up id in DB.
    if (!empty($_SESSION['user_email'])) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$_SESSION['user_email']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                // Cache into session for future requests
                $_SESSION['user_id'] = (int)$row['id'];
                return (int)$row['id'];
            }
        } catch (Exception $e) {
            // ignore lookup errors, treat as guest
        }
    }

    return null;
}

function getGuestSessionId() {
    return session_id();
}

/**
 * When a guest with session_id later logs in (or is associated with a user),
 * move that session's chat_history rows to the user's account. This:
 *  - Updates rows where session_id matches to set user_id and clear session_id.
 *  - Also handles legacy rows that embed "{SESSION:<token>}" in the message if migration hasn't been applied.
 *
 * This function is safe to call multiple times; we use a session flag to avoid repeated work.
 */
function attachSessionHistoryToUser($pdo, $userId) {
    if (empty($userId)) return;

    // Only run once per PHP session to avoid repeated DB work
    if (!empty($_SESSION['chat_history_attached'])) {
        return;
    }

    $sessionToken = getGuestSessionId();
    if (!$sessionToken) return;

    try {
        $pdo->beginTransaction();

        // 1) If session_id column exists and there are rows with that session, assign them to user
        //    Use user_id IS NULL guard to avoid overwriting records already linked to a user.
        $update1 = $pdo->prepare("UPDATE chat_history SET user_id = ?, session_id = NULL WHERE session_id = ? AND (user_id IS NULL OR user_id = '')");
        $update1->execute([$userId, $sessionToken]);

        // 2) Handle legacy messages that embed the prefix "{SESSION:<token>}" in the message text.
        //    Update user_id and strip the prefix from message body.
        //    MySQL string functions used: LOCATE, SUBSTRING, LTRIM
        $legacyLike = '{SESSION:' . $sessionToken . '}%';
        $selectLegacy = $pdo->prepare("SELECT id FROM chat_history WHERE user_id IS NULL AND message LIKE ?");
        $selectLegacy->execute([$legacyLike]);
        $legacyRows = $selectLegacy->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($legacyRows)) {
            // Strip prefix and set user_id for each matching row
            $updateLegacy = $pdo->prepare("
                UPDATE chat_history
                SET
                    message = LTRIM(SUBSTRING(message, LOCATE('}', message) + 1)),
                    user_id = ?
                WHERE id = ?
            ");
            foreach ($legacyRows as $rowId) {
                $updateLegacy->execute([$userId, $rowId]);
            }
        }

        $pdo->commit();
        // mark as attached so we don't re-run this in the same session
        $_SESSION['chat_history_attached'] = true;
    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
        // Do not block user login if migration fails; log error if you have logging (omitted here).
        $_SESSION['chat_history_attached'] = true; // prevent retry spam; operator can inspect DB
    }
}

/* ------------------------------
   Ollama client
   ------------------------------ */
function callOllamaServer($systemMessage, $userMessage, $model = null) {
    $host = getenv('OLLAMA_HOST') ?: 'http://127.0.0.1:11434';
    $model = $model ?: (getenv('OLLAMA_MODEL') ?: 'qwen3:1.7b');

    // Many Ollama setups accept a single prompt; build a prompt with system + user context.
    $prompt = trim($systemMessage) . "\n\nUser: " . trim($userMessage) . "\nAssistant:";

    // Try /api/generate first (preferred for Ollama)
    $body = [
        'model' => $model,
        'prompt' => $prompt,
        'max_tokens' => 512,
        'temperature' => 0.2,
        'stream' => false
    ];

    $urlGenerate = rtrim($host, '/') . '/api/generate';
    $ch = curl_init($urlGenerate);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response && $httpCode >= 200 && $httpCode < 300) {
        $json = json_decode($response, true);
        if (is_array($json)) {
            // Common shapes:
            if (isset($json['choices'][0]['message']['content'])) {
                return $json['choices'][0]['message']['content'];
            }
            if (isset($json['choices'][0]['text'])) {
                return $json['choices'][0]['text'];
            }
            if (isset($json['result']) && is_string($json['result'])) {
                return $json['result'];
            }
            if (isset($json['output'])) {
                if (is_string($json['output'])) return $json['output'];
                if (is_array($json['output'])) {
                    $parts = [];
                    foreach ($json['output'] as $o) {
                        if (is_string($o)) $parts[] = $o;
                        elseif (isset($o['text'])) $parts[] = $o['text'];
                    }
                    return implode('', $parts);
                }
            }
            if (isset($json['choices'][0]['content'])) {
                $c = $json['choices'][0]['content'];
                if (is_string($c)) return $c;
                if (is_array($c)) {
                    $parts = [];
                    foreach ($c as $p) {
                        if (is_string($p)) $parts[] = $p;
                        elseif (isset($p['text'])) $parts[] = $p['text'];
                        elseif (isset($p['content']) && is_string($p['content'])) $parts[] = $p['content'];
                    }
                    return implode('', $parts);
                }
            }
        }
    }

    // Fallback: OpenAI-like chat completions endpoint
    $fallback = [
        "model" => $model,
        "messages" => [
            ["role" => "system", "content" => $systemMessage],
            ["role" => "user", "content" => $userMessage]
        ]
    ];
    $urlChat = rtrim($host, '/') . '/v1/chat/completions';
    $ch2 = curl_init($urlChat);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_POST, true);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
    curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($fallback));
    curl_setopt($ch2, CURLOPT_TIMEOUT, 60);
    $response2 = curl_exec($ch2);
    $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);

    if ($response2 && $httpCode2 >= 200 && $httpCode2 < 300) {
        $json2 = json_decode($response2, true);
        if (isset($json2['choices'][0]['message']['content'])) {
            return $json2['choices'][0]['message']['content'];
        }
        if (isset($json2['choices'][0]['text'])) {
            return $json2['choices'][0]['text'];
        }
    }

    // All attempts failed
    return null;
}

/* ------------------------------
   If user is logged in this request, attach any guest session history to their user account.
   This ensures that chat history created while the user was a guest will be stored under their user_id.
   ------------------------------ */
$currentUserId = getCurrentUserId($pdo);
if ($currentUserId) {
    attachSessionHistoryToUser($pdo, $currentUserId);
}

/* ------------------------------
   AJAX endpoints
   ------------------------------ */

// Fetch chat history
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fetch_history'])) {
    header('Content-Type: application/json; charset=utf-8');
    $userId = getCurrentUserId($pdo);
    $history = [];

    try {
        if ($userId) {
            $stmt = $pdo->prepare("SELECT message, sender, created_at FROM chat_history WHERE user_id = ? ORDER BY id ASC");
            $stmt->execute([$userId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Guest path: prefer session_id column
            $session = getGuestSessionId();
            $stmt = $pdo->prepare("SELECT message, sender, created_at FROM chat_history WHERE user_id IS NULL AND session_id = ? ORDER BY id ASC");
            $stmt->execute([$session]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                // Fallback to legacy prefix search
                $stmt = $pdo->prepare("SELECT message, sender, created_at FROM chat_history WHERE user_id IS NULL AND message LIKE ? ORDER BY id ASC");
                $stmt->execute(['{SESSION:' . $session . '}%']);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        foreach ($rows as $row) {
            $msg = preg_replace('/^\{SESSION:[^\}]+\}/', '', $row['message']);
            $history[] = [
                'message' => $msg,
                'sender' => $row['sender'],
                'created_at' => $row['created_at'],
            ];
        }

        echo json_encode(['history' => $history]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['history' => []]);
        exit;
    }
}

// Main chat endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chat_message'])) {
    header('Content-Type: application/json; charset=utf-8');
    $userMessage = (string) $_POST['chat_message'];
    $userId = getCurrentUserId($pdo);

    try {
        if ($userId) {
            $stmt = $pdo->prepare("INSERT INTO chat_history (user_id, message, sender) VALUES (?, ?, 'user')");
            $stmt->execute([$userId, $userMessage]);
        } else {
            $guestSession = getGuestSessionId();
            // Try to use session_id column; fallback to legacy prefix if column doesn't exist or insert fails.
            try {
                $stmt = $pdo->prepare("INSERT INTO chat_history (user_id, session_id, message, sender) VALUES (NULL, ?, ?, 'user')");
                $stmt->execute([$guestSession, $userMessage]);
            } catch (PDOException $e) {
                $guestTag = '{SESSION:' . $guestSession . '}';
                $stmt = $pdo->prepare("INSERT INTO chat_history (user_id, message, sender) VALUES (NULL, ?, 'user')");
                $stmt->execute([$guestTag . $userMessage]);
            }
        }

        // System prompt context
        $systemMessage = "You are an AI assistant for Pelikula Cinema, a user-friendly movie booking website. 
Users can easily reserve tickets, view currently showing movies, and choose their preferred screening time. 
The platform allows users to add or remove the quantity of tickets, book in advance, and see a movie's description preview before purchasing. 
Users can check ticket prices ahead of time and watch trailers to help them decide. 
With Gmail integration, Pelikula Cinema sends confirmation emails for successful bookings, allows users to cancel reservations directly through email, 
and provides a simple way to send feedback about their experience. 
Answer user questions accurately and politely, using this context.";

        $model = getenv('OLLAMA_MODEL') ?: 'qwen3:1.7b';
        $reply = callOllamaServer($systemMessage, $userMessage, $model);
        if ($reply === null) {
            $reply = "Sorry, I couldn't communicate with the model server. Please try again later.";
        }

        if ($userId) {
            $stmt = $pdo->prepare("INSERT INTO chat_history (user_id, message, sender) VALUES (?, ?, 'ai')");
            $stmt->execute([$userId, $reply]);
        } else {
            $guestSession = getGuestSessionId();
            try {
                $stmt = $pdo->prepare("INSERT INTO chat_history (user_id, session_id, message, sender) VALUES (NULL, ?, ?, 'ai')");
                $stmt->execute([$guestSession, $reply]);
            } catch (PDOException $e) {
                $guestTag = '{SESSION:' . $guestSession . '}';
                $stmt = $pdo->prepare("INSERT INTO chat_history (user_id, message, sender) VALUES (NULL, ?, 'ai')");
                $stmt->execute([$guestTag . $reply]);
            }
        }

        echo json_encode(["reply" => $reply]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["reply" => "Internal server error. Please try again later."]);
        exit;
    }
}

/* ------------------------------
   HTML + Frontend JS
   ------------------------------ */
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>POPI Chat</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    /* Styles unchanged from previous version */
    :root {
      --accent: #FF4500;
      --bg-main: #f7f8fa;
      --bg-card: #fff;
      --text-main: #1a1a22;
      --text-muted: #5a5a6e;
      --navbar-bg: #e9ecef;
      --navbar-text: #1a1a22;
      --footer-bg: #e9ecef;
      --brand: #FF4500;
      --btn-primary-bg: #FF4500;
      --btn-primary-text: #fff;
      --toggle-btn-bg: #FF4500;
      --toggle-btn-color: #fff;
      --toggle-btn-border: #FF4500;
    }
    body.dark-mode {
      --accent: #0d6efd;
      --bg-main: #10121a;
      --bg-card: #181a20;
      --text-main: #e6e9ef;
      --text-muted: #aab1b8;
      --navbar-bg: #23272f;
      --navbar-text: #fff;
      --footer-bg: #181a20;
      --brand: #0d6efd;
      --btn-primary-bg: #0d6efd;
      --btn-primary-text: #fff;
      --toggle-btn-bg: #23272f;
      --toggle-btn-color: #0d6efd;
      --toggle-btn-border: #0d6efd;
    }
    body, html {
      height: 100%;
      margin: 0;
      padding: 0;
      background: var(--bg-main) !important;
      color: var(--text-main);
      font-family: 'Montserrat', Arial, sans-serif;
      min-height:100vh;
    }
    .navbar { background: var(--navbar-bg) !important; box-shadow:0 2px 12px rgba(0,0,0,0.25);}
    .navbar .navbar-brand, .navbar .nav-link, .navbar .navbar-text { color: var(--navbar-text) !important;}
    .navbar .navbar-brand { color: var(--accent) !important; font-family: 'Montserrat',sans-serif; font-weight:700;}
    .navbar-profile-pic { width: 46px; height: 46px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; }
    .chatgpt-hero {
      min-height: 140px;
      background: url('pictures/banner-cinema.jpg') center/cover no-repeat, linear-gradient(120deg,#0c2340 60%,#1e4356 100%);
      display: flex; align-items: center;
    }
    .chatgpt-hero-content {
      padding: 24px 0 12px 0; width: 100%;
      text-align: center;
      color: #fff;
    }
    .chatgpt-hero-content h1 {
      color: var(--accent);
      font-size: 2.2rem;
      font-family: 'Montserrat',sans-serif;
      font-weight: bold;
      text-shadow:0 3px 20px rgba(0,0,0,0.21);
      letter-spacing:0.03em;
    }
    .chat-app-fixed-container {
      position: fixed;
      left: 0;
      right: 0;
      top: 75px;
      bottom: 0;
      height: calc(100vh - 75px);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-start;
      background: var(--bg-main);
      z-index: 1;
    }
    .chat-container {
      width: 100%;
      max-width: 900px;
      background: var(--bg-card);
      border-radius: 18px;
      box-shadow: 0 4px 18px 0 rgba(0,0,0,0.10),0 2px 7px 0 rgba(0,0,0,0.04);
      display: flex;
      flex-direction: column;
      height: 100%;
      margin-top: 0;
      position: relative;
    }
    .history-btn-row {
      width: 100%;
      display: flex;
      justify-content: flex-end;
      background: transparent;
      padding: 15px 32px 0 32px;
      z-index: 2;
    }
    #chatBox {
      border: none;
      border-radius: 0;
      padding: 22px 32px 22px 32px;
      background: transparent;
      overflow-y: auto;
      font-size:1.12rem;
      flex: 1 1 auto;
      min-height: 0;
      max-height: none;
    }
    .msg-user {
      background: var(--accent);
      color: #fff;
      border-radius: 16px 16px 4px 16px;
      margin-bottom: 8px;
      padding: 10px 18px;
      max-width:85%;
      margin-left: auto;
      text-align:right
    }
    .msg-ai {
      background: linear-gradient(120deg, #eceffc 70%, var(--bg-main) 100%);
      color: var(--text-main);
      border-radius: 16px 16px 16px 4px;
      margin-bottom: 8px;
      padding: 10px 18px;
      max-width:86%;
      margin-right: auto;
      text-align:left
    }
    body.dark-mode .msg-ai {
      background: #262d35;
      color: #e6e9ef;
    }
    .chat-footer {
      background: var(--bg-card);
      border-radius: 0 0 18px 18px;
      padding: 1.0rem 2rem 1.3rem 2rem;
      border-top: 1px solid #ececec;
      flex-shrink: 0;
      width: 100%;
    }
    .chat-gpt-input-group .input-group { width: 100%; }
    .chat-gpt-input-group input { border-radius:0.25rem 0 0 0.25rem; border:2px solid var(--accent); }
    .chat-gpt-input-group button { 
      border-radius:0 0.25rem 0.25rem 0; 
      background:var(--btn-primary-bg); 
      color:var(--btn-primary-text); 
      border:2px solid var(--btn-primary-bg);
      font-weight:700;
    }
    .chat-gpt-input-group button:active, .chat-gpt-input-group button:focus {
      background: var(--accent);
      color:#fff;
    }
    .theme-toggle-btn {
      background: var(--toggle-btn-bg) !important;
      border: 2px solid var(--toggle-btn-border) !important;
      color: var(--toggle-btn-color) !important;
      border-radius: 50%;
      padding: 8px 11px;
      font-size: 1.25rem;
      cursor: pointer;
      transition: all 0.2s;
      margin-right:7px;
    }
    #toggleModeBtn {
      background: var(--accent) !important;
      color: #fff !important;
      border: 2px solid var(--accent) !important;
      transition: all 0.2s;
    }
    #toggleModeBtn:hover {
      transform: scale(1.05);
    }
    .modal-body {
      max-height: 60vh;
      overflow-y: auto;
    }
    @media (max-width: 1200px){
      .chat-container {max-width:99vw;}
    }
    @media (max-width: 750px) {
      .chatgpt-hero-content h1 { font-size:1.3rem; }
      #chatBox {padding:12px 4vw;}
      .chat-footer {padding:0.5rem 1rem 0.7rem 1rem;}
      .history-btn-row {padding: 13px 4vw 0 4vw;}
    }
    @media (max-width: 500px) {
      .chat-container { border-radius:12px;}
      .chatgpt-hero-content { padding:12px 0 7px 0; }
      #chatBox {font-size:1rem;}
      .modal-body { max-height: 46vh;}
    }
  </style>
</head>
<body>
<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="index.php">
      <img src="pictures/gwapobibat1.png" alt="Logo" height="34" class="me-2">
      PELIKULA
    </a>
    <div class="d-flex ms-auto align-items-center">
      <button id="toggleModeBtn" class="btn btn-outline-warning me-3" title="Toggle light/dark mode">
        <i class="bi bi-moon-stars" id="modeIcon"></i>
      </button>
      <?php if (isset($_SESSION['user_phone']) || isset($_SESSION['user_email'])): ?>
        <?php
          // Display name/phone for navbar
          if (isset($_SESSION['user_phone'])) {
              $displayName = substr($_SESSION['user_phone'], -4); // Last 4 digits
              $displayText = '••••' . $displayName;
          } else {
              $displayName = explode('@', $_SESSION['user_email'])[0];
              $displayText = $displayName;
          }
          
          // Profile picture
          $profileImg = !empty($_SESSION['user_picture'])
              ? htmlspecialchars($_SESSION['user_picture'])
              : "https://ui-avatars.com/api/?name=" . urlencode($displayText) . "&background=0D8ABC&color=fff";
        ?>
        <a href="profile.php" title="Go to Profile">
          <img src="<?php echo $profileImg; ?>" class="navbar-profile-pic" alt="Profile">
        </a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<!-- HERO -->
<div class="chatgpt-hero">
  <div class="container chatgpt-hero-content">
    <h1>POPI</h1>
  </div>
</div>
<!-- FIXED CHAT APP -->
<div class="chat-app-fixed-container">
  <div class="chat-container shadow">
    <div class="history-btn-row">
      <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#chatHistoryModal">
        <i class="bi bi-clock-history"></i> View Chat History
      </button>
    </div>
    <div id="chatBox"></div>
    <div class="chat-footer">
      <div class="chat-gpt-input-group">
        <div class="input-group">
          <input type="text" id="chatMessage" class="form-control" placeholder="Type your message..." aria-label="Type your message..." autocomplete="off" />
          <button class="btn" onclick="sendChat()"><i class="bi bi-send"></i></button>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Chat History Modal -->
<div class="modal fade" id="chatHistoryModal" tabindex="-1" aria-labelledby="chatHistoryLabel" aria-hidden="true">
  <div class="modal-dialog  modal-dialog-centered ">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="chatHistoryLabel"><i class="bi bi-clock-history"></i> Chat History</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="historyBox">
        <div class="text-center text-muted">Loading chat history...</div>
      </div>
    </div>
  </div>
</div>
<!-- END CHAT -->

<script>
function appendMsg(isUser, msg) {
  const chatBox = document.getElementById('chatBox');
  const bubble = document.createElement('div');
  bubble.className = isUser ? 'msg-user ms-auto' : 'msg-ai me-auto';
  const safeMsg = (msg === null || msg === undefined) ? '' : String(msg);
  const div = document.createElement('div');
  div.textContent = safeMsg;
  bubble.innerHTML = div.innerHTML.replace(/\n/g,'<br/>');
  chatBox.appendChild(bubble);
  chatBox.scrollTop = chatBox.scrollHeight;
}
async function sendChat() {
  const input = document.getElementById('chatMessage');
  const message = input.value.trim();
  if (!message) return;

  appendMsg(true, message);
  input.value = '';
  input.disabled = true;

  try {
    const res = await fetch('chat.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ chat_message: message })
    });
    if (!res.ok) throw new Error('Network response was not ok');
    const data = await res.json();
    appendMsg(false, data.reply);
  } catch (err) {
    appendMsg(false, `Error: ${err.message}`);
  }
  input.disabled = false;
  input.focus();
}

// Chat history modal fetcher
document.addEventListener('DOMContentLoaded', function() {
  var historyModal = document.getElementById('chatHistoryModal');
  historyModal.addEventListener('show.bs.modal', function() {
    var historyBox = document.getElementById('historyBox');
    historyBox.innerHTML = "<div class='text-center text-muted'>Loading chat history...</div>";
    fetch('chat.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: new URLSearchParams({ fetch_history: 1 })
    }).then(response => response.json())
      .then(data => {
        if (!data.history || data.history.length === 0) {
          historyBox.innerHTML = "<div class='text-center text-muted'>No chat history found.</div>";
          return;
        }
        historyBox.innerHTML = "";
        data.history.forEach(item => {
          var div = document.createElement('div');
          div.className = item.sender === 'user' ? "msg-user ms-auto" : "msg-ai me-auto";
          var txt = document.createElement('div');
          txt.textContent = item.message;
          div.innerHTML = txt.innerHTML.replace(/\n/g,'<br/>');
          historyBox.appendChild(div);
        });
        historyBox.scrollTop = historyBox.scrollHeight;
      })
      .catch(e => {
        historyBox.innerHTML = "<div class='text-danger'>Error loading chat history.</div>";
      });
  });

  const theme = localStorage.getItem('theme') || 'dark';
  setMode(theme);
  const toggleBtn = document.getElementById('toggleModeBtn');
  if (toggleBtn) {
    toggleBtn.addEventListener('click', function() {
      const isDark = document.body.classList.contains('dark-mode');
      setMode(isDark ? 'light' : 'dark');
    });
  }
  const msgInput = document.getElementById('chatMessage');
  if (msgInput) msgInput.focus();
  var chatBox = document.getElementById('chatBox');
  chatBox.scrollTop = chatBox.scrollHeight;
});
function setMode(mode) {
  const dark = (mode === 'dark');
  if (dark) {
    document.body.classList.add('dark-mode');
    document.getElementById('modeIcon').className = 'bi bi-brightness-high';
    localStorage.setItem('theme', 'dark');
  } else {
    document.body.classList.remove('dark-mode');
    document.getElementById('modeIcon').className = 'bi bi-moon-stars';
    localStorage.setItem('theme', 'light');
  }
}
document.getElementById('chatMessage').addEventListener('keydown', function(e) {
  if (e.key === 'Enter') { 
    e.preventDefault();
    sendChat(); 
  }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>