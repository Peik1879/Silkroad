<?php
/**
 * Test-Script zur Fehlerdiagnose
 * Überprüfe: DB-Verbindung, respond()-Funktion, Mailer
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'test' => 'START',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION
], JSON_PRETTY_PRINT);

echo "\n\n";

// 1. Test: db.php laden
try {
    require_once __DIR__ . '/db.php';
    echo json_encode([
        'result' => 'db.php geladen: SUCCESS',
        'DB_HOST' => DB_HOST,
        'DB_NAME' => DB_NAME
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'result' => 'db.php laden: FAILED',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
    exit;
}

echo "\n\n";

// 2. Test: PDO Verbindung
try {
    $test = $pdo->query('SELECT 1');
    echo json_encode([
        'result' => 'Datenbankverbindung: SUCCESS'
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'result' => 'Datenbankverbindung: FAILED',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
    exit;
}

echo "\n\n";

// 3. Test: respond() Funktion
try {
    // Prufe ob function existiert
    if (!function_exists('respond')) {
        throw new Exception('respond() Funktion existiert nicht!');
    }
    echo json_encode([
        'result' => 'respond()-Funktion: SUCCESS'
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'result' => 'respond()-Funktion: FAILED',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
    exit;
}

echo "\n\n";

// 4. Test: mailer.php laden
try {
    require_once __DIR__ . '/mailer.php';
    if (!function_exists('sendEmail')) {
        throw new Exception('sendEmail() Funktion existiert nicht!');
    }
    echo json_encode([
        'result' => 'mailer.php geladen: SUCCESS'
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'result' => 'mailer.php geladen: FAILED',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
    exit;
}

echo "\n\n";

// 5. Test: Tabellen existieren
try {
    $tours = $pdo->query('SELECT COUNT(*) FROM tours')->fetchColumn();
    $requests = $pdo->query('SELECT COUNT(*) FROM tour_requests')->fetchColumn();
    echo json_encode([
        'result' => 'Tabellen vorhanden: SUCCESS',
        'tours_count' => $tours,
        'requests_count' => $requests
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'result' => 'Tabellen: FAILED',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
    exit;
}

echo "\n\n";

http_response_code(200);
echo json_encode([
    'result' => 'ALLE TESTS ERFOLGREICH ✅',
    'next_step' => 'Die Website sollte jetzt funktionieren!'
], JSON_PRETTY_PRINT);
?>
