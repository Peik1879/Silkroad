<?php
/**
 * POST - Neue Tour erstellen
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

// Validierung
$name = trim($data['name'] ?? '');
$start_date = $data['start_date'] ?? null;
$end_date = $data['end_date'] ?? null;
$price_per_person = (float)($data['price_per_person'] ?? 0);
$max_participants = (int)($data['max_participants'] ?? 10);

if (empty($name) || empty($start_date) || empty($end_date) || $price_per_person <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Erforderliche Felder sind leer oder ungültig']);
    exit;
}

if ($max_participants < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Kapazität muss mindestens 1 sein']);
    exit;
}

try {
    $sql = 'INSERT INTO tours (name, start_date, end_date, price_per_person, max_participants, current_participants) 
            VALUES (?, ?, ?, ?, ?, 0)';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $name, $start_date, $end_date, $price_per_person, $max_participants
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Tour erstellt'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Datenbankfehler: ' . $e->getMessage()]);
}
