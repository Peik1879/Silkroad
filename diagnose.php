<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== SILKROAD DB DIAGNOSE ===\n\n";

// Test 1: db.php laden
echo "1. Lade db.php...\n";
try {
    require_once __DIR__ . '/silkroad_db/db.php';
    echo "   ✅ db.php geladen\n";
    echo "   DB_HOST: " . DB_HOST . "\n";
    echo "   DB_NAME: " . DB_NAME . "\n";
} catch (Exception $e) {
    echo "   ❌ FEHLER: " . $e->getMessage() . "\n";
    exit;
}

echo "\n2. Teste Datenbankverbindung...\n";
try {
    $pdo->query('SELECT 1');
    echo "   ✅ Verbindung erfolgreich!\n";
} catch (PDOException $e) {
    echo "   ❌ PDO FEHLER:\n";
    echo "      Message: " . $e->getMessage() . "\n";
    echo "      Code: " . $e->getCode() . "\n";
    exit;
}

echo "\n3. Teste Tabellen...\n";
try {
    $tours = $pdo->query('SELECT COUNT(*) FROM tours')->fetchColumn();
    $requests = $pdo->query('SELECT COUNT(*) FROM tour_requests')->fetchColumn();
    echo "   ✅ Tours: $tours\n";
    echo "   ✅ Requests: $requests\n";
} catch (Exception $e) {
    echo "   ❌ Tabellen FEHLER: " . $e->getMessage() . "\n";
    exit;
}

echo "\n4. Teste respond() Funktion...\n";
if (function_exists('respond')) {
    echo "   ✅ respond() vorhanden\n";
} else {
    echo "   ❌ respond() NICHT vorhanden!\n";
    exit;
}

echo "\n5. Teste mailer.php...\n";
try {
    require_once __DIR__ . '/silkroad_db/mailer.php';
    if (function_exists('sendEmail')) {
        echo "   ✅ sendEmail() vorhanden\n";
    } else {
        echo "   ❌ sendEmail() NICHT vorhanden!\n";
    }
} catch (Exception $e) {
    echo "   ❌ FEHLER: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ ALLES OK - Website sollte funktionieren!\n";
echo str_repeat("=", 50) . "\n";
?>
