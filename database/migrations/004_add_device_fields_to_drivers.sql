-- Add device assignment fields to drivers table
-- This migration adds device_assigned and device_serial_number columns
-- Run this if the drivers table already exists without these fields

ALTER TABLE drivers 
ADD COLUMN IF NOT EXISTS device_assigned VARCHAR(100) DEFAULT NULL AFTER background_check_document,
ADD COLUMN IF NOT EXISTS device_serial_number VARCHAR(100) DEFAULT NULL AFTER device_assigned;

-- If route_assigned column exists, you can optionally remove it:
-- ALTER TABLE drivers DROP COLUMN IF EXISTS route_assigned;

