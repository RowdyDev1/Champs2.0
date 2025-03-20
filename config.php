<?php
// Database configuration
$host = 'localhost';
$db = 'users_db';
$user = 'root';
$pass = 'AARRU#champs';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// API Key configuration
define('API_KEY', 'd859bfaa-c3a4-4190-a411-bdbb47df8b90');
?>
