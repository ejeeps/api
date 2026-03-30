-- Add deduction_status column to track if fare was actually deducted from card
ALTER TABLE trip_fares 
ADD COLUMN deduction_status ENUM('success', 'failed', 'pending') DEFAULT 'pending' 
COMMENT 'Status of balance deduction: success, failed (insufficient balance), or pending'
AFTER final_fare_amount;

ALTER TABLE trip_fares 
ADD COLUMN deduction_error VARCHAR(255) DEFAULT NULL 
COMMENT 'Error message if deduction failed'
AFTER deduction_status;

-- Update existing records - assume they were successful if trip_transactions exists
UPDATE trip_fares tf
SET tf.deduction_status = 'success'
WHERE EXISTS (
    SELECT 1 FROM trip_transactions tt 
    WHERE tt.trip_id = tf.trip_id 
    AND tt.card_id = tf.card_id 
    AND tt.status = 'success'
);

-- Mark remaining as failed (no successful transaction found)
UPDATE trip_fares tf
SET tf.deduction_status = 'failed', tf.deduction_error = 'Insufficient balance or card issue'
WHERE tf.deduction_status = 'pending';
