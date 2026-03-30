-- Create taripa_rates table for LTFRB fare matrix
-- Stores distance-based fare rates for Regular and Discounted passengers
-- Based on NON-AIRCON MODERN AND ELECTRIC PUJ GENERAL FARE GUIDE

CREATE TABLE IF NOT EXISTS taripa_rates (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Distance tier
    distance_km INT UNSIGNED NOT NULL COMMENT 'Distance in kilometers (1-50+)',
    distance_label VARCHAR(50) NOT NULL COMMENT 'Human readable label (e.g., "1-4 km", "5 km")',
    
    -- Fare rates
    regular_fare DECIMAL(10, 2) NOT NULL COMMENT 'Regular passenger fare',
    discounted_fare DECIMAL(10, 2) NOT NULL COMMENT 'Discounted fare (Student/Elderly/PWD - 20% off)',
    
    -- Configuration
    vehicle_type ENUM('non_aircon', 'aircon', 'electric') DEFAULT 'non_aircon' COMMENT 'Type of vehicle',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Whether this rate is active',
    
    -- Validity period
    effective_date DATE NOT NULL COMMENT 'When this rate becomes effective',
    expiry_date DATE DEFAULT NULL COMMENT 'When this rate expires (NULL = no expiry)',
    
    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    UNIQUE KEY uk_distance_vehicle (distance_km, vehicle_type),
    INDEX idx_vehicle_type (vehicle_type),
    INDEX idx_is_active (is_active),
    INDEX idx_effective_date (effective_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='LTFRB Fare Matrix (Taripa)';

-- Insert default fare rates based on March 19, 2026 LTFRB guide
-- NON-AIRCON MODERN AND ELECTRIC PUJ
-- Regular: First 4km = ₱17.00, Succeeding km = +₱2.00 per km
-- Discounted: First 4km = ₱13.50, Succeeding km = +₱1.60 per km (20% discount)

INSERT INTO taripa_rates (distance_km, distance_label, regular_fare, discounted_fare, vehicle_type, effective_date, is_active) VALUES
-- First 4 km (base fare tier)
(4, '1-4 km', 17.00, 13.50, 'non_aircon', '2026-03-19', TRUE),

-- 5 km onwards
(5, '5 km', 19.00, 15.25, 'non_aircon', '2026-03-19', TRUE),
(6, '6 km', 21.00, 16.75, 'non_aircon', '2026-03-19', TRUE),
(7, '7 km', 23.00, 18.50, 'non_aircon', '2026-03-19', TRUE),
(8, '8 km', 25.00, 20.00, 'non_aircon', '2026-03-19', TRUE),
(9, '9 km', 27.00, 21.50, 'non_aircon', '2026-03-19', TRUE),
(10, '10 km', 29.00, 23.25, 'non_aircon', '2026-03-19', TRUE),
(11, '11 km', 31.00, 24.75, 'non_aircon', '2026-03-19', TRUE),
(12, '12 km', 33.00, 26.50, 'non_aircon', '2026-03-19', TRUE),
(13, '13 km', 35.00, 28.00, 'non_aircon', '2026-03-19', TRUE),
(14, '14 km', 37.00, 29.50, 'non_aircon', '2026-03-19', TRUE),
(15, '15 km', 39.00, 31.25, 'non_aircon', '2026-03-19', TRUE),
(16, '16 km', 41.00, 32.75, 'non_aircon', '2026-03-19', TRUE),
(17, '17 km', 43.00, 34.50, 'non_aircon', '2026-03-19', TRUE),
(18, '18 km', 45.00, 36.00, 'non_aircon', '2026-03-19', TRUE),
(19, '19 km', 47.00, 37.50, 'non_aircon', '2026-03-19', TRUE),
(20, '20 km', 49.00, 39.25, 'non_aircon', '2026-03-19', TRUE),
(21, '21 km', 51.00, 40.75, 'non_aircon', '2026-03-19', TRUE),
(22, '22 km', 53.00, 42.50, 'non_aircon', '2026-03-19', TRUE),
(23, '23 km', 55.00, 44.00, 'non_aircon', '2026-03-19', TRUE),
(24, '24 km', 57.00, 45.50, 'non_aircon', '2026-03-19', TRUE),
(25, '25 km', 59.00, 47.25, 'non_aircon', '2026-03-19', TRUE),
(26, '26 km', 61.00, 48.75, 'non_aircon', '2026-03-19', TRUE),
(27, '27 km', 63.00, 50.50, 'non_aircon', '2026-03-19', TRUE),
(28, '28 km', 65.00, 52.00, 'non_aircon', '2026-03-19', TRUE),
(29, '29 km', 67.00, 53.50, 'non_aircon', '2026-03-19', TRUE),
(30, '30 km', 69.00, 55.25, 'non_aircon', '2026-03-19', TRUE),
(31, '31 km', 71.00, 56.75, 'non_aircon', '2026-03-19', TRUE),
(32, '32 km', 73.00, 58.50, 'non_aircon', '2026-03-19', TRUE),
(33, '33 km', 75.00, 60.00, 'non_aircon', '2026-03-19', TRUE),
(34, '34 km', 77.00, 61.50, 'non_aircon', '2026-03-19', TRUE),
(35, '35 km', 79.00, 63.25, 'non_aircon', '2026-03-19', TRUE),
(36, '36 km', 81.00, 64.75, 'non_aircon', '2026-03-19', TRUE),
(37, '37 km', 83.00, 66.50, 'non_aircon', '2026-03-19', TRUE),
(38, '38 km', 85.00, 68.00, 'non_aircon', '2026-03-19', TRUE),
(39, '39 km', 87.00, 69.50, 'non_aircon', '2026-03-19', TRUE),
(40, '40 km', 89.00, 71.25, 'non_aircon', '2026-03-19', TRUE),
(41, '41 km', 91.00, 72.75, 'non_aircon', '2026-03-19', TRUE),
(42, '42 km', 93.00, 74.50, 'non_aircon', '2026-03-19', TRUE),
(43, '43 km', 95.00, 76.00, 'non_aircon', '2026-03-19', TRUE),
(44, '44 km', 97.00, 77.50, 'non_aircon', '2026-03-19', TRUE),
(45, '45 km', 99.00, 79.25, 'non_aircon', '2026-03-19', TRUE),
(46, '46 km', 101.00, 80.75, 'non_aircon', '2026-03-19', TRUE),
(47, '47 km', 103.00, 82.50, 'non_aircon', '2026-03-19', TRUE),
(48, '48 km', 105.00, 84.00, 'non_aircon', '2026-03-19', TRUE),
(49, '49 km', 107.00, 85.50, 'non_aircon', '2026-03-19', TRUE),
(50, '50 km', 109.00, 87.25, 'non_aircon', '2026-03-19', TRUE);
