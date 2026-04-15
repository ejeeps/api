<?php
/**
 * Get Passenger Information Controller
 * Fetches passenger information with card balance for the logged-in passenger
 * 
 * @return array|null Passenger information array or null if not found
 */
function getPassengerInfo($pdo, $userId) {
    try {
        // Try the full query with card_assign_passengers table first
        // If it fails (table doesn't exist), fall back to simpler query
        $tableExists = true;
        try {
            $testQuery = $pdo->query("SELECT 1 FROM card_assign_passengers LIMIT 1");
        } catch (PDOException $e) {
            // Table doesn't exist or no permission, use fallback query
            $tableExists = false;
        }
        
        if ($tableExists) {
            // Get passenger information with card details through card_assign_passengers
            $passengerStmt = $pdo->prepare("
                SELECT 
                    u.*, 
                    p.*,
                    p.id AS passenger_table_id,
                    c.card_id_number AS card_number,
                    c.balance AS card_balance,
                    c.status AS card_status,
                    c.card_type,
                    c.issued_date,
                    c.expiry_date,
                    ap.assigned_at,
                    ap.assignment_status,
                    o.name AS organization_name,
                    o.code AS organization_code
                FROM users u 
                LEFT JOIN passengers p ON u.id = p.user_id 
                LEFT JOIN card_assign_passengers ap ON p.id = ap.passenger_id AND ap.assignment_status = 'active'
                LEFT JOIN cards c ON ap.card_id = c.id
                LEFT JOIN organizations o ON COALESCE(ap.organization_id, c.organization_id) = o.id
                WHERE u.id = ?
            ");
        } else {
            // Fallback query without card_assign_passengers table (for backward compatibility)
            $passengerStmt = $pdo->prepare("
                SELECT 
                    u.*, 
                    p.*,
                    p.id AS passenger_table_id,
                    NULL AS card_number,
                    NULL AS card_balance,
                    NULL AS card_status,
                    NULL AS card_type,
                    NULL AS issued_date,
                    NULL AS expiry_date,
                    NULL AS assigned_at,
                    NULL AS assignment_status
                FROM users u 
                LEFT JOIN passengers p ON u.id = p.user_id 
                WHERE u.id = ?
            ");
        }
        
        $passengerStmt->execute([$userId]);
        $result = $passengerStmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if user exists
        if (!$result || empty($result['id'])) {
            error_log("User not found: user_id = " . $userId);
            return null;
        }
        
        // Check if passenger record exists (passenger_table_id will be NULL if no passenger record)
        if (empty($result['passenger_table_id']) || is_null($result['passenger_table_id'])) {
            error_log("Passenger record not found for user_id = " . $userId);
            return null;
        }

        // Persist passenger trip state with 15-minute stale timeout.
        // If the latest trip row for this card is still a pending IN within 15 minutes, mark ongoing.
        // Otherwise, mark ended.
        $tripTimeoutMinutes = 15;
        if (!empty($result['card_number'])) {
            try {
                $latestTripStmt = $pdo->prepare("
                    SELECT trip_id, tap_level, trip_status, timestamp
                    FROM trips
                    WHERE card_id = ?
                    ORDER BY timestamp DESC, id DESC
                    LIMIT 1
                ");
                $latestTripStmt->execute([(string)$result['card_number']]);
                $latestTrip = $latestTripStmt->fetch(PDO::FETCH_ASSOC);

                $tripState = 'ended';
                $lastSeenAt = null;
                if ($latestTrip) {
                    $lastSeenAt = $latestTrip['timestamp'] ?? null;
                    $isPendingIn = (($latestTrip['tap_level'] ?? null) === 'IN') && (($latestTrip['trip_status'] ?? null) === 'pending');
                    $isFresh = false;

                    if (!empty($latestTrip['timestamp'])) {
                        $ageStmt = $pdo->prepare("SELECT TIMESTAMPDIFF(MINUTE, ?, NOW())");
                        $ageStmt->execute([(string)$latestTrip['timestamp']]);
                        $ageMinutes = (int)$ageStmt->fetchColumn();
                        $isFresh = $ageMinutes <= $tripTimeoutMinutes;
                    }

                    if ($isPendingIn && $isFresh) {
                        $tripState = 'ongoing';
                    }
                }

                $updateTripStateStmt = $pdo->prepare("
                    UPDATE passengers
                    SET trip_activity_status = ?, trip_last_seen_at = ?, trip_status_updated_at = NOW()
                    WHERE id = ?
                ");
                $updateTripStateStmt->execute([$tripState, $lastSeenAt, (int)$result['passenger_table_id']]);

                $result['trip_activity_status'] = $tripState;
                $result['trip_last_seen_at'] = $lastSeenAt;
            } catch (PDOException $e) {
                // Keep backward compatibility if migration isn't applied yet.
                error_log("Trip state update skipped: " . $e->getMessage());
            }
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error fetching passenger info: " . $e->getMessage());
        error_log("SQL Error Code: " . $e->getCode());
        return null;
    }
}
?>

