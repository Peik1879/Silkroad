<?php
/**
 * API Endpoint: Get Available Tours
 * Returns list of available tours from database
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../silkroad_db/db.php';

try {
    // Get all tours from database, ordered by start date
    $stmt = $pdo->query('
        SELECT 
            id,
            name,
            start_date,
            end_date,
            price_per_person,
            max_participants,
            current_participants
        FROM tours 
        WHERE start_date >= CURDATE()
        ORDER BY start_date ASC
    ');
    
    $tours = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'tours' => $tours,
        'count' => count($tours)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Fehler beim Laden der Touren: ' . $e->getMessage()
    ]);
}
