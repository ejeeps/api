-- Add fare_mode and is_discounted columns to trip_fares table
-- This allows tracking whether taripa or base_km was used and if discount was applied

ALTER TABLE trip_fares 
ADD COLUMN fare_mode ENUM('taripa', 'base_km', 'route_proportional') DEFAULT 'taripa' 
COMMENT 'Fare calculation mode used' AFTER calculation_method;

ALTER TABLE trip_fares 
ADD COLUMN is_discounted BOOLEAN DEFAULT FALSE 
COMMENT 'Whether discounted fare was applied (student/senior/pwd)' AFTER fare_mode;

ALTER TABLE trip_fares
ADD COLUMN km_distance INT UNSIGNED DEFAULT NULL
COMMENT 'Rounded km distance used for taripa lookup' AFTER is_discounted;
