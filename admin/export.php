<?php
/**
 * CSV Export
 */
// Suppress deprecation notices in output (keep logs server-side)
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 0);

session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../silkroad_db/db.php';

// Session-Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Filter aus Query-String übernehmen
$search = $_GET['search'] ?? '';
$filter_tour = $_GET['tour'] ?? '';
$order = $_GET['order'] ?? 'DESC';

// Query bauen (gleiche Logik wie dashboard.php)
$sql = 'SELECT * FROM tour_requests WHERE 1=1';
$params = [];

if ($search) {
    $sql .= ' AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)';
    $searchParam = '%' . $search . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($filter_tour) {
    $sql .= ' AND tour = ?';
    $params[] = $filter_tour;
}

$sql .= ' ORDER BY created_at ' . ($order === 'ASC' ? 'ASC' : 'DESC');

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// CSV-Header setzen
$filename = 'silkroad_anmeldungen_' . date('Y-m-d_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Output-Stream öffnen
$output = fopen('php://output', 'w');

// BOM für Excel UTF-8 Unterstützung
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Spaltennamen
fputcsv($output, [
    'ID',
    'Erstellt am',
    'Name',
    'Email',
    'Telefon',
    'Tour',
    'Reisedatum',
    'Erwachsene',
    'Kinder',
    'Kleinkinder',
    'Nachricht'
], ';', '"', '\\');

// Datenzeilen
foreach ($requests as $req) {
    fputcsv($output, [
        $req['id'],
        date('d.m.Y H:i:s', strtotime($req['created_at'])),
        $req['name'],
        $req['email'],
        $req['phone'],
        $req['tour'],
        $req['travel_date'],
        $req['adults'],
        $req['children'],
        $req['toddlers'],
        $req['message']
    ], ';', '"', '\\');
}

fclose($output);
exit;
