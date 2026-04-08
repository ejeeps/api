<?php
declare(strict_types=1);

if (!function_exists('ejeepCustomerServiceEnsureTables')) {
    function ejeepCustomerServiceEnsureTables(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS customer_service_conversations (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS customer_service_messages (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS customer_service_attachments (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                message_id INT UNSIGNED NOT NULL,
                original_name VARCHAR(255) NOT NULL,
                stored_name VARCHAR(255) NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                mime_type VARCHAR(120) NOT NULL,
                file_size INT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX idx_message_id (message_id),
                CONSTRAINT fk_cs_attachment_message
                    FOREIGN KEY (message_id) REFERENCES customer_service_messages(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }
}

if (!function_exists('ejeepCustomerServiceUploadDir')) {
    function ejeepCustomerServiceUploadDir(): string
    {
        return dirname(__DIR__, 2) . '/uploads/customer_service/';
    }
}

if (!function_exists('ejeepCustomerServiceSaveAttachment')) {
    function ejeepCustomerServiceSaveAttachment(PDO $pdo, int $messageId, array $file): void
    {
        if ($messageId <= 0 || empty($file) || !isset($file['error'])) {
            return;
        }
        if ((int)$file['error'] === UPLOAD_ERR_NO_FILE) {
            return;
        }
        if ((int)$file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Attachment upload failed.');
        }

        $maxSize = 5 * 1024 * 1024;
        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxSize) {
            throw new RuntimeException('Attachment must be 1 byte to 5MB.');
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('Invalid attachment upload.');
        }

        $mime = (string)(mime_content_type($tmp) ?: '');
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
        ];
        if (!isset($allowed[$mime])) {
            throw new RuntimeException('Unsupported attachment type. Allowed: JPG, PNG, WEBP, PDF, TXT.');
        }

        $uploadDir = ejeepCustomerServiceUploadDir();
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            throw new RuntimeException('Unable to create upload directory.');
        }

        $safeOriginal = trim((string)($file['name'] ?? 'attachment'));
        $safeOriginal = preg_replace('/[^a-zA-Z0-9._-]/', '_', $safeOriginal);
        if ($safeOriginal === '') {
            $safeOriginal = 'attachment.' . $allowed[$mime];
        }

        $storedName = uniqid('cs_', true) . '.' . $allowed[$mime];
        $target = $uploadDir . $storedName;
        if (!move_uploaded_file($tmp, $target)) {
            throw new RuntimeException('Failed to store attachment.');
        }

        $relative = '/uploads/customer_service/' . $storedName;
        $stmt = $pdo->prepare(
            "INSERT INTO customer_service_attachments
                (message_id, original_name, stored_name, file_path, mime_type, file_size)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$messageId, $safeOriginal, $storedName, $relative, $mime, $size]);
    }
}

if (!function_exists('ejeepCustomerServiceMessageAttachments')) {
    function ejeepCustomerServiceMessageAttachments(PDO $pdo, array $messageIds): array
    {
        if (empty($messageIds)) {
            return [];
        }
        $messageIds = array_values(array_unique(array_map('intval', $messageIds)));
        $in = implode(',', array_fill(0, count($messageIds), '?'));
        $stmt = $pdo->prepare(
            "SELECT id, message_id, original_name, file_path, mime_type, file_size, created_at
             FROM customer_service_attachments
             WHERE message_id IN ($in)
             ORDER BY id ASC"
        );
        $stmt->execute($messageIds);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $map = [];
        foreach ($rows as $row) {
            $mid = (int)$row['message_id'];
            if (!isset($map[$mid])) {
                $map[$mid] = [];
            }
            $map[$mid][] = $row;
        }
        return $map;
    }
}

if (!function_exists('ejeepCustomerServicePassengerIdentity')) {
    function ejeepCustomerServicePassengerIdentity(PDO $pdo, int $userId): ?array
    {
        $stmt = $pdo->prepare(
            "SELECT first_name, last_name, email, phone_number
             FROM users
             WHERE id = ? AND user_level = 'passenger'
             LIMIT 1"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $name = trim(((string)($row['first_name'] ?? '')) . ' ' . ((string)($row['last_name'] ?? '')));
        $email = trim((string)($row['email'] ?? ''));
        $phone = trim((string)($row['phone_number'] ?? ''));
        $contact = $phone !== '' ? $phone : $email;

        return [
            'name' => $name !== '' ? $name : 'Passenger',
            'email' => $email,
            'phone' => $phone,
            'contact' => $contact,
        ];
    }
}

if (!function_exists('ejeepCustomerServiceListConversations')) {
    function ejeepCustomerServiceListConversations(PDO $pdo, array $identity): array
    {
        $sql = "
            SELECT c.*,
                   (SELECT COUNT(*) FROM customer_service_messages m WHERE m.conversation_id = c.id) AS message_count,
                   (SELECT m2.message FROM customer_service_messages m2 WHERE m2.conversation_id = c.id ORDER BY m2.created_at DESC, m2.id DESC LIMIT 1) AS latest_message
            FROM customer_service_conversations c
            WHERE c.customer_name = ?
        ";
        $params = [$identity['name']];

        if ($identity['contact'] !== '') {
            $sql .= " AND (c.customer_contact = ? OR c.customer_contact IS NULL OR c.customer_contact = '')";
            $params[] = $identity['contact'];
        } else {
            $sql .= " AND (c.customer_contact IS NULL OR c.customer_contact = '')";
        }

        $sql .= " ORDER BY COALESCE(c.last_message_at, c.created_at) DESC, c.id DESC LIMIT 100";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

if (!function_exists('ejeepCustomerServiceGetConversation')) {
    function ejeepCustomerServiceGetConversation(PDO $pdo, int $conversationId, array $identity): ?array
    {
        if ($conversationId <= 0) {
            return null;
        }

        $sql = "
            SELECT *
            FROM customer_service_conversations
            WHERE id = ? AND customer_name = ?
        ";
        $params = [$conversationId, $identity['name']];

        if ($identity['contact'] !== '') {
            $sql .= " AND (customer_contact = ? OR customer_contact IS NULL OR customer_contact = '')";
            $params[] = $identity['contact'];
        } else {
            $sql .= " AND (customer_contact IS NULL OR customer_contact = '')";
        }

        $sql .= " LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

if (!function_exists('ejeepCustomerServiceListMessages')) {
    function ejeepCustomerServiceListMessages(PDO $pdo, int $conversationId): array
    {
        if ($conversationId <= 0) {
            return [];
        }
        $stmt = $pdo->prepare(
            "SELECT id, conversation_id, sender_type, sender_name, message, created_at
             FROM customer_service_messages
             WHERE conversation_id = ?
             ORDER BY created_at ASC, id ASC"
        );
        $stmt->execute([$conversationId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $ids = array_map(static fn(array $r): int => (int)$r['id'], $rows);
        $attachmentMap = ejeepCustomerServiceMessageAttachments($pdo, $ids);
        foreach ($rows as &$row) {
            $row['attachments'] = $attachmentMap[(int)$row['id']] ?? [];
        }
        unset($row);
        return $rows;
    }
}

if (!function_exists('ejeepCustomerServiceHandlePost')) {
    function ejeepCustomerServiceHandlePost(PDO $pdo, int $userId, array $identity): array
    {
        $result = ['error' => null, 'success' => null];
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $result;
        }

        $action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';

        try {
            if ($action === 'create_conversation') {
                $subject = trim((string)($_POST['subject'] ?? ''));
                $message = trim((string)($_POST['message'] ?? ''));
                $priority = trim((string)($_POST['priority'] ?? 'medium'));
                $hasFile = isset($_FILES['attachment']) && (int)($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
                if ($subject === '' || ($message === '' && !$hasFile)) {
                    throw new RuntimeException('Subject and message or attachment are required.');
                }
                if (!in_array($priority, ['low', 'medium', 'high'], true)) {
                    $priority = 'medium';
                }

                $stmt = $pdo->prepare(
                    "INSERT INTO customer_service_conversations
                        (customer_name, customer_contact, subject, status, priority, created_by_admin_id, assigned_admin_id, last_message_at)
                     VALUES
                        (?, ?, ?, 'open', ?, NULL, NULL, NOW())"
                );
                $stmt->execute([$identity['name'], $identity['contact'] !== '' ? $identity['contact'] : null, $subject, $priority]);
                $conversationId = (int)$pdo->lastInsertId();

                $msg = $pdo->prepare(
                    "INSERT INTO customer_service_messages
                        (conversation_id, sender_type, sender_name, message, created_by_admin_id)
                     VALUES
                        (?, 'customer', ?, ?, NULL)"
                );
                $msg->execute([$conversationId, $identity['name'], $message]);
                $messageId = (int)$pdo->lastInsertId();
                if ($hasFile && isset($_FILES['attachment'])) {
                    ejeepCustomerServiceSaveAttachment($pdo, $messageId, $_FILES['attachment']);
                }
                $result['success'] = 'Support ticket created.';
            } elseif ($action === 'send_message') {
                $conversationId = (int)($_POST['conversation_id'] ?? 0);
                $message = trim((string)($_POST['message'] ?? ''));
                $hasFile = isset($_FILES['attachment']) && (int)($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
                if ($conversationId <= 0 || ($message === '' && !$hasFile)) {
                    throw new RuntimeException('Conversation and message or attachment are required.');
                }

                $conversation = ejeepCustomerServiceGetConversation($pdo, $conversationId, $identity);
                if (!$conversation) {
                    throw new RuntimeException('Conversation not found.');
                }

                $msg = $pdo->prepare(
                    "INSERT INTO customer_service_messages
                        (conversation_id, sender_type, sender_name, message, created_by_admin_id)
                     VALUES
                        (?, 'customer', ?, ?, NULL)"
                );
                $msg->execute([$conversationId, $identity['name'], $message]);
                $messageId = (int)$pdo->lastInsertId();
                if ($hasFile && isset($_FILES['attachment'])) {
                    ejeepCustomerServiceSaveAttachment($pdo, $messageId, $_FILES['attachment']);
                }

                $up = $pdo->prepare("UPDATE customer_service_conversations SET last_message_at = NOW() WHERE id = ?");
                $up->execute([$conversationId]);
                $result['success'] = 'Message sent.';
            }
        } catch (Throwable $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }
}
