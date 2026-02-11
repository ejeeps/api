-- Add balance field to organizations table
-- Organizations can have their own balance account to distribute money to cards
ALTER TABLE organizations
ADD COLUMN balance DECIMAL(15, 2) DEFAULT 0.00 COMMENT 'Organization account balance' AFTER status,
ADD INDEX idx_balance (balance);
