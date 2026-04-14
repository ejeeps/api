-- Adds ID type, ID number, and ID image columns to passengers.
-- Intended for databases created from an older 003 that had no ID columns.
-- Column order: user_id → id_type → id_number → id_image_front → id_image_back
--
-- If some columns already exist (e.g. you ran an older version of this file without id_type),
-- run 048_add_id_type_to_passengers.sql instead to add only id_type, or add missing columns manually.

ALTER TABLE passengers 
ADD COLUMN id_type VARCHAR(50) DEFAULT NULL AFTER user_id,
ADD COLUMN id_number VARCHAR(50) DEFAULT NULL AFTER id_type,
ADD COLUMN id_image_front VARCHAR(255) DEFAULT NULL AFTER id_number,
ADD COLUMN id_image_back VARCHAR(255) DEFAULT NULL AFTER id_image_front,
ADD INDEX idx_id_number (id_number);
