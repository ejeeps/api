<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['user_level'] !== 'passenger') {
    if (!isset($dashboard_view)) {
        header("Location: ../../index.php?login=1&error=" . urlencode("Please login to access this page."));
        exit;
    }
    header("Location: index.php?login=1&error=" . urlencode("Please login to access this page."));
    exit;
}

require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../controller/passenger/get_passengers_info.php';
require_once __DIR__ . '/../../controller/passenger/CustomerServiceController.php';

$passengerInfo = getPassengerInfo($pdo, (int)$_SESSION['user_id']);
if (!$passengerInfo) {
    $redirectPath = isset($dashboard_view) ? 'index.php' : '../../index.php';
    header("Location: " . $redirectPath . "?login=1&error=" . urlencode("Passenger information not found."));
    exit;
}

if (isset($dashboard_view)) {
    $basePath = '';
} else {
    $basePath = '../../';
}
$imageBasePath = $basePath;
$isEmbed = isset($_GET['embed']) && $_GET['embed'] === '1';

ejeepCustomerServiceEnsureTables($pdo);
$identity = ejeepCustomerServicePassengerIdentity($pdo, (int)$_SESSION['user_id']);
if (!$identity) {
    $redirectPath = isset($dashboard_view) ? 'index.php' : '../../index.php';
    header("Location: " . $redirectPath . "?login=1&error=" . urlencode("Passenger profile not found."));
    exit;
}

$error = null;
$success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = ejeepCustomerServiceHandlePost($pdo, (int)$_SESSION['user_id'], $identity);
    $error = $result['error'];
    $success = $result['success'];
}

$conversationId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
$conversations = ejeepCustomerServiceListConversations($pdo, $identity);
if ($conversationId <= 0 && !empty($conversations)) {
    $conversationId = (int)$conversations[0]['id'];
}
$activeConversation = $conversationId > 0 ? ejeepCustomerServiceGetConversation($pdo, $conversationId, $identity) : null;
$messages = $activeConversation ? ejeepCustomerServiceListMessages($pdo, (int)$activeConversation['id']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=no">
    <meta name="theme-color" content="#16a34a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="E-JEEP Passenger">
    <meta name="description" content="E-JEEP Passenger Support">
    <link rel="manifest" href="<?php echo htmlspecialchars($basePath); ?>manifest.json">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($basePath); ?>assets/icons/icon-192.png">
    <title>Customer Service - E-JEEP</title>
    <link href="<?php echo htmlspecialchars($basePath); ?>assets/style/index.css" rel="stylesheet" type="text/css">
    <link href="<?php echo htmlspecialchars($basePath); ?>assets/style/dashboard.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="<?php echo htmlspecialchars($basePath); ?>assets/script/pwa.js"></script>
    <style>
        .cs-layout {
            display: grid;
            gap: 14px;
            grid-template-columns: 1fr;
        }
        .cs-card {
            background: #fff;
            padding: 14px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            border-radius: 14px;
        }
        .cs-list {
            max-height: 360px;
            overflow: auto;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
        }
        .cs-item {
            display: block;
            padding: 10px;
            border-bottom: 1px solid #f1f5f9;
            text-decoration: none;
            color: #0f172a;
            background: #fff;
        }
        .cs-item.active {
            background: #f0fdf4;
        }
        .cs-thread {
            max-height: 420px;
            overflow: auto;
            border: 1px solid #e5e7eb;
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 12px;
        }
        .cs-bubble-wrap {
            margin-bottom: 10px;
            display: flex;
        }
        .cs-bubble-wrap.customer {
            justify-content: flex-end;
        }
        .cs-bubble-wrap.admin {
            justify-content: flex-start;
        }
        .cs-bubble {
            max-width: 82%;
            border: 1px solid #dbeafe;
            background: #fff;
            padding: 8px 10px;
            border-radius: 12px;
        }
        .cs-bubble-wrap.customer .cs-bubble {
            border-color: #bbf7d0;
            background: #ecfdf5;
        }
        .cs-meta {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 4px;
        }
        .cs-alert {
            padding: 10px;
            margin-bottom: 12px;
            border: 1px solid;
        }
        .cs-alert.error {
            border-color: #fecaca;
            background: #fef2f2;
            color: #991b1b;
        }
        .cs-alert.success {
            border-color: #bbf7d0;
            background: #f0fdf4;
            color: #166534;
        }
        .cs-ticket-card {
            border: 1px solid #dcfce7;
            background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 12px;
        }
        .cs-ticket-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }
        .cs-ticket-head h4 {
            margin: 0;
            font-size: 0.95rem;
            color: #166534;
        }
        .cs-pill {
            font-size: 11px;
            background: #dcfce7;
            color: #166534;
            border-radius: 999px;
            padding: 4px 8px;
            font-weight: 600;
        }
        .cs-input,
        .cs-select,
        .cs-textarea {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 10px 11px;
            font-size: 14px;
            background: #fff;
            outline: none;
        }
        .cs-input:focus,
        .cs-select:focus,
        .cs-textarea:focus {
            border-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.12);
        }
        .cs-textarea {
            resize: vertical;
            min-height: 78px;
        }
        .cs-send-btn {
            border: none;
            border-radius: 10px;
            padding: 10px 14px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #fff;
            font-weight: 600;
            cursor: pointer;
        }
        .cs-send-btn:hover {
            filter: brightness(1.04);
        }
        .cs-file-input {
            display: none;
        }
        .cs-file-label {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            color: #334155;
            cursor: pointer;
        }
        .cs-file-label:hover {
            background: #e2e8f0;
        }
        .cs-file-name {
            font-size: 12px;
            color: #64748b;
            min-height: 16px;
            line-height: 1.2;
            max-width: 220px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .cs-attach-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
        }
        .cs-attach-row .cs-file-label {
            flex-shrink: 0;
        }
        .cs-attachment-list {
            margin-top: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .cs-attachment-image {
            width: 140px;
            max-width: 100%;
            border-radius: 10px;
            border: 1px solid #dbeafe;
            display: block;
        }
        .cs-attachment-file {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            padding: 6px 8px;
            border-radius: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            text-decoration: none;
        }
        .cs-empty {
            padding: 14px;
            color: #64748b;
            text-align: center;
        }
        .cs-form-grid {
            display: grid;
            gap: 8px;
        }
        .cs-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 12px;
        }
        .cs-live-dot {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #16a34a;
            font-weight: 600;
        }
        .cs-live-dot::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            animation: csPulse 1.4s infinite;
        }
        @keyframes csPulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.9); }
        }
        .cs-composer {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px;
            align-items: end;
        }
        @media (min-width: 960px) {
            .cs-layout {
                grid-template-columns: 340px 1fr;
            }
        }
    </style>
</head>
<body class="<?php echo $isEmbed ? 'cs-embed-page' : ''; ?>">
    <style>
    <?php if ($isEmbed): ?>
    .cs-embed-page .dashboard-header-top,
    .cs-embed-page .bottom-navbar,
    .cs-embed-page .support-top-icon-link {
        display: none !important;
    }
    .cs-embed-page .main-content {
        padding-top: 0 !important;
        padding-bottom: 0 !important;
    }
    .cs-embed-page .container {
        padding: 8px;
    }
    .cs-embed-page .cs-layout {
        grid-template-columns: 1fr !important;
    }
    <?php endif; ?>
    </style>
    <?php if (!$isEmbed): ?>
    <div class="dashboard-header-top">
        <div class="dashboard-header-content">
            <div class="dashboard-header-text">
                <h1 class="dashboard-title">Customer Service</h1>
                <p class="dashboard-subtitle">Message admin support from your passenger account.</p>
            </div>
            <div class="dashboard-profile-image">
                <?php
                $fullName = htmlspecialchars($passengerInfo['first_name'] . ' ' . $passengerInfo['last_name']);
                $initials = strtoupper(substr($passengerInfo['first_name'], 0, 1) . substr($passengerInfo['last_name'], 0, 1));
                ?>
                <?php if (!empty($passengerInfo['profile_image']) && file_exists($imageBasePath . $passengerInfo['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($imageBasePath . $passengerInfo['profile_image']); ?>" alt="Profile" class="profile-avatar">
                <?php else: ?>
                    <div class="profile-avatar-placeholder"><?php echo $initials; ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="main-content">
        <div class="container">
            <?php if ($error): ?>
                <div class="cs-alert error"><?php echo htmlspecialchars((string)$error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="cs-alert success"><?php echo htmlspecialchars((string)$success); ?></div>
            <?php endif; ?>

            <div class="cs-layout">
                <div class="cs-card">
                    <div class="cs-ticket-card">
                        <div class="cs-ticket-head">
                            <h4><i class="fas fa-ticket-alt"></i> New Ticket Support</h4>
                            <span class="cs-pill">Passenger</span>
                        </div>
                        <div style="font-size:12px; color:#475569;">
                            Create a ticket for card issues, trip concerns, balance concerns, or account help.
                        </div>
                    </div>
                    <form method="POST" action="<?php echo htmlspecialchars($basePath); ?>index.php?page=customer_service" class="cs-form-grid" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="create_conversation">
                        <input type="text" class="cs-input" value="<?php echo htmlspecialchars((string)$identity['name']); ?>" disabled>
                        <input type="text" class="cs-input" value="<?php echo htmlspecialchars((string)$identity['contact']); ?>" disabled>
                        <input type="text" name="subject" class="cs-input" placeholder="Subject (e.g. Card issue)" required>
                        <select name="priority" class="cs-select">
                            <option value="medium">Priority: Medium</option>
                            <option value="high">Priority: High</option>
                            <option value="low">Priority: Low</option>
                        </select>
                        <textarea name="message" class="cs-textarea" rows="3" placeholder="Describe your concern..."></textarea>
                        <div class="cs-attach-row">
                            <label for="cs-ticket-attachment" class="cs-file-label" title="Attach file">
                                <i class="fas fa-paperclip"></i>
                            </label>
                            <input id="cs-ticket-attachment" type="file" name="attachment" class="cs-file-input" accept=".jpg,.jpeg,.png,.webp,.pdf,.txt">
                            <span id="cs-ticket-file-name" class="cs-file-name">Attach image or file</span>
                        </div>
                        <button type="submit" class="cs-send-btn"><i class="fas fa-plus"></i> Create Ticket</button>
                    </form>

                    <hr style="margin:14px 0; border:none; border-top:1px solid #e5e7eb;">

                    <h3 style="margin:0 0 8px 0;">My Conversations</h3>
                    <div class="cs-list">
                        <?php if (empty($conversations)): ?>
                            <div class="cs-empty">No support tickets yet.</div>
                        <?php else: ?>
                            <?php foreach ($conversations as $cv): ?>
                                <?php $isActive = $activeConversation && (int)$activeConversation['id'] === (int)$cv['id']; ?>
                                <a href="<?php echo htmlspecialchars($basePath . 'index.php?page=customer_service&conversation_id=' . (int)$cv['id']); ?>" class="cs-item <?php echo $isActive ? 'active' : ''; ?>">
                                    <div style="display:flex; justify-content:space-between; gap:8px;">
                                        <strong><?php echo htmlspecialchars((string)$cv['subject']); ?></strong>
                                        <span style="font-size:12px; text-transform:uppercase; color:#475569;"><?php echo htmlspecialchars((string)$cv['status']); ?></span>
                                    </div>
                                    <div style="font-size:12px; color:#64748b; margin-top:4px;">
                                        <?php echo (int)$cv['message_count']; ?> messages
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="cs-card">
                    <?php if (!$activeConversation): ?>
                        <div class="cs-empty">Select a conversation to view messages.</div>
                    <?php else: ?>
                        <div class="cs-topbar">
                            <div>
                                <h3 style="margin:0;"><?php echo htmlspecialchars((string)$activeConversation['subject']); ?></h3>
                                <div style="font-size:12px; color:#64748b; margin-top:4px;">
                                    Status: <strong><?php echo htmlspecialchars((string)$activeConversation['status']); ?></strong>
                                    · Priority: <strong><?php echo htmlspecialchars((string)$activeConversation['priority']); ?></strong>
                                </div>
                            </div>
                            <span class="cs-live-dot">Live</span>
                        </div>

                        <div class="cs-thread" id="csThread">
                            <?php if (empty($messages)): ?>
                                <div style="color:#64748b;">No messages yet.</div>
                            <?php else: ?>
                                <?php foreach ($messages as $msg): ?>
                                    <?php $type = (($msg['sender_type'] ?? 'admin') === 'customer') ? 'customer' : 'admin'; ?>
                                    <div class="cs-bubble-wrap <?php echo $type; ?>">
                                        <div class="cs-bubble">
                                            <div class="cs-meta">
                                                <?php echo htmlspecialchars((string)($msg['sender_name'] ?: ucfirst((string)$msg['sender_type']))); ?>
                                                · <?php echo htmlspecialchars((string)$msg['created_at']); ?>
                                            </div>
                                            <div style="white-space:pre-wrap;"><?php echo htmlspecialchars((string)$msg['message']); ?></div>
                                            <?php if (!empty($msg['attachments']) && is_array($msg['attachments'])): ?>
                                                <div class="cs-attachment-list">
                                                    <?php foreach ($msg['attachments'] as $att): ?>
                                                        <?php $isImage = strpos((string)($att['mime_type'] ?? ''), 'image/') === 0; ?>
                                                        <?php
                                                        $attPath = (string)($att['file_path'] ?? '');
                                                        $attUrl = (strpos($attPath, '/') === 0) ? $attPath : ($basePath . $attPath);
                                                        ?>
                                                        <?php if ($isImage): ?>
                                                            <a href="<?php echo htmlspecialchars($attUrl); ?>" target="_blank" rel="noopener">
                                                                <img
                                                                    src="<?php echo htmlspecialchars($attUrl); ?>"
                                                                    alt="<?php echo htmlspecialchars((string)$att['original_name']); ?>"
                                                                    class="cs-attachment-image"
                                                                >
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="<?php echo htmlspecialchars($attUrl); ?>" target="_blank" rel="noopener" class="cs-attachment-file">
                                                                <i class="fas fa-paperclip"></i>
                                                                <?php echo htmlspecialchars((string)$att['original_name']); ?>
                                                            </a>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <form method="POST" action="<?php echo htmlspecialchars($basePath); ?>index.php?page=customer_service&conversation_id=<?php echo (int)$activeConversation['id']; ?>" class="cs-form-grid" id="csSendForm" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="send_message">
                            <input type="hidden" name="conversation_id" value="<?php echo (int)$activeConversation['id']; ?>">
                            <div class="cs-composer">
                                <textarea name="message" class="cs-textarea" rows="2" placeholder="Write your reply..."></textarea>
                                <button type="submit" class="cs-send-btn"><i class="fas fa-paper-plane"></i> Send</button>
                            </div>
                            <div class="cs-attach-row">
                                <label for="cs-reply-attachment" class="cs-file-label" title="Attach file">
                                    <i class="fas fa-paperclip"></i>
                                </label>
                                <input id="cs-reply-attachment" type="file" name="attachment" class="cs-file-input" accept=".jpg,.jpeg,.png,.webp,.pdf,.txt">
                                <span id="cs-reply-file-name" class="cs-file-name">Attach image or file</span>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$isEmbed): ?>
        <?php
        $activePage = 'customer_service';
        include __DIR__ . '/components/bottom_navbar.php';
        ?>
        <?php include 'view/components/live_bus_tracker.php'; ?>
    <?php endif; ?>
    <?php if ($activeConversation): ?>
    <script>
    (function () {
        var conversationId = <?php echo (int)$activeConversation['id']; ?>;
        var basePath = <?php echo json_encode((string)$basePath, JSON_UNESCAPED_SLASHES); ?>;
        var pollUrl = basePath + 'controller/passenger/CustomerServiceRealtimeController.php?conversation_id=' + conversationId;
        var thread = document.getElementById('csThread');
        var sendForm = document.getElementById('csSendForm');
        var isSending = false;

        function escapeHtml(text) {
            return String(text || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function buildAttachmentUrl(path) {
            var p = String(path || '');
            if (!p) return '';
            if (p.charAt(0) === '/') return p;
            return String(basePath || '') + p;
        }

        function renderMessages(messages) {
            if (!Array.isArray(messages) || !thread) return;
            var shouldStickBottom = (thread.scrollHeight - thread.scrollTop - thread.clientHeight) < 40;
            thread.innerHTML = '';
            if (!messages.length) {
                thread.innerHTML = '<div style="color:#64748b;">No messages yet.</div>';
                return;
            }
            messages.forEach(function (msg) {
                var type = (msg.sender_type === 'customer') ? 'customer' : 'admin';
                var sender = msg.sender_name || (msg.sender_type ? (msg.sender_type.charAt(0).toUpperCase() + msg.sender_type.slice(1)) : 'Unknown');
                var wrap = document.createElement('div');
                wrap.className = 'cs-bubble-wrap ' + type;
                var attachmentsHtml = '';
                if (Array.isArray(msg.attachments) && msg.attachments.length) {
                    attachmentsHtml = '<div class="cs-attachment-list">' + msg.attachments.map(function(att){
                        var mime = String(att.mime_type || '');
                        var isImage = mime.indexOf('image/') === 0;
                        var href = buildAttachmentUrl(att.file_path || '');
                        if (isImage) {
                            return '<a href="' + escapeHtml(href) + '" target="_blank" rel="noopener"><img src="' + escapeHtml(href) + '" alt="' + escapeHtml(att.original_name || 'Attachment') + '" class="cs-attachment-image"></a>';
                        }
                        return '<a href="' + escapeHtml(href) + '" target="_blank" rel="noopener" class="cs-attachment-file"><i class="fas fa-paperclip"></i> ' + escapeHtml(att.original_name || 'Attachment') + '</a>';
                    }).join('') + '</div>';
                }
                wrap.innerHTML =
                    '<div class="cs-bubble">' +
                    '<div class="cs-meta">' + escapeHtml(sender) + ' · ' + escapeHtml(msg.created_at || '') + '</div>' +
                    '<div style="white-space:pre-wrap;">' + escapeHtml(msg.message || '') + '</div>' + attachmentsHtml +
                    '</div>';
                thread.appendChild(wrap);
            });
            if (shouldStickBottom) {
                thread.scrollTop = thread.scrollHeight;
            }
        }

        function poll() {
            if (isSending) return;
            fetch(pollUrl, { credentials: 'same-origin' })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data && data.success) {
                        renderMessages(data.messages || []);
                    }
                })
                .catch(function () { /* keep silent to avoid UI noise */ });
        }

        if (sendForm) {
            sendForm.addEventListener('submit', function () {
                isSending = true;
                setTimeout(function () {
                    isSending = false;
                    poll();
                }, 700);
            });
        }

        setInterval(poll, 2500);
        poll();

        function wireFileName(inputId, outputId) {
            var input = document.getElementById(inputId);
            var output = document.getElementById(outputId);
            if (!input || !output) return;
            input.addEventListener('change', function () {
                output.textContent = input.files && input.files[0] ? input.files[0].name : 'Attach image or file';
            });
        }
        wireFileName('cs-ticket-attachment', 'cs-ticket-file-name');
        wireFileName('cs-reply-attachment', 'cs-reply-file-name');
    })();
    </script>
    <?php endif; ?>
</body>
</html>
