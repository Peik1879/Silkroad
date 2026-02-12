<?php
/**
 * Admin Login
 */
session_start();

require_once __DIR__ . '/config.php';

// Bereits eingeloggt? → Dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// Login-Verarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    
    // Rate-Limiting
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_attempts_time'] = time();
    }
    
    // Reset nach 1 Stunde
    if (time() - $_SESSION['login_attempts_time'] > 3600) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_attempts_time'] = time();
    }
    
    // Zu viele Versuche?
    if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $error = 'Zu viele fehlgeschlagene Versuche. Bitte warte 1 Stunde.';
    } else {
        // Passwort prüfen
        if (password_verify($password, ADMIN_PASSWORD_HASH)) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_login_time'] = time();
            $_SESSION['login_attempts'] = 0;
            header('Location: dashboard.php');
            exit;
        } else {
            $_SESSION['login_attempts']++;
            $error = 'Falsches Passwort. Versuch ' . $_SESSION['login_attempts'] . ' von ' . MAX_LOGIN_ATTEMPTS;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Silkroad Tour</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #8B4513 0%, #CD853F 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            color: #8B4513;
            font-size: 28px;
            margin-bottom: 5px;
        }
        .logo p {
            color: #666;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input[type="password"]:focus {
            outline: none;
            border-color: #8B4513;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: #8B4513;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #6b3410;
        }
        .error {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
        }
        .info {
            margin-top: 20px;
            padding: 12px;
            background: #f5f5f5;
            border-radius: 4px;
            font-size: 13px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🔐 Admin Login</h1>
            <p>Silkroad Tour Backend</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="password">Passwort</label>
                <input type="password" id="password" name="password" required autofocus>
            </div>
            
            <button type="submit" class="btn">Einloggen</button>
        </form>
        
        <div class="info">
            <strong>Standard-Passwort:</strong> admin123<br>
            <small>Bitte in config.php ändern!</small>
        </div>
    </div>
</body>
</html>
