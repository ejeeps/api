-- Add osrm_geometry column to trip_fares table
-- Stores the OSRM route coordinates as JSON for frontend map rendering
ALTER TABLE trip_fares
ADD COLUMN osrm_geometry JSON DEFAULT NULL COMMENT 'OSRM route coordinates as GeoJSON for map rendering' AFTER calculation_method;
