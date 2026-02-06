-- Add ID number and ID images fields to passengers table
-- This can be used for government ID, student ID, or other identification numbers
ALTER TABLE passengers 
ADD COLUMN id_number VARCHAR(50) DEFAULT NULL AFTER user_id,
ADD COLUMN id_image_front VARCHAR(255) DEFAULT NULL AFTER id_number,
ADD COLUMN id_image_back VARCHAR(255) DEFAULT NULL AFTER id_image_front,
ADD INDEX idx_id_number (id_number);

