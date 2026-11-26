<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// If already logged in, go to the home page
if (current_user()) redirect('index.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Check CSRF Token
    if (!csrf_ok($_POST['csrf'] ?? null)) {
        $errors[] = 'Session expired. Please try again.';
    } 
    // 2. Check CAPTCHA
    elseif (!captcha_verify($_POST['captcha'] ?? '')) { 
        $errors[] = 'Incorrect CAPTCHA answer.';         
    } 
    // 3. Check Username & Password
    else {
        $u = trim($_POST['username'] ?? '');
        $p = (string)($_POST['password'] ?? '');
        
        if ($u === '' || $p === '') {
            $errors[] = 'Both fields are required.';
        } elseif (login_attempt($pdo, $u, $p)) {
            flash('Welcome back, ' . $u . '!');
            redirect('index.php');
        } else {
            $errors[] = 'Invalid username or password.';
        }
    }
}

// Generate a new math question for the form (happens on GET, or if POST fails)
$captcha_question = captcha_generate(); 

// Render the Twig template
echo render('login', [
    'errors' => $errors, 
    'csrf' => csrf(), 
    'captcha_question' => $captcha_question
]);
// verify
