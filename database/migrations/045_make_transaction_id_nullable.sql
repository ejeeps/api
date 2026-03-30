-- Make transaction_id nullable to support organization invoice payments
-- Organization invoice payments don't have associated trip transactions
ALTER TABLE trip_transaction_remarks 
    MODIFY COLUMN transaction_id INT UNSIGNED NULL COMMENT 'Reference to trip_transactions.id (NULL for organization invoice payments)';
