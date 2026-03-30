-- Create trip_transactions table for fare deduction transactions
-- Stores balance deductions from card when trip fare is calculated
CREATE TABLE IF NOT EXISTS trip_transactions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Transaction References
    card_id VARCHAR(50) NOT NULL COMMENT 'Card ID number (references cards.card_id_number)',
    trip_id VARCHAR(50) NOT NULL COMMENT 'Trip ID for reference',
    trip_fare_id INT UNSIGNED DEFAULT NULL COMMENT 'Reference to trip_fares record',
    
    -- Transaction Details
    amount DECIMAL(10, 2) NOT NULL COMMENT 'Amount deducted',
    type ENUM('deduction', 'refund', 'adjustment') DEFAULT 'deduction' COMMENT 'Transaction type',
    
    -- Balance Tracking
    previous_balance DECIMAL(10, 2) NOT NULL COMMENT 'Card balance before deduction',
    new_balance DECIMAL(10, 2) NOT NULL COMMENT 'Card balance after deduction',
    
    -- Status
    status ENUM('success', 'failed', 'insufficient_balance', 'card_inactive') DEFAULT 'success' COMMENT 'Transaction status',
    description TEXT DEFAULT NULL COMMENT 'Transaction description or error message',
    
    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    FOREIGN KEY (trip_fare_id) REFERENCES trip_fares(id) ON DELETE SET NULL,
    INDEX idx_card_id (card_id),
    INDEX idx_trip_id (trip_id),
    INDEX idx_trip_fare_id (trip_fare_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
