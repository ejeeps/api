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
                    ap.assignment_status
                FROM users u 
                LEFT JOIN passengers p ON u.id = p.user_id 
                LEFT JOIN card_assign_passengers ap ON p.id = ap.passenger_id AND ap.assignment_status = 'active'
                LEFT JOIN cards c ON ap.card_id = c.id
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
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error fetching passenger info: " . $e->getMessage());
        error_log("SQL Error Code: " . $e->getCode());
        return null;
    }
}
?>

