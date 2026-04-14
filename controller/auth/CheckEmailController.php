<?php
/**
 * JSON endpoint: whether an email is already registered (users table).
 * Used by registration forms for live validation.
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/connection.php';

$email = isset($_GET['email']) ? trim((string) $_GET['email']) : '';
if ($email === '' && isset($_POST['email'])) {
    $email = trim((string) $_POST['email']);
}

if ($email === '') {
    echo json_encode([
        'ok'      => false,
        'taken'   => null,
        'message' => 'Email is required.',
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'ok'      => false,
        'taken'   => null,
        'message' => 'Invalid email format.',
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $taken = $stmt->rowCount() > 0;

    echo json_encode([
        'ok'      => true,
        'taken'   => $taken,
        'message' => $taken
            ? 'This email is already registered. Use a different email or log in.'
            : '',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok'      => false,
        'taken'   => null,
        'message' => 'Could not verify email. Please try again.',
    ]);
}
