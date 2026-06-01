<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? ($_SERVER['REQUEST_METHOD'] === 'POST' ? 'post' : 'list');

try {
    $db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($action === 'post') {
        // Accept both JSON body and form POST
        $body = json_decode(file_get_contents('php://input'), true);
        $name    = trim($body['name'] ?? $_POST['name'] ?? '');
        $comment = trim($body['comment'] ?? $_POST['comment'] ?? '');

        if (empty($name) || empty($comment)) {
            echo json_encode(['success' => false, 'error' => 'Name and comment are required.']);
            exit;
        }
        if (mb_strlen($name) > 100 || mb_strlen($comment) > 2000) {
            echo json_encode(['success' => false, 'error' => 'Input too long.']);
            exit;
        }

        $stmt = $db->prepare("INSERT INTO comments (name, comment) VALUES (?, ?)");
        $stmt->execute([$name, $comment]);
        echo json_encode(['success' => true]);

    } else {
        // List comments
        $comments = $db->query("SELECT name, comment, created_at FROM comments ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'comments' => $comments]);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
