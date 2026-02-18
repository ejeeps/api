<?php
// controller/passenger/get_live_positions.php
// Returns latest positions per driver_assign_id for recent trips

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Restrict to logged-in users (passenger/driver/admin as needed). For now, allow if logged in.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../config/connection.php';

// Configuration
$windowMinutes = 5; // consider trips in last N minutes as potentially live

try {
    // Latest trip per driver_assign_id in the recent window
    $sql = "
        SELECT t.driver_assign_id, t.route_id, t.latitude, t.longitude, t.timestamp
        FROM trips t
        INNER JOIN (
            SELECT driver_assign_id, MAX(timestamp) AS max_ts
            FROM trips
            WHERE timestamp >= (NOW() - INTERVAL :win MINUTE)
            GROUP BY driver_assign_id
        ) latest ON latest.driver_assign_id = t.driver_assign_id AND latest.max_ts = t.timestamp
        ORDER BY t.timestamp DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':win', $windowMinutes, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    foreach ($rows as $r) {
        // Basic validation on coords
        $lat = isset($r['latitude']) ? (float)$r['latitude'] : null;
        $lng = isset($r['longitude']) ? (float)$r['longitude'] : null;
        if ($lat === null || $lng === null) continue;
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) continue;

        $result[] = [
            'id' => (int)$r['driver_assign_id'],
            'route_id' => (int)$r['route_id'],
            'lat' => $lat,
            'lng' => $lng,
            'ts' => $r['timestamp'],
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Server error', 'details' => $e->getMessage()]);
}
