<?php
/**
 * Database Reset Script
 * Löscht alle Test-Daten aus den Tabellen
 * ACHTUNG: Diese Aktion kann nicht rückgängig gemacht werden!
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../silkroad_db/db.php';

// Nur für eingeloggte Admins
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('Zugriff verweigert');
}

// Sicherheitscheck: Nur bei POST mit Bestätigung ausführen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $_POST['confirm'] === 'RESET') {
    try {
        // Beginne Transaktion
        $pdo->beginTransaction();
        
        // Lösche alle Anmeldungen
        $stmt1 = $pdo->query('DELETE FROM tour_requests');
        $deleted_requests = $stmt1->rowCount();
        
        // Setze Auto-Increment zurück
        $pdo->query('ALTER TABLE tour_requests AUTO_INCREMENT = 1');
        
        // Lösche alle Touren
        $stmt2 = $pdo->query('DELETE FROM tours');
        $deleted_tours = $stmt2->rowCount();
        
        // Setze Auto-Increment zurück
        $pdo->query('ALTER TABLE tours AUTO_INCREMENT = 1');
        
        // Commit Transaktion
        $pdo->commit();
        
        $success = true;
        $message = "Erfolgreich gelöscht: $deleted_requests Anmeldungen und $deleted_tours Touren";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $success = false;
        $message = "Fehler: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datenbank zurücksetzen</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            max-width: 600px;
            width: 100%;
        }
        
        h1 {
            color: #dc3545;
            margin-bottom: 20px;
            font-size: 28px;
        }
        
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .warning h3 {
            color: #856404;
            margin-bottom: 10px;
        }
        
        .warning p {
            color: #856404;
            line-height: 1.6;
        }
        
        .info {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .info p {
            color: #0c5460;
            line-height: 1.6;
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #333;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            font-family: monospace;
        }
        
        .buttons {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            font-size: 16px;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #bb2d3b;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 600;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .stats {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .stats p {
            margin: 5px 0;
            color: #495057;
        }
        
        .stats strong {
            color: #212529;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚠️ Datenbank zurücksetzen</h1>
        
        <?php if (isset($success)): ?>
            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✓ <?= htmlspecialchars($message) ?>
                </div>
                <div class="buttons">
                    <a href="dashboard.php" class="btn btn-secondary">Zurück zum Dashboard</a>
                </div>
            <?php else: ?>
                <div class="alert alert-error">
                    ✕ <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="warning">
                <h3>⚠️ ACHTUNG!</h3>
                <p>Diese Aktion löscht <strong>alle</strong> Daten aus folgenden Tabellen:</p>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Alle Anmeldungen (tour_requests)</li>
                    <li>Alle Touren (tours)</li>
                </ul>
                <p><strong>Diese Aktion kann nicht rückgängig gemacht werden!</strong></p>
            </div>
            
            <?php
            // Aktuelle Statistik anzeigen
            try {
                $count_requests = $pdo->query('SELECT COUNT(*) FROM tour_requests')->fetchColumn();
                $count_tours = $pdo->query('SELECT COUNT(*) FROM tours')->fetchColumn();
            ?>
                <div class="stats">
                    <strong>Aktuelle Daten:</strong>
                    <p>📋 Anmeldungen: <?= $count_requests ?></p>
                    <p>📅 Touren: <?= $count_tours ?></p>
                </div>
            <?php } catch (Exception $e) { } ?>
            
            <div class="info">
                <p>Zum Bestätigen geben Sie bitte <strong>RESET</strong> ein (in Großbuchstaben):</p>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label for="confirm">Bestätigung:</label>
                    <input type="text" id="confirm" name="confirm" placeholder="RESET eingeben" autocomplete="off" required>
                </div>
                
                <div class="buttons">
                    <a href="dashboard.php" class="btn btn-secondary">Abbrechen</a>
                    <button type="submit" class="btn btn-danger">Datenbank leeren</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
