-- Update fare_configurations with correct Taripa defaults
-- LTFRB rates: First 4km = ₱17.00, +₱2.00/km for regular

UPDATE fare_configurations 
SET 
    base_km = 4.00,
    base_fare = 17.00,
    rate_per_km = 2.00,
    fare_mode = 'taripa'
WHERE area_id IS NULL AND route_id IS NULL AND is_active = TRUE;
