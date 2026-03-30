-- Create organization_invoices table for tracking invoice payments
-- Stores invoice records for organization balance top-ups and payments
CREATE TABLE IF NOT EXISTS organization_invoices (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Organization Reference
    organization_id INT UNSIGNED NOT NULL COMMENT 'Reference to organizations.id',
    
    -- Invoice Details
    invoice_number VARCHAR(50) NOT NULL COMMENT 'Unique invoice number (e.g., INV-2024-001)',
    invoice_month VARCHAR(7) NOT NULL COMMENT 'Invoice period in YYYY-MM format',
    total_amount DECIMAL(10, 2) NOT NULL COMMENT 'Total invoice amount (sum of negative balances)',
    paid_amount DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Amount already paid',
    balance_due DECIMAL(10, 2) NOT NULL COMMENT 'Remaining balance to pay',
    
    -- Status
    status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid' COMMENT 'Payment status',
    
    -- Payment Information
    payment_method ENUM('cash', 'check', 'bank_transfer', 'other') DEFAULT NULL COMMENT 'Method of payment',
    payment_reference VARCHAR(100) DEFAULT NULL COMMENT 'Check number, transaction ID, etc.',
    remarks TEXT DEFAULT NULL COMMENT 'Additional notes about the payment',
    paid_by VARCHAR(100) DEFAULT NULL COMMENT 'Name of person who made the payment',
    paid_at TIMESTAMP NULL DEFAULT NULL COMMENT 'When payment was made',
    
    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    UNIQUE KEY uk_invoice_number (invoice_number),
    INDEX idx_organization_id (organization_id),
    INDEX idx_invoice_month (invoice_month),
    INDEX idx_org_month (organization_id, invoice_month),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Organization invoice records for balance top-ups';

-- Add comment explaining the table purpose
ALTER TABLE organization_invoices 
    COMMENT = 'Tracks invoices for organization balance payments when org head pays in person';
