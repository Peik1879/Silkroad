<?php
/**
 * contact_submit.php - Empfängt Kontaktformular und sendet Email an ADMIN
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();
header('Content-Type: application/json; charset=utf-8');

function respond(int $code, array $payload): void {
    http_response_code($code);
    if (ob_get_length() !== false) {
        ob_clean();
    }
    echo json_encode($payload);
    exit;
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

// Konfiguration laden
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

// Validierung
$email = trim($data['email'] ?? '');
$name = trim($data['name'] ?? '');
$message = trim($data['message'] ?? '');

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
        "Email: $email\n\n" .
        "Nachricht:\n$message\n\n" .
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
