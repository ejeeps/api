-- Add organization and sponsorship fields to cards table
-- Links cards to organizations that sponsor/issue them (e.g., LGU sponsoring student cards)
ALTER TABLE cards
ADD COLUMN organization_id INT UNSIGNED DEFAULT NULL COMMENT 'Organization that sponsored/issued this card' AFTER card_type,
ADD COLUMN is_sponsored BOOLEAN DEFAULT FALSE COMMENT 'Whether this card is sponsored (free) by an organization' AFTER organization_id,
ADD COLUMN sponsorship_type VARCHAR(50) DEFAULT NULL COMMENT 'Type of sponsorship (e.g., scholarship, employee benefit, etc.)' AFTER is_sponsored,
ADD CONSTRAINT fk_cards_organization FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL ON UPDATE CASCADE,
ADD INDEX idx_organization_id (organization_id),
ADD INDEX idx_is_sponsored (is_sponsored);
