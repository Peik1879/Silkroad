<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== DB Connection Test ===\n\n";

// Test 1: Include db.php
try {
    require_once __DIR__ . '/db.php';
    echo "✅ db.php geladen\n";
    echo "   DB_HOST: " . DB_HOST . "\n";
    echo "   DB_NAME: " . DB_NAME . "\n";
    echo "   DB_USER: " . DB_USER . "\n";
} catch (Exception $e) {
    echo "❌ db.php geladen FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// Test 2: PDO Verbindung
try {
    $test = $pdo->query('SELECT 1');
    echo "✅ Datenbankverbindung erfolgreich\n";
} catch (PDOException $e) {
    echo "❌ PDO Fehler: " . $e->getMessage() . "\n";
    echo "   Code: " . $e->getCode() . "\n";
    exit(1);
}

echo "\n";

// Test 3: Tabellen
try {
    $count_tours = $pdo->query('SELECT COUNT(*) FROM tours')->fetchColumn();
    $count_requests = $pdo->query('SELECT COUNT(*) FROM tour_requests')->fetchColumn();
    echo "✅ Tabellen vorhanden\n";
    echo "   Tours: " . $count_tours . "\n";
    echo "   Requests: " . $count_requests . "\n";
} catch (Exception $e) {
    echo "❌ Tabellen Fehler: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ ALLES OK!\n";
?>
