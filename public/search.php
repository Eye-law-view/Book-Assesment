<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if ($q === '' || strlen($q) > 100) { echo '[]'; exit; }

$stmt = $pdo->prepare(
    "SELECT id, title, author FROM books
     WHERE title LIKE :q OR author LIKE :q
     ORDER BY title LIMIT 8"
);
$stmt->execute(['q' => "%$q%"]);
echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);