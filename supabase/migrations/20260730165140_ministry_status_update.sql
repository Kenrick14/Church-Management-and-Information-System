ALTER TABLE ministries
ADD COLUMN is_active BOOLEAN NOT NULL DEFAULT true;
CREATE INDEX idx_ministries_is_active ON ministries(is_active);