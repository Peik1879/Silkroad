<?php
/**
 * Admin Dashboard - Anmeldungen Übersicht
 */
session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../silkroad_db/db.php';

// Session-Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Session-Timeout prüfen
if (time() - $_SESSION['admin_login_time'] > SESSION_TIMEOUT) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Filter/Suche
$search = $_GET['search'] ?? '';
$filter_tour = $_GET['tour'] ?? '';
$order = $_GET['order'] ?? 'DESC';

// Query bauen
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

// Statistiken
$stats_stmt = $pdo->query('
    SELECT 
        COUNT(*) as total,
        SUM(adults) as total_adults,
        SUM(children) as total_children,
        SUM(toddlers) as total_toddlers,
        COUNT(DISTINCT tour) as unique_tours
    FROM tour_requests
');
$stats = $stats_stmt->fetch();

// Verfügbare Touren
$tours_stmt = $pdo->query('SELECT DISTINCT tour FROM tour_requests ORDER BY tour');
$tours = $tours_stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Silkroad Tour Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #8B4513 0%, #CD853F 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header h1 {
            font-size: 28px;
        }
        .header-actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-primary {
            background: white;
            color: #8B4513;
        }
        .btn-primary:hover {
            background: #f5f5f5;
        }
        .btn-danger {
            background: #c33;
            color: white;
        }
        .btn-danger:hover {
            background: #a22;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #8B4513;
        }
        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .filters form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .filters input,
        .filters select {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 4px;
            flex: 1;
            min-width: 200px;
        }
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #8B4513;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        tr:hover {
            background: #f9f9f9;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .empty-state h2 {
            margin-bottom: 10px;
        }
        a.email {
            color: #8B4513;
            text-decoration: none;
        }
        a.email:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>🌍 Silkroad Tour Admin</h1>
            <p>Anmeldungen Übersicht</p>
        </div>
        <div class="header-actions">
            <a href="export.php?<?= http_build_query($_GET) ?>" class="btn btn-primary">📥 CSV Export</a>
            <a href="logout.php" class="btn btn-danger">Ausloggen</a>
        </div>
    </div>
    
    <div class="stats">
        <div class="stat-card">
            <h3>Gesamt Anmeldungen</h3>
            <div class="number"><?= number_format($stats['total']) ?></div>
        </div>
        <div class="stat-card">
            <h3>Erwachsene</h3>
            <div class="number"><?= number_format($stats['total_adults']) ?></div>
        </div>
        <div class="stat-card">
            <h3>Kinder</h3>
            <div class="number"><?= number_format($stats['total_children']) ?></div>
        </div>
        <div class="stat-card">
            <h3>Kleinkinder</h3>
            <div class="number"><?= number_format($stats['total_toddlers']) ?></div>
        </div>
        <div class="stat-card">
            <h3>Verschiedene Touren</h3>
            <div class="number"><?= number_format($stats['unique_tours']) ?></div>
        </div>
    </div>
    
    <div class="filters">
        <form method="GET">
            <input type="text" name="search" placeholder="Suche: Name, Email, Telefon..." value="<?= htmlspecialchars($search) ?>">
            <select name="tour">
                <option value="">Alle Touren</option>
                <?php foreach ($tours as $tour): ?>
                    <option value="<?= htmlspecialchars($tour) ?>" <?= $filter_tour === $tour ? 'selected' : '' ?>>
                        <?= htmlspecialchars($tour) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="order">
                <option value="DESC" <?= $order === 'DESC' ? 'selected' : '' ?>>Neueste zuerst</option>
                <option value="ASC" <?= $order === 'ASC' ? 'selected' : '' ?>>Älteste zuerst</option>
            </select>
            <button type="submit" class="btn btn-primary">Filtern</button>
            <a href="dashboard.php" class="btn" style="background: #ddd; color: #333;">Reset</a>
        </form>
    </div>
    
    <div class="table-container">
        <?php if (count($requests) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Datum</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Telefon</th>
                        <th>Tour</th>
                        <th>Personen</th>
                        <th>Nachricht</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): ?>
                        <tr>
                            <td><strong>#<?= $req['id'] ?></strong></td>
                            <td><?= date('d.m.Y H:i', strtotime($req['created_at'])) ?></td>
                            <td><?= htmlspecialchars($req['name']) ?></td>
                            <td><a href="mailto:<?= htmlspecialchars($req['email']) ?>" class="email"><?= htmlspecialchars($req['email']) ?></a></td>
                            <td><?= htmlspecialchars($req['phone']) ?></td>
                            <td><span class="badge badge-success"><?= htmlspecialchars($req['tour']) ?></span></td>
                            <td>
                                <?= $req['adults'] ?> Erw. 
                                <?php if ($req['children'] > 0): ?> | <?= $req['children'] ?> Ki.<?php endif; ?>
                                <?php if ($req['toddlers'] > 0): ?> | <?= $req['toddlers'] ?> Kl.<?php endif; ?>
                            </td>
                            <td><?php 
                                $msg = $req['message'] ?? '';
                                echo htmlspecialchars(substr($msg, 0, 50));
                                echo strlen($msg) > 50 ? '...' : '';
                            ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <h2>Keine Anmeldungen gefunden</h2>
                <p>Es gibt noch keine Anmeldungen oder deine Filter haben keine Ergebnisse.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
