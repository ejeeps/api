-- Adds ID type, ID number, and ID image columns when upgrading legacy passengers tables.
-- Fresh installs: 003_create_passengers_table.sql already defines these columns — IF NOT EXISTS skips them.
-- Requires MariaDB 10.0.2+ (typical XAMPP). Index idx_id_number is created in 003 only.

ALTER TABLE passengers
  ADD COLUMN IF NOT EXISTS id_type VARCHAR(50) DEFAULT NULL AFTER user_id,
  ADD COLUMN IF NOT EXISTS id_number VARCHAR(50) DEFAULT NULL AFTER id_type,
  ADD COLUMN IF NOT EXISTS id_image_front VARCHAR(255) DEFAULT NULL AFTER id_number,
  ADD COLUMN IF NOT EXISTS id_image_back VARCHAR(255) DEFAULT NULL AFTER id_image_front;
