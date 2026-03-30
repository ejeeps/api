-- Add monthly budget field to organizations table
ALTER TABLE organizations
ADD COLUMN monthly_budget DECIMAL(15, 2) DEFAULT 0.00 COMMENT 'Monthly budget amount for card distributions' AFTER balance;

-- Create table to track budget distributions
CREATE TABLE IF NOT EXISTS organization_distributions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Organization Reference
    organization_id INT UNSIGNED NOT NULL,
    
    -- Distribution Details
    distribution_date DATE NOT NULL COMMENT 'Date of distribution',
    distribution_month VARCHAR(7) NOT NULL COMMENT 'Month of distribution (YYYY-MM format)',
    total_amount DECIMAL(15, 2) NOT NULL COMMENT 'Total amount distributed',
    amount_per_card DECIMAL(15, 2) NOT NULL COMMENT 'Amount distributed per card',
    cards_count INT UNSIGNED NOT NULL COMMENT 'Number of cards that received distribution',
    
    -- Source of funds
    source ENUM('organization_balance', 'manual', 'external') DEFAULT 'organization_balance' COMMENT 'Where the funds came from',
    
    -- Status
    status ENUM('completed', 'partial', 'failed') DEFAULT 'completed',
    notes TEXT DEFAULT NULL,
    
    -- Who performed the distribution
    distributed_by VARCHAR(100) DEFAULT NULL COMMENT 'Admin who performed the distribution',
    
    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    INDEX idx_organization_id (organization_id),
    INDEX idx_distribution_month (distribution_month),
    INDEX idx_distribution_date (distribution_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create table to track individual card distributions (for audit trail)
CREATE TABLE IF NOT EXISTS card_distribution_details (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- References
    distribution_id INT UNSIGNED NOT NULL,
    card_id VARCHAR(50) NOT NULL COMMENT 'Card ID that received the distribution',
    
    -- Amount Details
    amount DECIMAL(15, 2) NOT NULL COMMENT 'Amount distributed to this card',
    previous_balance DECIMAL(15, 2) NOT NULL COMMENT 'Card balance before distribution',
    new_balance DECIMAL(15, 2) NOT NULL COMMENT 'Card balance after distribution',
    
    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    FOREIGN KEY (distribution_id) REFERENCES organization_distributions(id) ON DELETE CASCADE,
    INDEX idx_distribution_id (distribution_id),
    INDEX idx_card_id (card_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
