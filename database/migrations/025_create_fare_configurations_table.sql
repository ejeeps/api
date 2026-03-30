-- Create fare_configurations table for fare calculation settings
-- Stores base fare, base km, and rate per km for tiered pricing
CREATE TABLE IF NOT EXISTS fare_configurations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Fare Configuration
    base_km DECIMAL(10, 2) NOT NULL DEFAULT 5.00 COMMENT 'Base kilometers included in base fare',
    base_fare DECIMAL(10, 2) NOT NULL DEFAULT 15.00 COMMENT 'Base fare amount for base_km distance',
    rate_per_km DECIMAL(10, 2) NOT NULL DEFAULT 2.50 COMMENT 'Additional rate per km beyond base_km',
    
    -- Configuration Scope
    area_id INT UNSIGNED DEFAULT NULL COMMENT 'Optional: Apply to specific area only (NULL = global)',
    route_id INT UNSIGNED DEFAULT NULL COMMENT 'Optional: Apply to specific route only (NULL = global/area)',
    
    -- Configuration Status
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Whether this configuration is active',
    priority INT UNSIGNED DEFAULT 0 COMMENT 'Priority for configuration resolution (higher = more specific)',
    
    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE CASCADE,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE,
    INDEX idx_area_id (area_id),
    INDEX idx_route_id (route_id),
    INDEX idx_is_active (is_active),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default fare configuration
INSERT INTO fare_configurations (base_km, base_fare, rate_per_km, is_active, priority) 
VALUES (5.00, 15.00, 2.50, TRUE, 0);
