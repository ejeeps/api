<?php
/**
 * Get Driver Information Controller
 * Fetches driver information with device assignment for the logged-in driver
 * 
 * @return array|null Driver information array or null if not found
 */
function getDriverInfo($pdo, $userId) {
    try {
        // Get driver information with device assignment
        $driverStmt = $pdo->prepare("
            SELECT 
                u.*, 
                d.*,
                dev.device_name,
                dev.device_serial_number,
                da.assignment_status AS device_assignment_status
            FROM users u 
            LEFT JOIN drivers d ON u.id = d.user_id 
            LEFT JOIN device_assignments da ON d.id = da.driver_id AND da.assignment_status = 'active'
            LEFT JOIN devices dev ON da.device_id = dev.id
            WHERE u.id = ?
        ");
        $driverStmt->execute([$userId]);
        return $driverStmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching driver info: " . $e->getMessage());
        return null;
    }
}
?>

