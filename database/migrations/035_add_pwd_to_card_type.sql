-- Add 'pwd' (Person With Disability) to card_type ENUM
-- This migration alters the existing cards table

ALTER TABLE cards 
MODIFY COLUMN card_type ENUM('driver','student', 'senior', 'regular', 'employee', 'special', 'pwd', 'admin') 
DEFAULT 'regular' COMMENT 'Type of card';
