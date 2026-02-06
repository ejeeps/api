-- Create driver_routes table for driver-route assignments
-- This junction table links drivers to their assigned routes
-- Allows many-to-many relationship: drivers can have multiple routes, routes can have multiple drivers
CREATE TABLE IF NOT EXISTS driver_routes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    driver_id INT UNSIGNED NOT NULL COMMENT 'Foreign key to drivers table',
    route_id INT UNSIGNED NOT NULL COMMENT 'Foreign key to routes table',
    
    -- Assignment Details
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When the route was assigned to the driver',
    unassigned_at TIMESTAMP NULL DEFAULT NULL COMMENT 'When the route was unassigned (if applicable)',
    
    -- Assignment Status
    assignment_status ENUM('active', 'inactive', 'suspended', 'completed') DEFAULT 'active' COMMENT 'Status of the driver-route assignment',
    
    -- Additional Assignment Information (optional)
    notes TEXT DEFAULT NULL COMMENT 'Additional notes about this assignment',
    
    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE,
    INDEX idx_driver_id (driver_id),
    INDEX idx_route_id (route_id),
    INDEX idx_assignment_status (assignment_status),
    INDEX idx_active_assignments (driver_id, assignment_status),
    INDEX idx_route_assignments (route_id, assignment_status),
    -- Prevent duplicate active assignments (a driver can only have one active assignment per route)
    UNIQUE KEY unique_active_assignment (driver_id, route_id, assignment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
