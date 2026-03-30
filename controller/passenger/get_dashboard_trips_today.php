<?php
/**
 * Routes that have trip records today (network-wide), for passenger dashboard.
 *
 * @return array<int, array<string, mixed>>
 */
function getDashboardTripsToday(PDO $pdo): array
{
    try {
        $sql = "
            SELECT
                r.id,
                r.from_location,
                r.to_location,
                r.location,
                COUNT(DISTINCT t.trip_id) AS trip_sessions,
                SUM(CASE WHEN t.trip_status = 'pending' THEN 1 ELSE 0 END) AS pending_rows,
                MAX(t.timestamp) AS last_activity
            FROM trips t
            INNER JOIN routes r ON r.id = t.route_id
            WHERE DATE(t.timestamp) = CURDATE()
            GROUP BY r.id, r.from_location, r.to_location, r.location
            ORDER BY pending_rows DESC, last_activity DESC
        ";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('getDashboardTripsToday: ' . $e->getMessage());
        return [];
    }
}
