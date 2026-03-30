-- Add coordinate columns to routes table for Leaflet map integration
-- Stores start and end coordinates for route visualization and distance calculation

-- Add start coordinates
ALTER TABLE routes
ADD COLUMN start_lat DECIMAL(10, 8) DEFAULT NULL COMMENT 'Start latitude coordinate' AFTER fare_amount,
ADD COLUMN start_lng DECIMAL(11, 8) DEFAULT NULL COMMENT 'Start longitude coordinate' AFTER start_lat;

-- Add end coordinates
ALTER TABLE routes
ADD COLUMN end_lat DECIMAL(10, 8) DEFAULT NULL COMMENT 'End latitude coordinate' AFTER start_lng,
ADD COLUMN end_lng DECIMAL(11, 8) DEFAULT NULL COMMENT 'End longitude coordinate' AFTER end_lat;

-- Add index for coordinate-based queries
ALTER TABLE routes
ADD INDEX idx_coordinates (start_lat, start_lng, end_lat, end_lng);
