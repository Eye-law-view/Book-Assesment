<?php
$cfg = require __DIR__ . '/../config/config.php';
$d   = $cfg['db'];

try {
    $pdo = new PDO(
        "mysql:host={$d['host']};dbname={$d['name']};charset={$d['charset']}",
        $d['user'], $d['pass'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log($e->getMessage());
    exit('Database unavailable.');
}