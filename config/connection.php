<?php
// Detect environment
$isLocal = in_array($_SERVER['SERVER_NAME'], [
    'localhost',
    '192.168.102.212',
    '127.0.0.1'
]);

if ($isLocal) {
    // LOCAL (XAMPP)
    $dbhost = 'localhost';
    $dbuser = 'root';
    $dbpass = '';
    $dbname = 'ejeepdb';
} else {
    // SERVER (Hostinger)
    $dbhost = 'localhost';
    $dbuser = 'u951669574_xmas';
    $dbpass = '?0Gm|uH|n6';
    $dbname = 'u951669574_xmas';
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
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
