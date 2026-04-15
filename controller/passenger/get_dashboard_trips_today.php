<?php
/**
 * Routes that have trip records today (network-wide), for passenger dashboard.
 *
 * @return array<int, array<string, mixed>>
 */
function getDashboardTripsToday(PDO $pdo, ?string $cardIdNumber = null): array
{
    try {
        // If an IN remains pending too long, we stop considering it "ongoing".
        // And most importantly: we treat a trip as "open" only when it has an IN row
        // and there is NO matching OUT row for the same trip_id + card_id.
        $pendingTimeoutMinutes = 15;

        $whereCard = '';
        if (!empty($cardIdNumber)) {
            $whereCard = " AND t.card_id = :cardIdNumber ";
        }

        // Map endpoints: prefer routes.start_* / end_* (migration 024); if unset, use earliest
        // and latest GPS from trips today for this route (trips.latitude/longitude from 014).
        $sql = "
            SELECT
                r.id,
                r.from_location,
                r.to_location,
                r.location,
                r.start_lat,
                r.start_lng,
                r.end_lat,
                r.end_lng,
                COALESCE(
                    r.start_lat,
                    (SELECT t3.latitude FROM trips t3
                     WHERE t3.route_id = r.id AND DATE(t3.timestamp) = CURDATE()
                     ORDER BY t3.timestamp ASC, t3.id ASC LIMIT 1)
                ) AS map_start_lat,
                COALESCE(
                    r.start_lng,
                    (SELECT t3.longitude FROM trips t3
                     WHERE t3.route_id = r.id AND DATE(t3.timestamp) = CURDATE()
                     ORDER BY t3.timestamp ASC, t3.id ASC LIMIT 1)
                ) AS map_start_lng,
                COALESCE(
                    r.end_lat,
                    (SELECT t3.latitude FROM trips t3
                     WHERE t3.route_id = r.id AND DATE(t3.timestamp) = CURDATE()
                     ORDER BY t3.timestamp DESC, t3.id DESC LIMIT 1)
                ) AS map_end_lat,
                COALESCE(
                    r.end_lng,
                    (SELECT t3.longitude FROM trips t3
                     WHERE t3.route_id = r.id AND DATE(t3.timestamp) = CURDATE()
                     ORDER BY t3.timestamp DESC, t3.id DESC LIMIT 1)
                ) AS map_end_lng,
                COUNT(DISTINCT t.trip_id) AS trip_sessions,
                COUNT(DISTINCT IF(
                    t.trip_status = 'pending'
                    AND t.card_id IS NOT NULL
                    AND t.tap_level = 'IN'
                    AND TIMESTAMPDIFF(MINUTE, t.timestamp, NOW()) <= :timeoutMinutes
                    AND NOT EXISTS (
                        SELECT 1
                        FROM trips t2
                        WHERE t2.trip_id = t.trip_id
                          AND t2.card_id = t.card_id
                          AND t2.tap_level = 'OUT'
                          AND DATE(t2.timestamp) = CURDATE()
                    ),
                    t.trip_id,
                    NULL
                )) AS pending_rows,
                MAX(t.timestamp) AS last_activity
            FROM trips t
            INNER JOIN routes r ON r.id = t.route_id
            WHERE DATE(t.timestamp) = CURDATE()
            {$whereCard}
            GROUP BY r.id, r.from_location, r.to_location, r.location, r.start_lat, r.start_lng, r.end_lat, r.end_lng
            ORDER BY pending_rows DESC, last_activity DESC
        ";
        $stmt = $pdo->prepare($sql);
        $params = [
            ':timeoutMinutes' => $pendingTimeoutMinutes
        ];
        if (!empty($cardIdNumber)) {
            $params[':cardIdNumber'] = $cardIdNumber;
        }
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('getDashboardTripsToday: ' . $e->getMessage());
        return [];
    }
}
