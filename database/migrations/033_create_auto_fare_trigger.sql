-- Create trigger to automatically process fare calculation when tap OUT is inserted
-- This trigger calls a stored procedure that logs the request for PHP processing

-- First, create a processing queue table
CREATE TABLE IF NOT EXISTS fare_processing_queue (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    trip_id VARCHAR(50) NOT NULL,
    card_id VARCHAR(50) NOT NULL,
    tap_out_trip_id INT UNSIGNED NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    error_message TEXT NULL,
    PRIMARY KEY (id),
    INDEX idx_status (status),
    INDEX idx_trip_id (trip_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Drop existing trigger if exists
DROP TRIGGER IF EXISTS trg_queue_fare_processing;

-- Create trigger to queue fare processing when tap OUT is inserted
CREATE TRIGGER trg_queue_fare_processing
AFTER INSERT ON trips
FOR EACH ROW
BEGIN
    -- Only process tap OUT records with card_id
    IF NEW.tap_level = 'OUT' AND NEW.card_id IS NOT NULL THEN
        -- Check if this trip hasn't been processed yet
        IF NOT EXISTS (
            SELECT 1 FROM fare_processing_queue 
            WHERE trip_id = NEW.trip_id AND card_id = NEW.card_id AND status = 'pending'
        ) THEN
            INSERT INTO fare_processing_queue (trip_id, card_id, tap_out_trip_id, status)
            VALUES (NEW.trip_id, NEW.card_id, NEW.id, 'pending');
        END IF;
    END IF;
END;
