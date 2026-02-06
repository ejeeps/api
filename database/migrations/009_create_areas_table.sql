-- Create areas table for route area/region information
-- This table stores different areas or regions where routes operate
CREATE TABLE IF NOT EXISTS areas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Area Information
    name VARCHAR(100) NOT NULL UNIQUE COMMENT 'Name of the area or region',
    description TEXT DEFAULT NULL COMMENT 'Description of the area',
    code VARCHAR(20) DEFAULT NULL COMMENT 'Short code for the area (optional)',
    
    -- Area Status
    status ENUM('active', 'inactive') DEFAULT 'active',
    
    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    INDEX idx_name (name),
    INDEX idx_code (code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key constraint for area_id in drivers table
-- This must run after both drivers and areas tables are created
ALTER TABLE drivers 
ADD CONSTRAINT fk_drivers_area_id 
FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE SET NULL;

