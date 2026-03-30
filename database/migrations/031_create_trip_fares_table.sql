-- Create simplified trip_fares table to store computed fare results
-- Calculation logic will be handled in PHP code
CREATE TABLE IF NOT EXISTS trip_fares (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Trip References (links tap IN and OUT records)
    trip_id VARCHAR(50) NOT NULL COMMENT 'Trip ID that links IN and OUT records',
    card_id VARCHAR(50) NOT NULL COMMENT 'Card ID used for the trip',
    
    -- Trip Record References
    tap_in_trip_id INT UNSIGNED NOT NULL COMMENT 'ID of the tap IN record from trips table',
    tap_out_trip_id INT UNSIGNED NOT NULL COMMENT 'ID of the tap OUT record from trips table',
    
    -- Route Information
    route_id INT UNSIGNED DEFAULT NULL COMMENT 'Route ID if available',
    
    -- Location Data (from trips table)
    tap_in_latitude DECIMAL(10, 8) NOT NULL COMMENT 'Tap IN latitude',
    tap_in_longitude DECIMAL(11, 8) NOT NULL COMMENT 'Tap IN longitude',
    tap_out_latitude DECIMAL(10, 8) NOT NULL COMMENT 'Tap OUT latitude',
    tap_out_longitude DECIMAL(11, 8) NOT NULL COMMENT 'Tap OUT longitude',
    
    -- Distance Calculations
    gps_distance_km DECIMAL(10, 2) DEFAULT NULL COMMENT 'GPS distance calculated via haversine formula',
    route_distance_km DECIMAL(10, 2) DEFAULT NULL COMMENT 'Distance from routes table if available',
    final_distance_km DECIMAL(10, 2) NOT NULL COMMENT 'Final distance used for fare calculation',
    
    -- Fare Calculations
    base_fare DECIMAL(10, 2) NOT NULL COMMENT 'Base fare amount used',
    rate_per_km DECIMAL(10, 2) NOT NULL COMMENT 'Rate per km used',
    base_km DECIMAL(10, 2) NOT NULL COMMENT 'Base km threshold used',
    final_fare_amount DECIMAL(10, 2) NOT NULL COMMENT 'Final calculated fare amount',
    
    -- Trip Info
    duration_minutes INT UNSIGNED DEFAULT NULL COMMENT 'Trip duration in minutes',
    
    -- Calculation Source
    calculation_method ENUM('route_table', 'gps_haversine') DEFAULT 'gps_haversine' COMMENT 'Which distance method was used',
    
    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    FOREIGN KEY (tap_in_trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (tap_out_trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE SET NULL,
    INDEX idx_trip_id (trip_id),
    INDEX idx_card_id (card_id),
    INDEX idx_route_id (route_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
