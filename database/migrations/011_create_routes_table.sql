-- Create routes table for route information
-- Stores the starting point, destination, and location details for routes
CREATE TABLE IF NOT EXISTS routes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Route Information
    area_id INT UNSIGNED DEFAULT NULL COMMENT 'Foreign key to areas table - Area or region where the route operates',
    from_location VARCHAR(255) NOT NULL COMMENT 'Starting point of the route',
    to_location VARCHAR(255) NOT NULL COMMENT 'Destination point of the route',
    location VARCHAR(255) DEFAULT NULL COMMENT 'Additional location information',
    
    -- Route Details
    distance_km DECIMAL(10, 2) DEFAULT NULL COMMENT 'Distance in kilometers',
    estimated_duration_minutes INT UNSIGNED DEFAULT NULL COMMENT 'Estimated travel time in minutes',
    fare_amount DECIMAL(10, 2) DEFAULT NULL COMMENT 'Default fare amount for this route',
    
    -- Route Status
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    
    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE SET NULL,
    INDEX idx_area_id (area_id),
    INDEX idx_from_location (from_location),
    INDEX idx_to_location (to_location),
    INDEX idx_status (status),
    INDEX idx_route_pair (from_location, to_location)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

