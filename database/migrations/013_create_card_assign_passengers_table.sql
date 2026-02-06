-- Create card_assign_passengers table for passenger-card relationships
-- This junction table links passengers to their assigned cards
CREATE TABLE IF NOT EXISTS card_assign_passengers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    passenger_id INT UNSIGNED NOT NULL COMMENT 'Foreign key to passengers table',
    card_id INT UNSIGNED NOT NULL COMMENT 'Foreign key to cards table',
    
    -- Assignment Details
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When the card was assigned to the passenger',
    unassigned_at TIMESTAMP NULL DEFAULT NULL COMMENT 'When the card was unassigned (if applicable)',
    
    -- Assignment Status
    assignment_status ENUM('active', 'inactive', 'returned', 'lost') DEFAULT 'active',
    
    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    FOREIGN KEY (passenger_id) REFERENCES passengers(id) ON DELETE CASCADE,
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE,
    INDEX idx_passenger_id (passenger_id),
    INDEX idx_card_id (card_id),
    INDEX idx_assignment_status (assignment_status),
    INDEX idx_active_assignments (passenger_id, assignment_status),
    UNIQUE KEY unique_active_assignment (passenger_id, card_id, assignment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

