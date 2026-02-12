<?php
/**
 * submit.php - Speichert Formulardaten in MySQL
 * Empfängt JSON via POST und speichert in Datenbank
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Ausgabepuffer aktivieren, um "headers already sent" sicher zu vermeiden
ob_start();
// Content-Type früh setzen; Response-Code wird je nach Ergebnis gesetzt
header('Content-Type: application/json; charset=utf-8');

// Einheitliche JSON-Antwortfunktion
function respond(int $code, array $payload): void {
    http_response_code($code);
    // sicherstellen, dass keine vorherige Ausgabe im Buffer ist
    if (ob_get_length() !== false) {
        ob_clean();
    }
    echo json_encode($payload);
    exit;
}

// Log-Datei für Debugging
$logfile = __DIR__ . '/error.log';
ini_set('error_log', $logfile);

// Error handler: alle PHP-Fehler als JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Nur einmal antworten
    static $hasResponded = false;
    if ($hasResponded) return;
    $hasResponded = true;
    
    // Nur für schwere Fehler
    if ($errno === E_WARNING || $errno === E_NOTICE) return false;
    
    respond(500, ['error' => "PHP Error [$errno]: $errstr (in $errfile:$errline)"]);
});

// Exception handler
set_exception_handler(function($exception) {
    respond(500, ['error' => 'Exception: ' . $exception->getMessage()]);
});

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

// DB-Verbindung laden
if (!file_exists(__DIR__ . '/db.php')) {
    respond(500, ['error' => 'db.php not found']);
}
require_once __DIR__ . '/db.php';

// Mailer laden
if (!file_exists(__DIR__ . '/mailer.php')) {
    respond(500, ['error' => 'mailer.php not found']);
}
require_once __DIR__ . '/mailer.php';

// ===== INPUT VALIDIEREN =====
$errors = [];

// Name
$name = trim($data['name'] ?? '');
if (empty($name)) {
    $errors[] = 'Name ist erforderlich';
}

// Email
$email = trim($data['email'] ?? '');
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Gültige E-Mail ist erforderlich';
}

// Telefon
$phone = trim($data['phone'] ?? '');
if (empty($phone)) {
    $errors[] = 'Telefonnummer ist erforderlich';
}

// Tour (Reisedatum-String)
$tour = trim($data['tour'] ?? '');
if (empty($tour)) {
    $errors[] = 'Reisedatum ist erforderlich';
}

// Travel Date - konvertiere erstes Datum aus dem String
$travel_date = '';
if (!empty($tour)) {
    // Format: "15.03.2026-27.03.2026" -> "2026-03-15"
    if (preg_match('/(\d{2})\.(\d{2})\.(\d{4})/', $tour, $matches)) {
        $travel_date = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
    } else {
        $travel_date = date('Y-m-d'); // Fallback
    }
}

// Personen
$adults = intval($data['adults'] ?? 1);
$children = intval($data['children'] ?? 0);
$toddlers = intval($data['toddlers'] ?? 0);
$message = trim($data['message'] ?? '');

if ($adults < 1) {
    $errors[] = 'Mindestens 1 Erwachsener erforderlich';
}

// Bei Validierungsfehlern
if (!empty($errors)) {
    respond(400, ['error' => implode(', ', $errors)]);
}

// ===== IN DATENBANK SPEICHERN =====
try {
    $stmt = $pdo->prepare('
        INSERT INTO tour_requests 
        (name, email, phone, tour, travel_date, adults, children, toddlers, message)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    
    $stmt->execute([
        $name,
        $email,
        $phone,
        $tour,
        $travel_date,
        $adults,
        $children,
        $toddlers,
        $message ?: null
    ]);
    
    
    $booking_id = $pdo->lastInsertId();
    
    // ===== EMAILS VERSENDEN =====
    
    // 1. EMAIL AN ADMIN
    $admin_subject = "Neue Anmeldung: $name für $tour";
    $admin_body = "
Eine neue Anmeldung ist eingegangen:

PERSÖNLICHE DATEN:
- Name: $name
- Email: $email
- Telefon: $phone

REISEDATEN:
- Reisetermin: $tour
- Reisedatum: $travel_date
- Erwachsene: $adults
- Kinder: $children
- Kleinkinder: $toddlers

NACHRICHT:
$message

Booking-ID: $booking_id
Zeitstempel: " . date('d.m.Y H:i:s') . "

---
Diese Email wurde automatisch generiert.
    ";
    
    // Admin-Email versenden (mit Fehlertoleranz)
    try {
        sendEmail(ADMIN_EMAIL, $admin_subject, $admin_body, false, $email);
    } catch (Exception $e) {
        $errorMsg = "Admin-Email Fehler: " . $e->getMessage();
        error_log($errorMsg);
        file_put_contents(__DIR__ . '/smtp_errors.log', date('Y-m-d H:i:s') . " - $errorMsg\n", FILE_APPEND);
        // Nicht abbrechen - Buchung existiert bereits, nur Email-Fehler
    }
    
    // 2. BESTÄTIGUNGSEMAIL AN GAST (HTML-formatiert)
    $guest_subject = "Anmeldungsbestätigung: Silkroad Usbekistan Tour";
    
    $guest_body = '
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            line-height: 1.6;
            color: #333;
        }
        .email-wrapper {
            background-color: #fff;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #8B4513 0%, #CD853F 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
            border-bottom: 4px solid #FFD59A;
        }
        .header .logo {
            max-width: 180px;
            height: auto;
            margin: 0 auto 20px;
            display: block;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }
        .header p {
            font-size: 14px;
            opacity: 0.95;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .booking-box {
            background-color: #f9f9f9;
            border-left: 4px solid #FFD59A;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .booking-box h3 {
            color: #8B4513;
            font-size: 16px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .booking-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .booking-row:last-child {
            border-bottom: none;
        }
        .booking-label {
            font-weight: 600;
            color: #555;
        }
        .booking-value {
            color: #333;
            text-align: right;
        }
        .info-text {
            color: #666;
            font-size: 15px;
            margin: 20px 0;
            line-height: 1.8;
        }
        .contact-box {
            background-color: #faf5ef;
            padding: 20px;
            border-radius: 4px;
            margin: 25px 0;
            border: 1px solid #FFD59A;
        }
        .contact-box h3 {
            color: #8B4513;
            font-size: 16px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .contact-method {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            font-size: 14px;
        }
        .contact-method:last-child {
            margin-bottom: 0;
        }
        .contact-icon {
            display: inline-block;
            width: 30px;
            text-align: center;
            font-size: 18px;
            margin-right: 12px;
        }
        .contact-method a {
            color: #8B4513;
            text-decoration: none;
            font-weight: 600;
        }
        .contact-method a:hover {
            text-decoration: underline;
        }
        .footer {
            background-color: #f5f5f5;
            padding: 30px 30px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 12px;
            color: #999;
        }
        .footer p {
            margin-bottom: 5px;
        }
        .signature {
            margin-top: 25px;
            color: #555;
            font-style: italic;
        }
        .cta-button {
            display: inline-block;
            background-color: #8B4513;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            margin: 20px 0;
        }
        .cta-button:hover {
            background-color: #6b3410;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="header">
            <img src="' . WEBSITE_URL . '/assets/images/logo.png" alt="Silkroad Tour Logo" class="logo">
            <h1>🌍 Silkroad Tour</h1>
            <p>Usbekistan – Einmalige Seidenstraße Erlebnisse</p>
        </div>
        
        <div class="content">
            <p class="greeting">Hallo ' . htmlspecialchars($name) . ',</p>
            
            <p class="info-text">
                vielen Dank für Ihre Anmeldung zur <strong>Silkroad Usbekistan Tour</strong>! 
                Wir freuen uns riesig auf Ihre Reise entlang der historischen Seidenstraße.
            </p>
            
            <div class="booking-box">
                <h3>✓ Ihre Buchungsdaten</h3>
                <div class="booking-row">
                    <span class="booking-label">Buchungs-ID:</span>
                    <span class="booking-value"><strong>#' . htmlspecialchars($booking_id) . '</strong></span>
                </div>
                <div class="booking-row">
                    <span class="booking-label">Reisetermin:</span>
                    <span class="booking-value">' . htmlspecialchars($tour) . '</span>
                </div>
                <div class="booking-row">
                    <span class="booking-label">Reisegruppe:</span>
                    <span class="booking-value">' . ($adults + $children + $toddlers) . ' Personen</span>
                </div>
                <div class="booking-row">
                    <span class="booking-label">Zusammensetzung:</span>
                    <span class="booking-value">' . $adults . ' Erw. · ' . $children . ' Kinder · ' . $toddlers . ' Kleinst.</span>
                </div>
            </div>
            
            <p class="info-text">
                <strong>Was kommt als nächstes?</strong><br>
                Unser Team wird sich in Kürze mit Ihnen in Verbindung setzen, um alle Details zur Reise zu besprechen, 
                Ihre Fragen zu beantworten und die endgültige Buchung abzuschließen.
            </p>
            
            <div class="contact-box">
                <h3>Noch Fragen?</h3>
                <div class="contact-method">
                    <span class="contact-icon">💬</span>
                    <span><a href="https://wa.me/' . preg_replace('/[^0-9]/', '', WHATSAPP_NUMBER) . '?text=Hallo%2C%20ich%20habe%20eine%20Frage%20zu%20meiner%20Buchung%20%23' . htmlspecialchars($booking_id) . '">WhatsApp Nachricht schreiben</a> (schnellste Antwort!)</span>
                </div>
                <div class="contact-method">
                    <span class="contact-icon">📧</span>
                    <span><a href="mailto:' . htmlspecialchars(ADMIN_EMAIL) . '">Email schreiben</a></span>
                </div>
                <div class="contact-method">
                    <span class="contact-icon">🌐</span>
                    <span><a href="' . htmlspecialchars(WEBSITE_URL) . '/kontakt.html">Kontaktformular auf unserer Website</a></span>
                </div>
            </div>
            
            <p class="info-text">
                Wir freuen uns auf Ihre Reise und versprechen Ihnen ein unvergessliches Abenteuer 
                auf den Spuren der berühmten Seidenstraße!
            </p>
            
            <p class="signature">
                Herzliche Grüße<br>
                <strong>Ihr Silkroad Tour Team</strong>
            </p>
        </div>
        
        <div class="footer">
            <p><strong>Silkroad Tour</strong></p>
            <p>Email: ' . htmlspecialchars(ADMIN_EMAIL) . ' | WhatsApp: ' . htmlspecialchars(WHATSAPP_NUMBER) . '</p>
            <p style="margin-top: 15px; opacity: 0.7;">Diese Email wurde automatisch generiert. Bitte antworten Sie auf diese Email mit Ihren Fragen.</p>
        </div>
    </div>
</body>
</html>
    ';
    
    // Gast-Bestätigung versenden (mit Fehlertoleranz)
    try {
        sendEmail($email, $guest_subject, $guest_body, true);
    } catch (Exception $e) {
        error_log("Gast-Email Fehler: " . $e->getMessage());
        // Nicht abbrechen - Buchung existiert bereits
    }
    
    // Erfolg
    respond(200, [
        'success' => true,
        'message' => 'Anmeldung erfolgreich gespeichert. Bestätigungsemail wurde verschickt.',
        'id' => $booking_id
    ]);
    
} catch (PDOException $e) {
    error_log('DB-Fehler: ' . $e->getMessage());
    respond(500, ['error' => 'Datenbankfehler: ' . $e->getMessage()]);
}

