<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

function twig(): Environment {
    static $twig = null;
    if ($twig === null) {
        $loader = new FilesystemLoader(__DIR__ . '/../templates');
        $twig   = new Environment($loader, [
            'cache'            => false,
            'autoescape'       => 'html',
            'strict_variables' => false,
        ]);
        $twig->addFunction(new TwigFunction('csrf', 'csrf'));
        $twig->addFunction(new TwigFunction('cover_src', 'cover_src'));
        $twig->addGlobal('current_user', current_user());
    }
    return $twig;
}

function render(string $tpl, array $vars = []): string {
    return twig()->render($tpl . '.twig', $vars);
}

function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function csrf(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_ok(?string $t): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    return is_string($t) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function flash(?string $msg = null): ?string {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if ($msg !== null) { $_SESSION['flash'] = $msg; return null; }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function cover_src(?string $path): string {
    // If an image was uploaded, verify it exists in the new public/uploads folder
    if ($path && str_starts_with($path, 'uploads/')) {
        $file = __DIR__ . '/../public/' . $path;
        if (file_exists($file)) return e($path) . '?v=' . filemtime($file);
    }
    // Fallback SVG placeholder
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="160" height="240" viewBox="0 0 160 240">'
         . '<rect width="160" height="240" fill="#eef2ff"/>'
         . '<text x="80" y="140" font-size="70" text-anchor="middle">📖</text></svg>';
    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}

function handle_upload(array $file, ?string $oldPath): array {
    $cfg = require __DIR__ . '/../config/config.php';
    
    if (!isset($file['tmp_name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return [$oldPath, null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [null, 'Upload failed (code ' . $file['error'] . ').'];
    }
    if ($file['size'] > $cfg['max_upload_mb'] * 1024 * 1024) {
        return [null, 'Cover must be under ' . $cfg['max_upload_mb'] . ' MB.'];
    }
    
    // Security: Verify the actual file content, not just the extension
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $ext   = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext) return [null, 'Only JPG, PNG or WEBP allowed.'];

    // Correct path for your new folder structure
    $uploadDir = __DIR__ . '/../public/uploads';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    // Generate a random name to prevent overwriting and path traversal
    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    $dest = $uploadDir . '/' . $name;
    
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return [null, 'Could not save file.'];
    }
    
    // Delete the old image if updating a book
    if ($oldPath && str_starts_with($oldPath, 'uploads/')) {
        $old = $uploadDir . '/' . basename($oldPath);
        if (file_exists($old)) @unlink($old);
    }
    
    // Return the relative path saved in the database
    return ['uploads/' . $name, null];
}

/* ---------- CAPTCHA FUNCTIONS ---------- */
function captcha_generate(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $num1 = rand(2, 9);
    $num2 = rand(2, 9);
    $operator = rand(0, 1) ? '+' : '×';
    
    // Save the answer in the session
    $_SESSION['captcha_answer'] = ($operator === '+') ? ($num1 + $num2) : ($num1 * $num2);
    
    // Return the question to display on the screen
    return "$num1 $operator $num2";
}

function captcha_verify($user_answer): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    
    // Check if the session answer exists and matches the user's input
    if (isset($_SESSION['captcha_answer']) && (int)$user_answer === $_SESSION['captcha_answer']) {
        unset($_SESSION['captcha_answer']); // Destroy it so it can't be reused
        return true;
    }
    return false;
}
// mime

// captcha
