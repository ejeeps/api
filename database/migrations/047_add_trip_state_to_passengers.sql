-- Persist passenger trip activity state for dashboard/status features.
-- `ended` is used as the default non-active state.
ALTER TABLE passengers
ADD COLUMN trip_activity_status ENUM('ongoing', 'ended') NOT NULL DEFAULT 'ended' AFTER postal_code,
ADD COLUMN trip_last_seen_at DATETIME NULL AFTER trip_activity_status,
ADD COLUMN trip_status_updated_at DATETIME NULL AFTER trip_last_seen_at;

CREATE INDEX idx_passengers_trip_activity_status ON passengers(trip_activity_status);
CREATE INDEX idx_passengers_trip_last_seen_at ON passengers(trip_last_seen_at);
