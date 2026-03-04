<?php
/**
 * contact_submit.php - Empfängt Kontaktformular und sendet Email an ADMIN
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();
header('Content-Type: application/json; charset=utf-8');

// DB und Mailer früh laden (wird von respond() benötigt)
try {
    if (!file_exists(__DIR__ . '/db.php')) {
        http_response_code(500);
        die(json_encode(['error' => 'db.php not found']));
    }
    require_once __DIR__ . '/db.php';

    if (!file_exists(__DIR__ . '/mailer.php')) {
        http_response_code(500);
        die(json_encode(['error' => 'mailer.php not found']));
    }
    require_once __DIR__ . '/mailer.php';
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Initialisierungsfehler: ' . $e->getMessage()]));
}

// Nur POST erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['error' => 'Method not allowed']);
}

// JSON Input lesen
$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    respond(400, ['error' => 'Invalid JSON']);
}

// Validierung
$email = trim($data['email'] ?? '');
$name = trim($data['name'] ?? '');
$message = trim($data['message'] ?? '');
$tour = trim($data['tour'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(400, ['error' => 'Gültige E-Mail ist erforderlich']);
}
if (empty($message)) {
    respond(400, ['error' => 'Nachricht ist erforderlich']);
}

// Email an Admin vorbereiten
$subject = 'Kontaktanfrage: ' . ($name ? $name : $email);
$body = "Neue Kontaktanfrage:\n\n" .
        "Name: " . ($name ?: '—') . "\n" .
        "Email: $email\n" .
        ($tour ? "Interesse an Tour: $tour\n" : "") .
        "\nNachricht:\n$message\n\n" .
        "Zeitstempel: " . date('d.m.Y H:i:s') . "\n" .
        "---\nDiese Email wurde automatisch generiert.";

try {
    // an Admin senden; Reply-To = Absender
    sendEmail(ADMIN_EMAIL, $subject, $body, false, $email);
    respond(200, ['ok' => true]);
} catch (Exception $e) {
    // Fehler bereits in smtp_errors.log geloggt durch mailer.php
    respond(500, ['error' => 'Email-Versand fehlgeschlagen']);
}
