-- Fix transactions table migration
-- This migration safely creates or fixes the transactions table

-- First, drop the table if it exists (to clean up any partial creation)
DROP TABLE IF EXISTS transactions;

-- Create the transactions table with correct structure
CREATE TABLE transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    card_id INT UNSIGNED NULL,
    transaction_reference VARCHAR(100) NOT NULL UNIQUE,
    payment_intent_id VARCHAR(255) NULL,
    checkout_session_id VARCHAR(255) NULL,
    amount DECIMAL(10, 2) NOT NULL,
    transaction_type ENUM('top_up', 'purchase', 'refund', 'adjustment') NOT NULL DEFAULT 'top_up',
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending',
    payment_method VARCHAR(50) NULL,
    payment_method_details JSON NULL,
    description TEXT NULL,
    metadata JSON NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    CONSTRAINT fk_transactions_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_transactions_card_id FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE SET NULL,
    
    -- Indexes for better performance
    INDEX idx_user_id (user_id),
    INDEX idx_card_id (card_id),
    INDEX idx_transaction_reference (transaction_reference),
    INDEX idx_payment_intent_id (payment_intent_id),
    INDEX idx_status (status),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores all payment transactions including PayMongo payments';