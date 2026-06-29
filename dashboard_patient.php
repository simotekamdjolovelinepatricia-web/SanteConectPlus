<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] != 'patient') { 
    header('Location: index.php'); 
    exit(); 
}

if (isset($_GET['logout'])) { 
    session_destroy(); 
    header('Location: index.php'); 
    exit(); 
}

require 'db.php';

// Récupérer les informations du patient connecté
$stmt = $conn->prepare("SELECT * FROM patient WHERE idPatient = ?");
$stmt->execute([$_SESSION['id']]);
$patient = $stmt->fetch();

// Récupérer les rendez-vous du patient
$stmt = $conn->prepare("
    SELECT r.*, s.nomService 
    FROM rendezvous r
    JOIN service s ON r.idService = s.idService
    WHERE r.idPatient = ?
    ORDER BY r.dateRdv DESC, r.heureRdv DESC
");
$stmt->execute([$_SESSION['id']]);
$rendezvous = $stmt->fetchAll();

// Récupérer les consultations du patient
$stmt = $conn->prepare("
    SELECT c.*, m.prenomMedecin, m.nomMedecin, s.nomService, r.dateRdv, r.heureRdv
    FROM consultation c
    JOIN rendezvous r ON c.idRdv = r.idRdv
    JOIN medecin m ON c.idMedecin = m.idMedecin
    JOIN service s ON r.idService = s.idService
    WHERE r.idPatient = ?
    ORDER BY c.dateConsultation DESC
");
$stmt->execute([$_SESSION['id']]);
$consultations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Patient | Santé Connect+</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f7fa; }
        .navbar { background: green; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .navbar h1 { font-size: 1.5rem; }
        .navbar a { color: white; text-decoration: none; padding: 0.5rem 1rem; background: #e74c3c; border-radius: 4px; }
        .navbar a:hover { background: #c0392b; }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        .welcome-card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .welcome-card h2 { color: #2c3e50; margin-bottom: 0.5rem; }
        .welcome-card .info { color: #7f8c8d; }
        .section { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .section h3 { color: #2c3e50; margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; }
        table th { background: #34495e; color: white; padding: 0.75rem; text-align: left; }
        table td { padding: 0.75rem; border-bottom: 1px solid #ecf0f1; }
        table tr:hover { background: #f8f9fa; }
        .badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
        .badge-programme { background: #f39c12; color: white; }
        .badge-confirme { background: #2ecc71; color: white; }
        .badge-termine { background: #3498db; color: white; }
        .badge-annule { background: #e74c3c; color: white; }
        .empty { text-align: center; color: #7f8c8d; padding: 2rem; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        @media (max-width: 768px) { .grid-2 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🏥 Santé Connect+ - Patient</h1>
        <a href="?logout=true">Déconnexion</a>
    </div>
    <div class="container">
        <div class="welcome-card">
            <h2>Bonjour <?php echo htmlspecialchars($_SESSION['user']); ?> 👋</h2>
            <p class="info">
                Groupe sanguin : <?php echo htmlspecialchars($patient['groupeSanguin']); ?> | 
                Email : <?php echo htmlspecialchars($patient['email']); ?>
            </p>
        </div>

        <div class="grid-2">
            <div class="section">
                <h3>📅 Mes rendez-vous</h3>
                <?php if (count($rendezvous) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Heure</th>
                            <th>Service</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rendezvous as $r): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($r['dateRdv'])); ?></td>
                            <td><?php echo substr($r['heureRdv'], 0, 5); ?></td>
                            <td><?php echo htmlspecialchars($r['nomService']); ?></td>
                            <td><span class="badge badge-<?php echo $r['statut']; ?>"><?php echo ucfirst($r['statut']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="empty">Aucun rendez-vous.</p>
                <?php endif; ?>
            </div>

            <div class="section">
                <h3>💊 Mes consultations</h3>
                <?php if (count($consultations) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Médecin</th>
                            <th>Service</th>
                            <th>Diagnostic</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($consultations as $c): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($c['dateConsultation'])); ?></td>
                            <td>Dr <?php echo htmlspecialchars($c['prenomMedecin'] . ' ' . $c['nomMedecin']); ?></td>
                            <td><?php echo htmlspecialchars($c['nomService']); ?></td>
                            <td><?php echo htmlspecialchars($c['diagnostic']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="empty">Aucune consultation.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>