<?php
/**
 * Email-Versand mit PHPMailer (SMTP)
 * Ersetzt PHP's mail() Funktion für zuverlässigen Versand
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';
require __DIR__ . '/PHPMailer/Exception.php';

/**
 * Sendet eine Email via SMTP (PHPMailer)
 * 
 * @param string $to Empfänger-Email
 * @param string $subject Betreff
 * @param string $body Email-Inhalt (HTML oder Text)
 * @param bool $isHTML Ist der Body HTML? (default: true)
 * @param string $replyTo Optional: Reply-To Adresse
 * @return bool Erfolg
 */
function sendEmail($to, $subject, $body, $isHTML = true, $replyTo = null) {
    // Im DEV_MODE nur loggen
    if (defined('DEV_MODE') && DEV_MODE) {
        $logEntry = "=== EMAIL ===\n";
        $logEntry .= "To: $to\n";
        $logEntry .= "Subject: $subject\n";
        if ($replyTo) $logEntry .= "Reply-To: $replyTo\n";
        $logEntry .= "IsHTML: " . ($isHTML ? 'ja' : 'nein') . "\n";
        $logEntry .= "Body:\n$body\n\n";
        $logEntry .= "========================================\n\n";
        
        file_put_contents(__DIR__ . '/mail_debug.log', $logEntry, FILE_APPEND);
        return true;
    }    cd "C:\Users\peik_\Desktop\Papierkrieg\Aaron Köhler\Arbeit\Silkroad"
    php -S 127.0.0.1:8000 -t Silkroad
    
    // SMTP-Versand mit PHPMailer
    $mail = new PHPMailer(true);
    
    try {
        // SMTP-Konfiguration
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS für Port 587
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        
        // WICHTIG: Timeouts setzen (verhindert Hängen bei Netzwerkfehlern)
        $mail->Timeout = 10;           // Genereller Timeout
        $mail->SMTPDebug = 0;          // Debug: 0=off, 1=client, 2=server+client
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ];
        
        // Absender
        $mail->setFrom(SMTP_USERNAME, SENDER_NAME);
        
        // Empfänger
        $mail->addAddress($to);
        
        // Reply-To (optional)
        if ($replyTo) {
            $mail->addReplyTo($replyTo);
        }
        
        // Inhalt
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        // Plain-Text Alternative (falls HTML)
        if ($isHTML) {
            $mail->AltBody = strip_tags($body);
        }
        
        // Senden
        $mail->send();
        error_log("✅ Email erfolgreich gesendet an: $to");
        return true;
        
    } catch (Exception $e) {
        $errorDetails = "❌ Email-Fehler an $to: {$mail->ErrorInfo}";
        error_log($errorDetails);
        
        // Zusätzlich in separate Log-Datei schreiben
        $logFile = __DIR__ . '/smtp_errors.log';
        $logEntry = date('Y-m-d H:i:s') . " - $errorDetails\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
        
        throw $e; // Exception weiterwerfen damit submit.php es behandeln kann
    }
}

