-- Create trigger to queue fare processing when tap OUT is inserted
-- Using PDO-compatible syntax (no DELIMITER)

-- First ensure queue table exists
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

-- Create the trigger
CREATE TRIGGER trg_queue_fare_processing
AFTER INSERT ON trips
FOR EACH ROW
BEGIN
    IF NEW.tap_level = 'OUT' AND NEW.card_id IS NOT NULL THEN
        IF NOT EXISTS (
            SELECT 1 FROM fare_processing_queue 
            WHERE trip_id = NEW.trip_id AND card_id = NEW.card_id AND status = 'pending'
        ) THEN
            INSERT INTO fare_processing_queue (trip_id, card_id, tap_out_trip_id, status)
            VALUES (NEW.trip_id, NEW.card_id, NEW.id, 'pending');
        END IF;
    END IF;
END;
