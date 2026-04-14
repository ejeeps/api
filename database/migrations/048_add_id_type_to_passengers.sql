-- Add id_type when passengers already has id_number / images from an older 010 migration
-- that did not include id_type. Skip if id_type already exists.

ALTER TABLE passengers
ADD COLUMN id_type VARCHAR(50) DEFAULT NULL AFTER user_id;
