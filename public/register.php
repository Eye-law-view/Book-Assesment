<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (current_user()) redirect('index.php');

$errors = [];
$old    = ['username' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok($_POST['csrf'] ?? null)) {
        $errors[] = 'Session expired.';
    } else {
        $old['username'] = trim($_POST['username'] ?? '');
        $p1 = (string)($_POST['password']  ?? '');
        $p2 = (string)($_POST['password2'] ?? '');

        if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $old['username']))
            $errors[] = 'Username: 3–30 chars, letters/numbers/underscore only.';
        if (strlen($p1) < 8)
            $errors[] = 'Password must be at least 8 characters.';
        if ($p1 !== $p2)
            $errors[] = 'Passwords do not match.';

        if (!$errors) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :u");
            $stmt->execute(['u' => $old['username']]);
            if ($stmt->fetch()) {
                $errors[] = 'That username is already taken.';
            } else {
                $hash = password_hash($p1, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (:u, :h)");
                $stmt->execute(['u' => $old['username'], 'h' => $hash]);
                flash('Account created. Please log in.');
                redirect('login.php');
            }
        }
    }
}

echo render('register', ['errors' => $errors, 'old' => $old, 'csrf' => csrf()]);