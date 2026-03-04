<?php
echo "Test 1: Testen ob das Skript überhaupt lädt...\n\n";

echo "Test 2: Versuche db.php zu laden...\n";

$dbfile = __DIR__ . '/silkroad_db/db.php';
if (file_exists($dbfile)) {
    echo "Datei existiert: $dbfile\n";
    include $dbfile;
    echo "Erfolgreich geladen!\n";
    echo "DB_HOST: " . DB_HOST . "\n";
} else {
    echo "Datei NICHT gefunden!\n";
}
?>
