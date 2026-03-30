-- Fix collation mismatch between tables
-- This resolves: SQLSTATE[HY000]: General error: 1267 Illegal mix of collations 
-- (utf8mb4_unicode_ci,COERCIBLE) and (utf8mb4_general_ci,COERCIBLE) for operation '='

-- Convert trip_transactions table to utf8mb4_unicode_ci
ALTER TABLE trip_transactions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Convert trip_fares table to utf8mb4_unicode_ci
ALTER TABLE trip_fares CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Convert cards table to utf8mb4_unicode_ci (ensure consistency)
ALTER TABLE cards CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Convert trips table to utf8mb4_unicode_ci
ALTER TABLE trips CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Convert organizations table to utf8mb4_unicode_ci
ALTER TABLE organizations CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Convert organization_distributions table to utf8mb4_unicode_ci
ALTER TABLE organization_distributions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Convert fare_configurations table to utf8mb4_unicode_ci
ALTER TABLE fare_configurations CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Convert passengers table to utf8mb4_unicode_ci
ALTER TABLE passengers CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Convert drivers table to utf8mb4_unicode_ci
ALTER TABLE drivers CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Convert routes table to utf8mb4_unicode_ci
ALTER TABLE routes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Convert areas table to utf8mb4_unicode_ci
ALTER TABLE areas CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
