-- Create organizations table
-- Stores organization information that sponsor/issue cards
-- Organization names are flexible - can be any organization name
CREATE TABLE IF NOT EXISTS organizations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Organization Information
    name VARCHAR(255) NOT NULL COMMENT 'Organization name (flexible - any organization name)',
    code VARCHAR(50) DEFAULT NULL UNIQUE COMMENT 'Organization code/identifier',
    type ENUM('lgu', 'school', 'company', 'government', 'ngo', 'other') DEFAULT 'other' COMMENT 'Type of organization',
    
    -- Contact Information
    email VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    
    -- Organization Details
    description TEXT DEFAULT NULL COMMENT 'Organization description',
    contact_person VARCHAR(255) DEFAULT NULL COMMENT 'Primary contact person name',
    contact_person_phone VARCHAR(20) DEFAULT NULL,
    
    -- Status
    status ENUM('active', 'inactive') DEFAULT 'active',
    
    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    INDEX idx_name (name),
    INDEX idx_code (code),
    INDEX idx_type (type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
