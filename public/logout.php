<?php
require_once __DIR__ . '/../includes/auth.php';
logout();
flash('You have been logged out.');
header('Location: index.php');
exit;