<?php
/**
 * Passenger-only AI assistant (Groq). Uses routes + this passenger's trip history as context.
 */
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../config/groq.php';
require_once __DIR__ . '/get_passengers_info.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_level'] ?? '') !== 'passenger') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$apiKey = ejeep_groq_api_key();
if ($apiKey === '') {
    http_response_code(503);
    echo json_encode(['error' => 'Assistant is not configured. Add GROQ_API_KEY (see config/groq.php).']);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$message = isset($input['message']) ? trim((string) $input['message']) : '';
if ($message === '' || mb_strlen($message) > 4000) {
    http_response_code(400);
    echo json_encode(['error' => 'Message is required (max 4000 characters).']);
    exit;
}

$history = $input['history'] ?? [];
if (!is_array($history)) {
    $history = [];
}
$history = array_slice($history, -20);

$passengerInfo = getPassengerInfo($pdo, $_SESSION['user_id']);
if (!$passengerInfo) {
    http_response_code(403);
    echo json_encode(['error' => 'Passenger profile not found.']);
    exit;
}

$first = trim((string) ($passengerInfo['first_name'] ?? ''));
$last = trim((string) ($passengerInfo['last_name'] ?? ''));
$cardNumber = $passengerInfo['card_number'] ?? null;
$balance = isset($passengerInfo['card_balance']) ? (float) $passengerInfo['card_balance'] : null;
$cardType = strtolower(trim((string)($passengerInfo['card_type'] ?? 'regular')));

// Active routes (system-wide, for "what routes exist")
$routes = [];
try {
    $rStmt = $pdo->query("
        SELECT id, from_location, to_location, location, distance_km, estimated_duration_minutes, fare_amount, status
        FROM routes
        WHERE status = 'active'
        ORDER BY id ASC
    ");
    $routes = $rStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('PassengerAiAssistant routes: ' . $e->getMessage());
}

// This passenger's recent trips (matched by card_id on trips table)
$recentTrips = [];
if (!empty($cardNumber)) {
    try {
        $tStmt = $pdo->prepare("
            SELECT
                t.trip_id,
                t.tap_level,
                t.fare_amount,
                t.trip_status,
                t.timestamp,
                r.id AS route_id,
                r.from_location,
                r.to_location,
                r.fare_amount AS route_default_fare
            FROM trips t
            INNER JOIN routes r ON t.route_id = r.id
            WHERE t.card_id = ?
            ORDER BY t.timestamp DESC
            LIMIT 50
        ");
        $tStmt->execute([$cardNumber]);
        $recentTrips = $tStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('PassengerAiAssistant trips: ' . $e->getMessage());
    }
}

$taripaRates = [];
// Taripa (LTFRB) fare matrix — used to explain/estimate distance-based fares.
try {
    $tStmt = $pdo->query("
        SELECT
            distance_km,
            distance_label,
            regular_fare,
            discounted_fare,
            vehicle_type,
            effective_date,
            expiry_date
        FROM taripa_rates
        WHERE is_active = TRUE
          AND effective_date <= CURDATE()
          AND (expiry_date IS NULL OR expiry_date >= CURDATE())
        ORDER BY vehicle_type ASC, distance_km ASC
    ");
    $taripaRates = $tStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('PassengerAiAssistant taripa_rates: ' . $e->getMessage());
}

$contextPayload = [
    'passenger' => [
        'display_name' => trim($first . ' ' . $last),
        'has_active_card' => !empty($cardNumber),
        'card_balance_php' => $balance,
        'card_type' => $cardType,
        // Taripa uses "discounted_fare" for Student/Senior/PWD type cards.
        'has_discount' => in_array($cardType, ['student', 'senior', 'pwd'], true),
    ],
    'routes_active' => $routes,
    'passenger_recent_trips' => $recentTrips,
    'taripa_rates_active' => $taripaRates,
];

$contextJson = json_encode($contextPayload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if ($contextJson === false) {
    $contextJson = '{}';
}

$systemPrompt = <<<PROMPT
You are the E-JEEP assistant for PASSENGERS ONLY.

Rules:
- Answer only using the JSON context below (routes, this passenger's trips, passenger balance/card flags, and Taripa fare matrix). Do not invent routes, trips, or fares.
- Help explain which route goes where, what trips the passenger took (tap in/out, time, fare), and how to read this data.
- If the user asks about drivers, admin, other passengers, politics, coding, or anything outside E-JEEP passenger trips/routes/balance, politely say you can only help with their E-JEEP routes and trip history from this app.
- Be concise and friendly. Use Philippine peso (₱) when mentioning money.
- If there are no trips in context, say they have no recorded trips yet for their card on file.

CONTEXT_JSON:
{$contextJson}
PROMPT;

$messages = [['role' => 'system', 'content' => $systemPrompt]];

foreach ($history as $h) {
    if (!is_array($h)) {
        continue;
    }
    $role = $h['role'] ?? '';
    $content = isset($h['content']) ? trim((string) $h['content']) : '';
    if ($content === '' || mb_strlen($content) > 4000) {
        continue;
    }
    if ($role === 'user') {
        $messages[] = ['role' => 'user', 'content' => $content];
    } elseif ($role === 'assistant') {
        $messages[] = ['role' => 'assistant', 'content' => $content];
    }
}

$messages[] = ['role' => 'user', 'content' => $message];

$payload = json_encode([
    'model' => 'llama-3.3-70b-versatile',
    'messages' => $messages,
    'temperature' => 0.35,
    'max_tokens' => 1200,
]);

$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ],
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_TIMEOUT => 60,
]);

$responseBody = curl_exec($ch);
$curlErr = curl_error($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($responseBody === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Assistant request failed: ' . ($curlErr ?: 'network error')]);
    exit;
}

$decoded = json_decode($responseBody, true);
if ($httpCode >= 400 || !is_array($decoded)) {
    $msg = is_array($decoded) && isset($decoded['error']['message'])
        ? $decoded['error']['message']
        : 'Assistant error';
    http_response_code(502);
    echo json_encode(['error' => $msg]);
    exit;
}

$reply = $decoded['choices'][0]['message']['content'] ?? '';
if (!is_string($reply) || $reply === '') {
    http_response_code(502);
    echo json_encode(['error' => 'Empty response from assistant']);
    exit;
}

echo json_encode(['reply' => $reply], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
