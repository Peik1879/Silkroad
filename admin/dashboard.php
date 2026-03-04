<?php
/**
 * Admin Dashboard - Hub System
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

$tab = $_GET['tab'] ?? 'anmeldungen';

// ============================================
// DATEN FÜR ANMELDUNGEN
// ============================================
$search = $_GET['search'] ?? '';
$filter_tour = $_GET['tour'] ?? '';
$order = $_GET['order'] ?? 'DESC';

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

// ============================================
// DATEN FÜR TERMINPLANUNG
// ============================================
$tours_list = [];
try {
    $tours_query = $pdo->query('SELECT * FROM tours ORDER BY start_date DESC');
    $tours_list = $tours_query->fetchAll();
} catch (Exception $e) {
    // Tours-Tabelle existiert noch nicht
}

// Lade verfügbare Tour-Namen aus der Datenbank (nur unique Namen)
$available_tours = [];
try {
    $tours_names_query = $pdo->query('SELECT DISTINCT name FROM tours ORDER BY name ASC');
    $available_tours = $tours_names_query->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    // Keine Fallback-Tourdaten: Bei Fehler/fehlender Tabelle bleibt die Liste leer
    $available_tours = [];
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Hub - Silkroad Tour</title>
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
        }
        
        .header {
            background: linear-gradient(135deg, #8B4513 0%, #CD853F 100%);
            color: white;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 32px;
            font-weight: 700;
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .btn-primary {
            background: white;
            color: #8B4513;
        }
        
        .btn-primary:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #bb2d3b;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #229954;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* NAV TABS */
        .nav-tabs {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .nav-tab {
            background: white;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 3px solid transparent;
            text-decoration: none;
            color: #333;
        }
        
        .nav-tab:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
            border-color: #8B4513;
        }
        
        .nav-tab.active {
            background: linear-gradient(135deg, #8B4513 0%, #CD853F 100%);
            color: white;
            border-color: #8B4513;
        }
        
        .nav-tab-icon {
            font-size: 40px;
            margin-bottom: 10px;
            display: block;
        }
        
        .nav-tab-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .nav-tab-desc {
            font-size: 12px;
            opacity: 0.7;
        }
        
        .nav-tab.active .nav-tab-desc {
            opacity: 0.9;
        }
        
        /* TAB CONTENT */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid #8B4513;
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 12px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-card .number {
            font-size: 28px;
            font-weight: 700;
            color: #8B4513;
        }
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
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
            border-radius: 6px;
            flex: 1;
            min-width: 200px;
            font-size: 14px;
        }
        
        .filters input:focus,
        .filters select:focus {
            outline: none;
            border-color: #8B4513;
        }
        
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 30px;
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
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
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
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
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
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn-edit {
            background: #3498db;
            color: white;
        }
        
        .btn-edit:hover {
            background: #2980b9;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c0392b;
        }
        
        @media (max-width: 1024px) {
            .nav-tabs {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>🌍 Silkroad Tour Admin Hub</h1>
        </div>
        <div class="header-actions">
            <a href="logout.php" class="btn btn-danger">Ausloggen</a>
        </div>
    </div>
    
    <div class="container">
        <!-- NAVIGATION TABS -->
        <div class="nav-tabs">
            <a href="?tab=anmeldungen" class="nav-tab <?= $tab === 'anmeldungen' ? 'active' : '' ?>">
                <span class="nav-tab-icon">📋</span>
                <div class="nav-tab-title">Anmeldungen</div>
                <div class="nav-tab-desc"><?= $stats['total'] ?? 0 ?> Anfragen</div>
            </a>
            <a href="?tab=terminplanung" class="nav-tab <?= $tab === 'terminplanung' ? 'active' : '' ?>">
                <span class="nav-tab-icon">📅</span>
                <div class="nav-tab-title">Terminplanung</div>
                <div class="nav-tab-desc"><?= count($tours_list) ?> Touren</div>
            </a>
            <a href="?tab=zoom" class="nav-tab <?= $tab === 'zoom' ? 'active' : '' ?>">
                <span class="nav-tab-icon">📹</span>
                <div class="nav-tab-title">Zoom Meetings</div>
                <div class="nav-tab-desc">In Planung</div>
            </a>
        </div>
        
        <!-- ============================================ -->
        <!-- TAB 1: ANMELDUNGEN -->
        <!-- ============================================ -->
        <div class="tab-content <?= $tab === 'anmeldungen' ? 'active' : '' ?>">
            <h2 style="margin-bottom: 20px; color: #333;">Anmeldungen Übersicht</h2>
            
            <div style="margin-bottom: 20px;">
                <button class="btn btn-success" onclick="openRegistrationModal()">+ Neue Anmeldung hinzufügen</button>
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
            </div>
            
            <div class="filters">
                <form method="GET">
                    <input type="hidden" name="tab" value="anmeldungen">
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
                    <a href="dashboard.php?tab=anmeldungen" class="btn btn-secondary">Reset</a>
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
                                <th>Tour</th>
                                <th>Personen</th>
                                <th>Flughafen</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td><strong>#<?= $req['id'] ?></strong></td>
                                    <td><?= date('d.m.Y', strtotime($req['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($req['name']) ?></td>
                                    <td><a href="mailto:<?= htmlspecialchars($req['email']) ?>" class="email"><?= htmlspecialchars($req['email']) ?></a></td>
                                    <td><span class="badge badge-success"><?= htmlspecialchars($req['tour']) ?></span></td>
                                    <td>
                                        <?= $req['adults'] ?> Erw.
                                        <?php if ($req['children'] > 0): ?> | <?= $req['children'] ?> Ki.<?php endif; ?>
                                        <?php if ($req['toddlers'] > 0): ?> | <?= $req['toddlers'] ?> Kl.<?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($req['abflughafen'] ?? '-') ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-edit btn-sm" onclick="editRegistration(<?= $req['id'] ?>)">Ändern</button>
                                            <button class="btn btn-delete btn-sm" onclick="deleteRegistration(<?= $req['id'] ?>, '<?= htmlspecialchars($req['name']) ?>')">Löschen</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <h2>Keine Anmeldungen gefunden</h2>
                        <p>Es gibt noch keine Anmeldungen.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- ============================================ -->
        <!-- TAB 2: TERMINPLANUNG -->
        <!-- ============================================ -->
        <div class="tab-content <?= $tab === 'terminplanung' ? 'active' : '' ?>">
            <h2 style="margin-bottom: 20px; color: #333;">Terminplanung & Tour-Verwaltung</h2>
            
            <div style="margin-bottom: 20px;">
                <button class="btn btn-success" onclick="showAddTourForm()">+ Neue Tour hinzufügen</button>
            </div>
            
            <div class="table-container">
                <?php if (count($tours_list) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Tour</th>
                                <th>Startdatum</th>
                                <th>Enddatum</th>
                                <th>Preis/Person</th>
                                <th>Kapazität</th>
                                <th>Angemeldet</th>
                                <th>Status</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            foreach ($tours_list as $tour):
                                // Status berechnen
                                $participants = $tour['current_participants'] ?? 0;
                                if ($participants >= ($tour['max_participants'] ?? 10)) {
                                    $status = 'Ausgebucht';
                                    $badge_class = 'badge-danger';
                                } elseif ($participants >= 4) {
                                    $status = 'Garantiert';
                                    $badge_class = 'badge-success';
                                } else {
                                    $status = 'In Planung';
                                    $badge_class = 'badge-warning';
                                }
                            ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($tour['name']) ?></strong></td>
                                    <td><?= date('d.m.Y', strtotime($tour['start_date'])) ?></td>
                                    <td><?= date('d.m.Y', strtotime($tour['end_date'])) ?></td>
                                    <td><?= number_format($tour['price_per_person'], 2, ',', '.') ?> €</td>
                                    <td><?= $tour['max_participants'] ?> Personen</td>
                                    <td><?= $participants ?> / <?= $tour['max_participants'] ?></td>
                                    <td><span class="badge <?= $badge_class ?>"><?= $status ?></span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-edit btn-sm" onclick="editTour(<?= $tour['id'] ?>)">Ändern</button>
                                            <button class="btn btn-delete btn-sm" onclick="deleteTour(<?= $tour['id'] ?>, '<?= htmlspecialchars($tour['name']) ?>')">Löschen</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <h2>Keine Touren vorhanden</h2>
                        <p>Erstelle eine neue Tour, um anzufangen.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- ============================================ -->
        <!-- TAB 3: ZOOM MEETINGS -->
        <!-- ============================================ -->
        <div class="tab-content <?= $tab === 'zoom' ? 'active' : '' ?>">
            <h2 style="margin-bottom: 20px; color: #333;">Zoom Meetings</h2>
            
            <div style="background: white; padding: 40px; border-radius: 8px; text-align: center;">
                <p style="font-size: 48px; margin-bottom: 20px;">📹</p>
                <h3 style="color: #777; margin-bottom: 10px;">Zoom Meetings</h3>
                <p style="color: #999;">Diese Funktion wird in Kürze verfügbar sein.</p>
            </div>
        </div>
    </div>
    
    <!-- ============================================ -->
    <!-- MODAL: ANMELDUNG BEARBEITEN -->
    <!-- ============================================ -->
    <div id="registrationModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 12px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto;">
            <h3 style="margin-bottom: 20px; color: #333;">Anmeldung bearbeiten</h3>
            <form id="registrationForm">
                <input type="hidden" id="regId">
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555;">Name</label>
                    <input type="text" id="regName" required style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555;">Email</label>
                    <input type="email" id="regEmail" required style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555;">Telefon</label>
                    <input type="tel" id="regPhone" required style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555;">Tour</label>
                    <select id="regTour" required style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px; background: white;">
                        <option value="">Bitte Tour wählen</option>
                        <?php foreach ($available_tours as $available_tour): ?>
                            <option value="<?= htmlspecialchars($available_tour) ?>"><?= htmlspecialchars($available_tour) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555;">Reisedatum</label>
                    <input type="date" id="regTravelDate" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px;">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555;">Erw.</label>
                        <input type="number" id="regAdults" min="1" required style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555;">Kinder</label>
                        <input type="number" id="regChildren" min="0" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555;">Kl.</label>
                        <input type="number" id="regToddlers" min="0" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px;">
                    </div>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555;">Flughafen</label>
                    <input type="text" id="regAirport" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px;">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555;">Notiz</label>
                    <textarea id="regMessage" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px; min-height: 80px;"></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeRegistrationModal()" style="padding: 10px 20px;">Abbrechen</button>
                    <button type="submit" class="btn btn-success" style="padding: 10px 20px;">Speichern</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- ============================================ -->
    <!-- MODAL: TOUR BEARBEITEN / HINZUFÜGEN -->
    <!-- ============================================ -->
    <div id="tourModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 12px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto;">
            <h3 style="margin-bottom: 20px; color: #333;" id="tourModalTitle">Tour hinzufügen</h3>
            <form id="tourForm">
                <input type="hidden" id="tourId">
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555;">Tour Name</label>
                    <input type="text" id="tourName" list="tourNameList" required placeholder="Tourname eingeben" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px;">
                    <datalist id="tourNameList">
                        <?php foreach ($available_tours as $available_tour): ?>
                            <option value="<?= htmlspecialchars($available_tour) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555;">Startdatum</label>
                        <input type="date" id="tourStartDate" required style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555;">Enddatum</label>
                        <input type="date" id="tourEndDate" required style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px;">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555;">Preis/Person (€)</label>
                        <input type="number" id="tourPrice" step="0.01" min="0" required style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555;">Max. Teilnehmer</label>
                        <input type="number" id="tourCapacity" min="1" value="10" required style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px;">
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeTourModal()" style="padding: 10px 20px;">Abbrechen</button>
                    <button type="submit" class="btn btn-success" style="padding: 10px 20px;">Speichern</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // ============ REGISTRATION FUNCTIONS ============
        
        function openRegistrationModal(id = null) {
            const modal = document.getElementById('registrationModal');
            
            if (id) {
                // Edit mode
                fetch(`api/registration_get.php?id=${id}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.error) {
                            alert('Fehler: ' + data.error);
                            return;
                        }
                        document.getElementById('regId').value = data.id;
                        document.getElementById('regName').value = data.name;
                        document.getElementById('regEmail').value = data.email;
                        document.getElementById('regPhone').value = data.phone;
                        
                        // Legacy support: Add tour option if it doesn't exist in the list
                        const tourSelect = document.getElementById('regTour');
                        const optionExists = Array.from(tourSelect.options).some(option => option.value === data.tour);
                        if (!optionExists && data.tour) {
                            const legacyOption = new Option(data.tour, data.tour);
                            tourSelect.add(legacyOption);
                        }
                        tourSelect.value = data.tour;
                        
                        document.getElementById('regTravelDate').value = data.travel_date;
                        document.getElementById('regAdults').value = data.adults;
                        document.getElementById('regChildren').value = data.children;
                        document.getElementById('regToddlers').value = data.toddlers;
                        document.getElementById('regAirport').value = data.abflughafen || '';
                        document.getElementById('regMessage').value = data.message || '';
                        modal.style.display = 'flex';
                    })
                    .catch(err => alert('Fehler beim Laden: ' + err));
            } else {
                // Add mode
                document.getElementById('registrationForm').reset();
                document.getElementById('regId').value = '';
                modal.style.display = 'flex';
            }
        }
        
        function closeRegistrationModal() {
            document.getElementById('registrationModal').style.display = 'none';
        }
        
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const id = document.getElementById('regId').value;
            const data = {
                id: id || null,
                name: document.getElementById('regName').value,
                email: document.getElementById('regEmail').value,
                phone: document.getElementById('regPhone').value,
                tour: document.getElementById('regTour').value,
                travel_date: document.getElementById('regTravelDate').value,
                adults: document.getElementById('regAdults').value,
                children: document.getElementById('regChildren').value,
                toddlers: document.getElementById('regToddlers').value,
                abflughafen: document.getElementById('regAirport').value,
                message: document.getElementById('regMessage').value
            };
            
            fetch('api/registration_update.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(result => {
                if (result.error) {
                    alert('Fehler: ' + result.error);
                } else {
                    alert('Erfolgreich gespeichert!');
                    closeRegistrationModal();
                    location.reload();
                }
            })
            .catch(err => alert('Fehler: ' + err));
        });
        
        function editRegistration(id) {
            openRegistrationModal(id);
        }
        
        function deleteRegistration(id, name) {
            if (confirm(`Wirklich "${name}" löschen?`)) {
                fetch('api/registration_delete.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: id})
                })
                .then(r => r.json())
                .then(result => {
                    if (result.error) {
                        alert('Fehler: ' + result.error);
                    } else {
                        alert('Gelöscht!');
                        location.reload();
                    }
                })
                .catch(err => alert('Fehler: ' + err));
            }
        }
        
        // ============ TOUR FUNCTIONS ============
        
        function openTourModal(id = null) {
            const modal = document.getElementById('tourModal');
            const title = document.getElementById('tourModalTitle');
            
            if (id) {
                // Edit mode
                title.textContent = 'Tour bearbeiten';
                fetch(`api/tour_get.php?id=${id}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.error) {
                            alert('Fehler: ' + data.error);
                            return;
                        }
                        document.getElementById('tourId').value = data.id;
                        document.getElementById('tourName').value = data.name;
                        
                        document.getElementById('tourStartDate').value = data.start_date;
                        document.getElementById('tourEndDate').value = data.end_date;
                        document.getElementById('tourPrice').value = data.price_per_person;
                        document.getElementById('tourCapacity').value = data.max_participants;
                        modal.style.display = 'flex';
                    })
                    .catch(err => alert('Fehler beim Laden: ' + err));
            } else {
                // Add mode
                title.textContent = 'Tour hinzufügen';
                document.getElementById('tourForm').reset();
                document.getElementById('tourId').value = '';
                modal.style.display = 'flex';
            }
        }
        
        function closeTourModal() {
            document.getElementById('tourModal').style.display = 'none';
        }
        
        document.getElementById('tourForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const id = document.getElementById('tourId').value;
            const data = {
                id: id || null,
                name: document.getElementById('tourName').value,
                start_date: document.getElementById('tourStartDate').value,
                end_date: document.getElementById('tourEndDate').value,
                price_per_person: document.getElementById('tourPrice').value,
                max_participants: document.getElementById('tourCapacity').value
            };
            
            const endpoint = id ? 'api/tour_update.php' : 'api/tour_create.php';
            
            fetch(endpoint, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(result => {
                if (result.error) {
                    alert('Fehler: ' + result.error);
                } else {
                    alert(id ? 'Tour aktualisiert!' : 'Tour erstellt!');
                    closeTourModal();
                    location.reload();
                }
            })
            .catch(err => alert('Fehler: ' + err));
        });
        
        function showAddTourForm() {
            openTourModal();
        }
        
        function editTour(id) {
            openTourModal(id);
        }
        
        function deleteTour(id, name) {
            if (confirm(`Wirklich die Tour "${name}" löschen?`)) {
                fetch('api/tour_delete.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: id})
                })
                .then(r => r.json())
                .then(result => {
                    if (result.error) {
                        alert('Fehler: ' + result.error);
                    } else {
                        alert('Tour gelöscht!');
                        location.reload();
                    }
                })
                .catch(err => alert('Fehler: ' + err));
            }
        }
    </script>
</body>
</html>
