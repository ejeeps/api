-- Add id_type when passengers already has id_number / images from an older 010 migration
-- that did not include id_type. Safe when id_type already exists (e.g. from 003 or 010).

ALTER TABLE passengers
ADD COLUMN IF NOT EXISTS id_type VARCHAR(50) DEFAULT NULL AFTER user_id;
