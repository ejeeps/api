-- Add license_back_image field to drivers table
-- This migration adds license_back_image column for capturing the back of driver's license

ALTER TABLE drivers 
ADD COLUMN IF NOT EXISTS license_back_image VARCHAR(255) DEFAULT NULL AFTER license_image;


