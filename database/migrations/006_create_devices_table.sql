-- Create devices table for device inventory
-- This table stores all available devices in the system
CREATE TABLE IF NOT EXISTS devices (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Device Information
    device_name VARCHAR(100) NOT NULL,
    device_serial_number VARCHAR(100) NOT NULL UNIQUE,
   
    
    -- Device Status
    device_status ENUM('available', 'assigned', 'maintenance', 'retired', 'lost') DEFAULT 'available',
    
    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    INDEX idx_serial_number (device_serial_number),
    INDEX idx_device_status (device_status),
    INDEX idx_device_name (device_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

