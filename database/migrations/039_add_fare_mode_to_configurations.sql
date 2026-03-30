-- Add fare_mode column to fare_configurations table
-- Allows choosing between 'taripa' (LTFRB fare matrix) and 'base_km' (traditional base km pricing)

ALTER TABLE fare_configurations 
ADD COLUMN fare_mode ENUM('taripa', 'base_km') NOT NULL DEFAULT 'taripa' 
COMMENT 'Fare calculation mode: taripa = LTFRB fare matrix, base_km = traditional base km pricing'
AFTER rate_per_km;

-- Update existing configuration to use taripa mode
UPDATE fare_configurations SET fare_mode = 'taripa' WHERE is_active = TRUE;
