<?php
/**
 * Debug: Zeige echte PHP-Fehler von submit.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain; charset=utf-8');

echo "=== DEBUG submit.php ===\n\n";

// Simuliere das, was submit.php macht
echo "1. Versuche ob_start()...\n";
ob_start();
echo "   ✅ ob_start() OK\n\n";

echo "2. Setze Content-Type Header...\n";
header('Content-Type: application/json; charset=utf-8');
echo "   ✅ Header gesetzt\n\n";

echo "3. Versuche db.php zu laden...\n";
try {
    $dbpath = __DIR__ . '/silkroad_db/db.php';
    if (!file_exists($dbpath)) {
        echo "   ❌ FEHLER: $dbpath existiert nicht!\n";
        exit;
    }
    require_once $dbpath;
    echo "   ✅ db.php geladen\n\n";
} catch (Exception $e) {
    echo "   ❌ FEHLER beim Laden von db.php:\n";
    echo "      " . $e->getMessage() . "\n";
    exit;
}

echo "4. Teste PDO...\n";
try {
    $test = $pdo->query('SELECT 1');
    echo "   ✅ PDO funktioniert\n\n";
} catch (PDOException $e) {
    echo "   ❌ PDO FEHLER:\n";
    echo "      Message: " . $e->getMessage() . "\n";
    echo "      Code: " . $e->getCode() . "\n\n";
    exit;
}

echo "5. Versuche mailer.php zu laden...\n";
try {
    require_once __DIR__ . '/silkroad_db/mailer.php';
    echo "   ✅ mailer.php geladen\n\n";
} catch (Exception $e) {
    echo "   ❌ FEHLER beim Laden von mailer.php:\n";
    echo "      " . $e->getMessage() . "\n";
    exit;
}

echo "6. Teste respond() Funktion...\n";
if (function_exists('respond')) {
    echo "   ✅ respond() vorhanden\n\n";
} else {
    echo "   ❌ respond() NICHT VORHANDEN!\n\n";
    exit;
}

echo "✅ ALLES OK - submit.php sollte funktionieren!\n";
?>
