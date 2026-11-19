<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$action = $_GET['action'] ?? 'list';
$user   = current_user();

/* ---------- SAVE ---------- */
if ($action === 'save') {
    require_login();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok($_POST['csrf'] ?? null)) {
        http_response_code(403); exit('CSRF token invalid.');
    }
    $id   = (int)($_POST['id'] ?? 0);
    $data = [
        'title'       => trim($_POST['title']       ?? ''),
        'author'      => trim($_POST['author']      ?? ''),
        'genre'       => trim($_POST['genre']       ?? ''),
        'year'        => (int)($_POST['year']       ?? 0),
        'isbn'        => trim($_POST['isbn']        ?? ''),
        'description' => trim($_POST['description'] ?? ''),
    ];
    $errors = [];
    if ($data['title'] === '')                      $errors[] = 'Title is required.';
    if (strlen($data['title']) > 200)               $errors[] = 'Title too long (max 200).';
    if ($data['author'] === '')                     $errors[] = 'Author is required.';
    if ($data['genre'] === '')                      $errors[] = 'Genre is required.';
    if ($data['year'] < 1000 || $data['year'] > (int)date('Y') + 1)
                                                    $errors[] = 'Year must be between 1000 and ' . ((int)date('Y') + 1) . '.';
    if (strlen($data['isbn']) > 20)                 $errors[] = 'ISBN too long.';

    $oldCover = null;
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT cover FROM books WHERE id = :id AND user_id = :u");
        $stmt->execute(['id' => $id, 'u' => $user['id']]);
        $row = $stmt->fetch();
        if (!$row) { http_response_code(403); exit('Forbidden.'); }
        $oldCover = $row['cover'];
    }
    if (!empty($_POST['remove_cover']) && $oldCover) {
        $f = __DIR__ . '/uploads/' . basename($oldCover);
        if (file_exists($f)) @unlink($f);
        $oldCover = null;
    }
    [$cover, $upErr] = handle_upload($_FILES['cover'] ?? [], $oldCover);
    if ($upErr) $errors[] = $upErr;
    $data['cover'] = $cover;

    if ($errors) {
        echo render('form', [
            'book' => $data + ['id' => $id], 'genres' => genres($pdo),
            'csrf' => csrf(), 'errors' => $errors,
        ]);
        exit;
    }

    if ($id > 0) {
        $stmt = $pdo->prepare(
            "UPDATE books SET title=:title, author=:author, genre=:genre,
             year=:year, isbn=:isbn, description=:description, cover=:cover
             WHERE id=:id AND user_id=:u"
        );
        $stmt->execute($data + ['id' => $id, 'u' => $user['id']]);
        flash('Book updated.');
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO books (user_id, title, author, genre, year, isbn, description, cover)
             VALUES (:u, :title, :author, :genre, :year, :isbn, :description, :cover)"
        );
        $stmt->execute($data + ['u' => $user['id']]);
        flash('Book added.');
    }
    redirect('index.php');
}

/* ---------- DELETE ---------- */
if ($action === 'delete') {
    require_login();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok($_POST['csrf'] ?? null)) {
        http_response_code(403); exit('CSRF token invalid.');
    }
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT cover FROM books WHERE id = :id AND user_id = :u");
    $stmt->execute(['id' => $id, 'u' => $user['id']]);
    $cover = $stmt->fetchColumn();
    if ($cover === false) { http_response_code(403); exit('Forbidden.'); }
    if ($cover) {
        $f = __DIR__ . '/uploads/' . basename($cover);
        if (file_exists($f)) @unlink($f);
    }
    $stmt = $pdo->prepare("DELETE FROM books WHERE id = :id AND user_id = :u");
    $stmt->execute(['id' => $id, 'u' => $user['id']]);
    flash('Book deleted.');
    redirect('index.php');
}

/* ---------- EDIT ---------- */
if ($action === 'edit') {
    require_login();
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = :id AND user_id = :u");
    $stmt->execute(['id' => (int)($_GET['id'] ?? 0), 'u' => $user['id']]);
    $book = $stmt->fetch();
    if (!$book) { http_response_code(404); echo render('404'); exit; }
    echo render('form', [
        'book' => $book, 'genres' => genres($pdo), 'csrf' => csrf(), 'errors' => [],
    ]);
    exit;
}

/* ---------- NEW ---------- */
if ($action === 'new') {
    require_login();
    echo render('form', [
        'book' => ['id'=>0,'title'=>'','author'=>'','genre'=>'','year'=>'',
                   'isbn'=>'','description'=>'','cover'=>null],
        'genres' => genres($pdo), 'csrf' => csrf(), 'errors' => [],
    ]);
    exit;
}

/* ---------- LIST + MULTI-CRITERIA SEARCH ---------- */
$title  = trim($_GET['title']  ?? '');
$author = trim($_GET['author'] ?? '');
$genre  = trim($_GET['genre']  ?? '');
$year   = ($_GET['year'] ?? '') !== '' ? (int)$_GET['year'] : null;

$sql    = "SELECT b.*, u.username FROM books b JOIN users u ON u.id = b.user_id WHERE 1=1";
$params = [];
if ($title  !== '') { $sql .= " AND b.title  LIKE :title";  $params[':title']  = "%$title%"; }
if ($author !== '') { $sql .= " AND b.author LIKE :author"; $params[':author'] = "%$author%"; }
if ($genre  !== '') { $sql .= " AND b.genre  = :genre";     $params[':genre']  = $genre; }
if ($year !== null) { $sql .= " AND b.year   = :year";      $params[':year']   = $year; }
$sql .= " ORDER BY b.title";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

echo render('index', [
    'books' => $books, 'genres' => genres($pdo),
    'filters' => compact('title','author','genre','year'),
    'csrf' => csrf(), 'flash' => flash(),
]);

function genres(PDO $pdo): array {
    return $pdo->query("SELECT DISTINCT genre FROM books ORDER BY genre")
               ->fetchAll(PDO::FETCH_COLUMN);
}
// Final check: Ensure DB connection and routing are secure.

// Final check: Ensure DB connection and routing are secure.

// final check for db connection

// final code review

// search

// add/edit

// delete

// owner
