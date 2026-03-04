<?php
/**
 * POST - Anmeldung aktualisieren
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

$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Nur POST erlaubt']);
    exit;
}

// Validierung
$id = $data['id'] ?? null;
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$phone = trim($data['phone'] ?? '');
$tour = trim($data['tour'] ?? '');
$travel_date = $data['travel_date'] ?? null;
$adults = (int)($data['adults'] ?? 0);
$children = (int)($data['children'] ?? 0);
$toddlers = (int)($data['toddlers'] ?? 0);
$abflughafen = trim($data['abflughafen'] ?? '');
$message = trim($data['message'] ?? '');

if (!$id || !is_numeric($id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ungültige ID']);
    exit;
}

if (empty($name) || empty($email) || empty($phone) || empty($tour)) {
    http_response_code(400);
    echo json_encode(['error' => 'Erforderliche Felder sind leer']);
    exit;
}

if ($adults < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Mindestens 1 Erwachsener erforderlich']);
    exit;
}

try {
    // Prüfe ob Anmeldung existiert
    $check = $pdo->prepare('SELECT id FROM tour_requests WHERE id = ?');
    $check->execute([$id]);
    if (!$check->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Anmeldung nicht gefunden']);
        exit;
    }
    
    // Update
    $sql = 'UPDATE tour_requests SET 
            name = ?, 
            email = ?, 
            phone = ?, 
            tour = ?, 
            travel_date = ?, 
            adults = ?, 
            children = ?, 
            toddlers = ?, 
            abflughafen = ?, 
            message = ? 
            WHERE id = ?';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $name, $email, $phone, $tour, $travel_date, 
        $adults, $children, $toddlers, $abflughafen, $message, $id
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Anmeldung aktualisiert'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Datenbankfehler: ' . $e->getMessage()]);
}
