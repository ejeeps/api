-- Create drivers table for driver-specific information
-- This table links to users table via user_id
CREATE TABLE IF NOT EXISTS drivers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    area_id INT UNSIGNED DEFAULT NULL,
    -- Address Information
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255) DEFAULT NULL,
    city VARCHAR(100) NOT NULL,
    province VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    
    
    -- Driver's License Information
    license_number VARCHAR(50) NOT NULL UNIQUE,
    license_expiry_date DATE NOT NULL,
    license_type VARCHAR(50) NOT NULL,
    license_image VARCHAR(255) DEFAULT NULL,
    license_back_image VARCHAR(255) DEFAULT NULL,
    
    -- Driver Status and Verification
    driver_status ENUM('pending', 'approved', 'rejected', 'suspended') DEFAULT 'pending',
    background_check_status ENUM('pending', 'passed', 'failed') DEFAULT 'pending',
    background_check_document VARCHAR(255) DEFAULT NULL,
    
    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL DEFAULT NULL,
    
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_license (license_number),
    INDEX idx_driver_status (driver_status),
    INDEX idx_area_id (area_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

