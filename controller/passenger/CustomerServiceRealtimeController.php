<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/CustomerServiceController.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || ($_SESSION['user_level'] ?? '') !== 'passenger') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    ejeepCustomerServiceEnsureTables($pdo);
    $identity = ejeepCustomerServicePassengerIdentity($pdo, (int)$_SESSION['user_id']);
    if (!$identity) {
        http_response_code(403);
        echo json_encode(['error' => 'Passenger profile not found']);
        exit;
    }

    $conversationId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
    $activeConversation = $conversationId > 0 ? ejeepCustomerServiceGetConversation($pdo, $conversationId, $identity) : null;
    $messages = $activeConversation ? ejeepCustomerServiceListMessages($pdo, (int)$activeConversation['id']) : [];
    $conversations = ejeepCustomerServiceListConversations($pdo, $identity);

    echo json_encode([
        'success' => true,
        'active_conversation' => $activeConversation,
        'messages' => $messages,
        'conversations' => $conversations,
        'server_time' => date('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
