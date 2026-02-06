-- Create device_assignments table for driver-device relationships
-- This table tracks which devices are assigned to which drivers
-- Supports one driver having multiple devices and device assignment history
CREATE TABLE IF NOT EXISTS device_assignments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    driver_id INT UNSIGNED NOT NULL,
    device_id INT UNSIGNED NOT NULL,
    
    -- Assignment Details
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    unassigned_at TIMESTAMP NULL DEFAULT NULL,
    assigned_by INT UNSIGNED DEFAULT NULL COMMENT 'User ID who assigned the device',
    
    -- Assignment Status
    assignment_status ENUM('active', 'returned', 'lost', 'broken') DEFAULT 'active',
    
    -- Notes
    assignment_notes TEXT DEFAULT NULL,
    return_notes TEXT DEFAULT NULL,
    
    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
    INDEX idx_driver_id (driver_id),
    INDEX idx_device_id (device_id),
    INDEX idx_assignment_status (assignment_status),
    INDEX idx_active_assignments (driver_id, assignment_status),
    INDEX idx_assigned_at (assigned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

