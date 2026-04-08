CREATE TABLE IF NOT EXISTS customer_service_conversations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_name VARCHAR(150) NOT NULL,
    customer_contact VARCHAR(190) DEFAULT NULL,
    subject VARCHAR(190) NOT NULL,
    status ENUM('open', 'pending', 'resolved', 'closed') NOT NULL DEFAULT 'open',
    priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    created_by_admin_id INT UNSIGNED DEFAULT NULL,
    assigned_admin_id INT UNSIGNED DEFAULT NULL,
    last_message_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_last_message_at (last_message_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customer_service_messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    conversation_id INT UNSIGNED NOT NULL,
    sender_type ENUM('customer', 'admin') NOT NULL DEFAULT 'admin',
    sender_name VARCHAR(150) DEFAULT NULL,
    message TEXT NOT NULL,
    created_by_admin_id INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_cs_messages_conversation
        FOREIGN KEY (conversation_id) REFERENCES customer_service_conversations(id)
        ON DELETE CASCADE,
    INDEX idx_conversation_id (conversation_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
