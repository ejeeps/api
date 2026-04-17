<?php
// Detect environment (SERVER_NAME alone misses many local setups; align with HTTP_HOST)
$host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
$host = explode(':', $host)[0];
$isLocal = in_array($host, [
    'localhost',
    '127.0.0.1',
    '::1',
    '192.168.102.212',
], true)
    || (strlen($host) >= 6 && substr($host, -6) === '.local')
    || (strlen($host) >= 5 && substr($host, -5) === '.test')
    || preg_match('/^192\.168\.\d{1,3}\.\d{1,3}$/', $host)
    || preg_match('/^10\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $host);

if ($isLocal) {
    // LOCAL (XAMPP)
    $dbhost = 'localhost';
    $dbuser = 'root';
    $dbpass = '';
    $dbname = 'ejeep_db';
} else {
    // SERVER (Hostinger)
    $dbhost = 'localhost';
    $dbuser = 'u951669574_xmas';
    $dbpass = '?0Gm|uH|n6';
    $dbname = 'u951669574_xmas';
}

/** Idempotent: add passengers.id_type if missing (matches migration 048). */
function ejeep_ensure_passengers_id_type(PDO $pdo): void
{
    try {
        $colCheck = $pdo->prepare(
            'SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
        );
        $colCheck->execute(['passengers', 'id_type']);
        if (!$colCheck->fetchColumn()) {
            $pdo->exec('ALTER TABLE passengers ADD COLUMN id_type VARCHAR(50) DEFAULT NULL AFTER user_id');
        }
    } catch (Throwable $e) {
        // No passengers table, no ALTER privilege, or non-standard setup — leave as-is.
    }
}

class Database {
    private $connection;
    
    public function __construct() {
        global $dbhost, $dbuser, $dbpass, $dbname;
        
        try {
            $this->connection = new PDO(
                "mysql:host={$dbhost};dbname={$dbname};charset=utf8mb4",
                $dbuser,
                $dbpass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            ejeep_ensure_passengers_id_type($this->connection);
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
}

// Create global PDO instance for backward compatibility
try {
    $pdo = new PDO(
        "mysql:host={$dbhost};dbname={$dbname};charset=utf8mb4",
        $dbuser,
        $dbpass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    ejeep_ensure_passengers_id_type($pdo);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
