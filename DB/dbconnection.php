<?php
$dbHost = getenv('DB_HOST');
$dbName = getenv('DB_NAME');
$dbUser = getenv('DB_USER');
$dbPassword = getenv('DB_PASS');

if ($dbHost === false || $dbHost === '') {
    $dbHost = 'localhost';
}
if ($dbName === false || $dbName === '') {
    $dbName = 'sdb';
}
if ($dbUser === false || $dbUser === '') {
    $dbUser = 'root';
}
if ($dbPassword === false) {
    $dbPassword = '';
}

try {
    $conn = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPassword,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $ex) {
    error_log('Database connection failed: ' . $ex->getMessage());
    http_response_code(500);
    exit('Database connection failed. Check the DB_HOST, DB_NAME, DB_USER, and DB_PASS settings.');
}
