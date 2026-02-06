-- Create cards table for card information
-- Stores card details including card ID number and type
CREATE TABLE IF NOT EXISTS cards (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Card Identification
    card_id_number VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique card identification number',
    card_type ENUM('driver','student', 'senior', 'regular', 'employee', 'special','admin') DEFAULT 'regular' COMMENT 'Type of card',
    
    -- Card Status
    status ENUM('active', 'inactive', 'blocked', 'expired') DEFAULT 'active',
    
    -- Card Details
    balance DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Current card balance',
    issued_date DATE DEFAULT NULL COMMENT 'Date when card was issued',
    expiry_date DATE DEFAULT NULL COMMENT 'Card expiration date',
    
    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    INDEX idx_card_id_number (card_id_number),
    INDEX idx_card_type (card_type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

