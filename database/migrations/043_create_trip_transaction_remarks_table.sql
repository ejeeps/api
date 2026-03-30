-- Create trip_transaction_remarks table for payment source tracking
-- Stores whether the fare was paid by organization or passenger, with additional remarks
CREATE TABLE IF NOT EXISTS trip_transaction_remarks (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Transaction Reference
    transaction_id INT UNSIGNED NOT NULL COMMENT 'Reference to trip_transactions.id',
    
    -- Payment Source Information
    payment_source ENUM('organization', 'passenger') NOT NULL DEFAULT 'passenger' COMMENT 'Who paid for the trip',
    organization_id INT UNSIGNED DEFAULT NULL COMMENT 'Organization ID if paid by organization (references organizations.id)',
    
    -- Additional Information
    remarks TEXT DEFAULT NULL COMMENT 'Additional notes or remarks about the transaction',
    payment_method ENUM('card_balance', 'cash', 'organization_budget', 'other') DEFAULT 'card_balance' COMMENT 'Method of payment',
    
    -- Processing Information
    processed_by INT UNSIGNED DEFAULT NULL COMMENT 'User/admin who processed/verified this transaction (references admins.id)',
    processed_at TIMESTAMP NULL DEFAULT NULL COMMENT 'When the remark was added/updated',
    
    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    FOREIGN KEY (transaction_id) REFERENCES trip_transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL,
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_payment_source (payment_source),
    INDEX idx_organization_id (organization_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Transaction remarks and payment source tracking';

-- Add comment explaining the table purpose
ALTER TABLE trip_transaction_remarks 
    COMMENT = 'Tracks payment source (Organization vs Passenger) and additional remarks for each trip transaction';
