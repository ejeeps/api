-- Create card_assign_admins table for admin-card relationships
-- This junction table links admins to their assigned cards
CREATE TABLE IF NOT EXISTS card_assign_admins (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_id INT UNSIGNED NOT NULL COMMENT 'Foreign key to admins table',
    card_id INT UNSIGNED NOT NULL COMMENT 'Foreign key to cards table',
    
    -- Assignment Details
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When the card was assigned to the admin',
    unassigned_at TIMESTAMP NULL DEFAULT NULL COMMENT 'When the card was unassigned (if applicable)',
    
    -- Assignment Status
    assignment_status ENUM('active', 'inactive', 'returned', 'lost') DEFAULT 'active',
    
    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE,
    INDEX idx_admin_id (admin_id),
    INDEX idx_card_id (card_id),
    INDEX idx_assignment_status (assignment_status),
    INDEX idx_active_assignments (admin_id, assignment_status),
    UNIQUE KEY unique_active_assignment (admin_id, card_id, assignment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
