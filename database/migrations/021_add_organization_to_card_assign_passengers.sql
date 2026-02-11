-- Add organization sponsorship tracking to card_assign_passengers table
-- This allows organizations (like LGU) to sponsor/claim passenger cards
-- When an organization claims a passenger card, the passenger doesn't need to pay

ALTER TABLE card_assign_passengers
ADD COLUMN organization_id INT UNSIGNED DEFAULT NULL COMMENT 'Organization that sponsors/claims this passenger card assignment' AFTER card_id,
ADD COLUMN sponsored_at TIMESTAMP NULL DEFAULT NULL COMMENT 'When the organization claimed/sponsored this card' AFTER organization_id,
ADD CONSTRAINT fk_card_assign_passengers_organization FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL ON UPDATE CASCADE,
ADD INDEX idx_organization_id (organization_id),
ADD INDEX idx_sponsored_at (sponsored_at);
