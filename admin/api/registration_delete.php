<?php
/**
 * POST - Anmeldung löschen
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
    // Hole Daten der Anmeldung (für Teilnehmerzähler)
    $check = $pdo->prepare('SELECT id, tour, adults, children, toddlers FROM tour_requests WHERE id = ?');
    $check->execute([$id]);
    $registration = $check->fetch();
    
    if (!$registration) {
        http_response_code(404);
        echo json_encode(['error' => 'Anmeldung nicht gefunden']);
        exit;
    }
    
    // Aktualisiere Teilnehmerzähler in tours Tabelle
    try {
        $total_persons = $registration['adults'] + $registration['children'] + $registration['toddlers'];
        $tour = $registration['tour'];
        
        // Parse Datum aus tour String (Format: "04.03.2026 - 15.03.2026")
        if (preg_match('/(\d{2})\.(\d{2})\.(\d{4})\s*-\s*(\d{2})\.(\d{2})\.(\d{4})/', $tour, $matches)) {
            $start_date = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
            $end_date = $matches[6] . '-' . $matches[5] . '-' . $matches[4];
            
            $update_stmt = $pdo->prepare('
                UPDATE tours 
                SET current_participants = GREATEST(0, current_participants - ?)
                WHERE start_date = ? AND end_date = ?
                LIMIT 1
            ');
            $update_stmt->execute([$total_persons, $start_date, $end_date]);
        }
    } catch (Exception $e) {
        error_log("Fehler beim Aktualisieren des Teilnehmerzählers: " . $e->getMessage());
    }
    
    // Delete Anmeldung
    $stmt = $pdo->prepare('DELETE FROM tour_requests WHERE id = ?');
    $stmt->execute([$id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Anmeldung gelöscht'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Datenbankfehler: ' . $e->getMessage()]);
}
