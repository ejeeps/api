-- Create admins table for admin-specific information
-- This table links to users table via user_id (where user_level = 'admin')
CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    
    -- Admin Information
    employee_id VARCHAR(50) DEFAULT NULL UNIQUE COMMENT 'Employee or admin ID number',
    department VARCHAR(100) DEFAULT NULL COMMENT 'Department or team',
    position VARCHAR(100) DEFAULT NULL COMMENT 'Job title or position',
    
    -- Admin Access & Permissions
    access_level ENUM('viewer', 'admin', 'super_admin') DEFAULT 'admin' COMMENT 'Access level for admin portal',
    can_access_api BOOLEAN DEFAULT TRUE COMMENT 'Can access API documentation',
    can_access_gateway BOOLEAN DEFAULT TRUE COMMENT 'Can access gateway services',
    can_manage_users BOOLEAN DEFAULT FALSE COMMENT 'Can manage users',
    can_manage_database BOOLEAN DEFAULT FALSE COMMENT 'Can manage database',
    
    -- Admin Status
    admin_status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    
    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_access TIMESTAMP NULL DEFAULT NULL COMMENT 'Last time admin accessed the portal',
    
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_employee_id (employee_id),
    INDEX idx_access_level (access_level),
    INDEX idx_admin_status (admin_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

