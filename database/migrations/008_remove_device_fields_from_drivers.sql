-- Remove device assignment fields from drivers table
-- Device assignments are now managed through the device_assignments table
-- This migration removes device_assigned and device_serial_number columns

ALTER TABLE drivers 
DROP COLUMN IF EXISTS device_assigned,
DROP COLUMN IF EXISTS device_serial_number;

