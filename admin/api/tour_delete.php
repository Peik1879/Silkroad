<?php
/**
 * POST - Tour löschen
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Nur POST erlaubt']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;

if (!$id || !is_numeric($id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ungültige ID']);
    exit;
}

try {
    // Prüfe ob Tour existiert
    $check = $pdo->prepare('SELECT id FROM tours WHERE id = ?');
    $check->execute([$id]);
    if (!$check->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Tour nicht gefunden']);
        exit;
    }
    
    // Delete
    $stmt = $pdo->prepare('DELETE FROM tours WHERE id = ?');
    $stmt->execute([$id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Tour gelöscht'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Datenbankfehler: ' . $e->getMessage()]);
}
