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

// Récupérer les ordonnances du patient
$stmt = $conn->prepare("
    SELECT o.*, c.diagnostic, c.traitement, r.dateRdv, m.prenomMedecin, m.nomMedecin
    FROM ordonnance o
    JOIN consultation c ON o.idConsultation = c.idConsultation
    JOIN rendezvous r ON c.idRdv = r.idRdv
    JOIN medecin m ON c.idMedecin = m.idMedecin
    WHERE r.idPatient = ?
    ORDER BY o.dateOrdonnance DESC
");
$stmt->execute([$_SESSION['id']]);
$ordonnances = $stmt->fetchAll();

// Récupérer les résultats d'examens du patient
$stmt = $conn->prepare("
    SELECT r.*, c.diagnostic, c.traitement, m.prenomMedecin, m.nomMedecin
    FROM resultat r
    JOIN consultation c ON r.idConsultation = c.idConsultation
    JOIN rendezvous rdv ON c.idRdv = rdv.idRdv
    JOIN medecin m ON c.idMedecin = m.idMedecin
    WHERE rdv.idPatient = ?
    ORDER BY r.dateTest DESC
");
$stmt->execute([$_SESSION['id']]);
$resultats = $stmt->fetchAll();

// Page active
$page = isset($_GET['page']) ? $_GET['page'] : 'accueil';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Patient | Santé Connect+</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        
        .navbar { 
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%); 
            color: white; 
            padding: 1rem 2rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .navbar h1 { font-size: 1.5rem; font-weight: 600; }
        .navbar a { color: white; text-decoration: none; padding: 0.6rem 1.2rem; background: #e74c3c; border-radius: 4px; }
        .navbar a:hover { background: #c0392b; }
        
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        
        .welcome-card { 
            background: white; 
            padding: 2rem; 
            border-radius: 8px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
            margin-bottom: 2rem;
            border-left: 5px solid #27ae60;
        }
        .welcome-card h2 { color: #2c3e50; margin-bottom: 0.5rem; font-size: 1.8rem; }
        .welcome-card .info { color: #7f8c8d; font-size: 0.95rem; }
        
        .section { 
            background: white; 
            padding: 2rem; 
            border-radius: 8px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .section h3 { 
            color: #2c3e50; 
            margin-bottom: 1.5rem; 
            font-size: 1.3rem; 
            border-bottom: 2px solid #ecf0f1; 
            padding-bottom: 0.75rem; 
        }
        
        table { width: 100%; border-collapse: collapse; }
        table th { 
            background: #34495e; 
            color: white; 
            padding: 1rem; 
            text-align: left;
            font-weight: 600;
        }
        table td { 
            padding: 0.85rem 1rem; 
            border-bottom: 1px solid #ecf0f1; 
        }
        table tr:hover { background: #f8f9fa; }
        
        .badge { 
            padding: 0.35rem 0.75rem; 
            border-radius: 4px; 
            font-size: 0.8rem; 
            font-weight: bold;
            display: inline-block;
        }
        .badge-programme { background: #f39c12; color: white; }
        .badge-confirme { background: #2ecc71; color: white; }
        .badge-termine { background: #3498db; color: white; }
        .badge-annule { background: #e74c3c; color: white; }
        
        .empty { text-align: center; color: #7f8c8d; padding: 2rem; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 4px solid #27ae60;
        }
        .stat-card .number { font-size: 2.5rem; font-weight: bold; color: #27ae60; }
        .stat-card .label { color: #7f8c8d; margin-top: 0.5rem; }
        
        .nav-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #ecf0f1;
        }
        .nav-tabs a {
            padding: 1rem 1.5rem;
            color: #7f8c8d;
            text-decoration: none;
            border-bottom: 3px solid transparent;
            transition: 0.3s;
        }
        .nav-tabs a:hover {
            color: #27ae60;
        }
        .nav-tabs a.active {
            color: #27ae60;
            border-bottom-color: #27ae60;
            font-weight: 600;
        }
        
        @media (max-width: 768px) { 
            .grid-2, .grid-3 { grid-template-columns: 1fr; }
            .nav-tabs { flex-wrap: wrap; }
        }
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
                Email : <?php echo htmlspecialchars($patient['email']); ?> |
                Dossier créé le : <?php echo date('d/m/Y', strtotime($patient['dateCreationDossier'])); ?>
            </p>
        </div>

        <div class="nav-tabs">
            <a href="dashboard_patient.php?page=accueil" class="<?php echo $page === 'accueil' ? 'active' : ''; ?>">📊 Accueil</a>
            <a href="dashboard_patient.php?page=rendezvous" class="<?php echo $page === 'rendezvous' ? 'active' : ''; ?>">📅 Rendez-vous</a>
            <a href="dashboard_patient.php?page=consultations" class="<?php echo $page === 'consultations' ? 'active' : ''; ?>">📋 Consultations</a>
            <a href="dashboard_patient.php?page=ordonnances" class="<?php echo $page === 'ordonnances' ? 'active' : ''; ?>">💊 Ordonnances</a>
            <a href="dashboard_patient.php?page=resultats" class="<?php echo $page === 'resultats' ? 'active' : ''; ?>">🔬 Résultats</a>
        </div>

        <?php if ($page === 'accueil'): ?>
            <div class="grid-3">
                <div class="stat-card">
                    <div class="number"><?php echo count($rendezvous); ?></div>
                    <div class="label">Rendez-vous</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo count($consultations); ?></div>
                    <div class="label">Consultations</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo count($ordonnances); ?></div>
                    <div class="label">Ordonnances</div>
                </div>
            </div>

            <div class="grid-2">
                <div class="section">
                    <h3>📅 Mes rendez-vous à venir</h3>
                    <?php 
                    $futureRdv = array_filter($rendezvous, function($r) { 
                        return strtotime($r['dateRdv']) >= time(); 
                    });
                    if (count($futureRdv) > 0): 
                    ?>
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
                            <?php foreach (array_slice($futureRdv, 0, 5) as $r): ?>
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
                    <p class="empty">Aucun rendez-vous à venir.</p>
                    <?php endif; ?>
                </div>

                <div class="section">
                    <h3>💊 Ordonnances récentes</h3>
                    <?php if (count($ordonnances) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Médicament</th>
                                <th>Dosage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($ordonnances, 0, 5) as $o): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($o['dateOrdonnance'])); ?></td>
                                <td><?php echo htmlspecialchars(substr($o['medicament'], 0, 40)); ?></td>
                                <td><?php echo htmlspecialchars($o['dosage']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="empty">Aucune ordonnance.</p>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($page === 'rendezvous'): ?>
            <div class="section">
                <h3>📅 Tous mes rendez-vous</h3>
                <?php if (count($rendezvous) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Heure</th>
                            <th>Service</th>
                            <th>Motif</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rendezvous as $r): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($r['dateRdv'])); ?></td>
                            <td><?php echo substr($r['heureRdv'], 0, 5); ?></td>
                            <td><?php echo htmlspecialchars($r['nomService']); ?></td>
                            <td><?php echo htmlspecialchars($r['motif']); ?></td>
                            <td><span class="badge badge-<?php echo $r['statut']; ?>"><?php echo ucfirst($r['statut']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="empty">Aucun rendez-vous.</p>
                <?php endif; ?>
            </div>

        <?php elseif ($page === 'consultations'): ?>
            <div class="section">
                <h3>📋 Mes consultations</h3>
                <?php if (count($consultations) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Médecin</th>
                            <th>Service</th>
                            <th>Diagnostic</th>
                            <th>Traitement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($consultations as $c): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($c['dateConsultation'])); ?></td>
                            <td>Dr <?php echo htmlspecialchars($c['prenomMedecin'] . ' ' . $c['nomMedecin']); ?></td>
                            <td><?php echo htmlspecialchars($c['nomService']); ?></td>
                            <td><?php echo htmlspecialchars(substr($c['diagnostic'], 0, 50)); ?></td>
                            <td><?php echo htmlspecialchars(substr($c['traitement'], 0, 50)); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="empty">Aucune consultation.</p>
                <?php endif; ?>
            </div>

        <?php elseif ($page === 'ordonnances'): ?>
            <div class="section">
                <h3>💊 Mes ordonnances</h3>
                <?php if (count($ordonnances) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Médecin</th>
                            <th>Médicament</th>
                            <th>Dosage</th>
                            <th>Durée</th>
                            <th>Instructions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ordonnances as $o): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($o['dateOrdonnance'])); ?></td>
                            <td>Dr <?php echo htmlspecialchars($o['prenomMedecin'] . ' ' . $o['nomMedecin']); ?></td>
                            <td><?php echo htmlspecialchars($o['medicament']); ?></td>
                            <td><?php echo htmlspecialchars($o['dosage']); ?></td>
                            <td><?php echo htmlspecialchars($o['duree']); ?></td>
                            <td><?php echo htmlspecialchars(substr($o['instructions'], 0, 50)); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="empty">Aucune ordonnance.</p>
                <?php endif; ?>
            </div>

        <?php elseif ($page === 'resultats'): ?>
            <div class="section">
                <h3>🔬 Mes résultats d'examens</h3>
                <?php if (count($resultats) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type de test</th>
                            <th>Résultat</th>
                            <th>Valeur normale</th>
                            <th>Médecin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultats as $r): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($r['dateTest'])); ?></td>
                            <td><?php echo htmlspecialchars($r['typeTest']); ?></td>
                            <td><?php echo htmlspecialchars(substr($r['resultat'], 0, 50)); ?></td>
                            <td><?php echo htmlspecialchars($r['valeurNormale']); ?></td>
                            <td>Dr <?php echo htmlspecialchars($r['prenomMedecin'] . ' ' . $r['nomMedecin']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="empty">Aucun résultat d'examen.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>