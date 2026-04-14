-- Offline fallback for Philippine province / city lookup (passenger address step).
-- Populated by 050_seed_ph_provinces.sql (provinces). Cities may stay empty when using the online PSGC API (see PhAddressController).

CREATE TABLE IF NOT EXISTS ph_provinces (
    code VARCHAR(20) NOT NULL,
    name VARCHAR(120) NOT NULL,
    PRIMARY KEY (code),
    KEY idx_ph_province_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ph_cities (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    province_code VARCHAR(20) NOT NULL,
    name VARCHAR(160) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_ph_city_province (province_code),
    KEY idx_ph_city_name (name),
    CONSTRAINT fk_ph_cities_province FOREIGN KEY (province_code) REFERENCES ph_provinces(code) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
