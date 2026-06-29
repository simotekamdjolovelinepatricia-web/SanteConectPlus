<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] != 'medecin') { 
    header('Location: index.php'); 
    exit(); 
}

if (isset($_GET['logout'])) { 
    session_destroy(); 
    header('Location: index.php'); 
    exit(); 
}

require 'db.php';

// Récupérer les informations du médecin connecté
$stmt = $conn->prepare("SELECT * FROM medecin WHERE idMedecin = ?");
$stmt->execute([$_SESSION['id']]);
$medecin = $stmt->fetch();

// Récupérer les consultations du médecin
$stmt = $conn->prepare("
    SELECT c.*, p.nomPatient, p.prenomPatient, r.dateRdv, r.heureRdv 
    FROM consultation c
    JOIN rendezvous r ON c.idRdv = r.idRdv
    JOIN patient p ON r.idPatient = p.idPatient
    WHERE c.idMedecin = ?
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
    <title>Dashboard Médecin | Santé Connect+</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f7fa; }
        .navbar { background: #2c3e50; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .navbar h1 { font-size: 1.5rem; }
        .navbar a { color: white; text-decoration: none; padding: 0.5rem 1rem; background: #e74c3c; border-radius: 4px; }
        .navbar a:hover { background: #c0392b; }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        .welcome-card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .welcome-card h2 { color: #2c3e50; margin-bottom: 0.5rem; }
        .welcome-card .info { color: #7f8c8d; }
        .section { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
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
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🏥 Santé Connect+ - Médecin</h1>
        <a href="?logout=true">Déconnexion</a>
    </div>
    <div class="container">
        <div class="welcome-card">
            <h2>Bonjour Dr <?php echo htmlspecialchars($_SESSION['user']); ?> 👋</h2>
            <p class="info">Service : <?php echo htmlspecialchars($medecin['idService']); ?> | Email : <?php echo htmlspecialchars($medecin['email']); ?></p>
        </div>

        <div class="section">
            <h3>📋 Mes consultations</h3>
            <?php if (count($consultations) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date Consultation</th>
                        <th>Patient</th>
                        <th>RDV le</th>
                        <th>Diagnostic</th>
                        <th>Traitement</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($consultations as $c): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($c['dateConsultation'])); ?></td>
                        <td><?php echo htmlspecialchars($c['prenomPatient'] . ' ' . $c['nomPatient']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($c['dateRdv'])) . ' ' . substr($c['heureRdv'], 0, 5); ?></td>
                        <td><?php echo htmlspecialchars($c['diagnostic']); ?></td>
                        <td><?php echo htmlspecialchars($c['traitement']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="empty">Aucune consultation pour le moment.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>