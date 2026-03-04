<?php
/**
 * GET - Einzelne Tour abrufen
 */
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../silkroad_db/db.php';

header('Content-Type: application/json');

// Session-Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht authentifiziert']);
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ungültige ID']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT * FROM tours WHERE id = ?');
    $stmt->execute([$id]);
    $tour = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tour) {
        http_response_code(404);
        echo json_encode(['error' => 'Tour nicht gefunden']);
        exit;
    }
    
    echo json_encode($tour);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Datenbankfehler: ' . $e->getMessage()]);
}
