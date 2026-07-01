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

// Récupérer la liste complète des patients
$stmt = $conn->prepare("
    SELECT *
    FROM patient
    ORDER BY nomPatient, prenomPatient
");
$stmt->execute();
$patients = $stmt->fetchAll();

// Récupérer les RDV du médecin
$stmt = $conn->prepare("
    SELECT r.*, p.nomPatient, p.prenomPatient, s.nomService
    FROM rendezvous r
    JOIN patient p ON r.idPatient = p.idPatient
    JOIN service s ON r.idService = s.idService
    WHERE r.idService = ? AND r.dateRdv >= CURDATE()
    ORDER BY r.dateRdv, r.heureRdv
");
$stmt->execute([$medecin['idService']]);
$rendezvous = $stmt->fetchAll();

// Page active
$page = isset($_GET['page']) ? $_GET['page'] : 'accueil';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Médecin | Santé Connect+</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        
        .container-main { display: flex; min-height: 100vh; }
        
        /* Navbar */
        .navbar { 
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); 
            color: white; 
            padding: 1rem 2rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
        }
        .navbar h1 { font-size: 1.5rem; font-weight: 600; }
        .navbar-right { display: flex; gap: 1rem; align-items: center; }
        .navbar a { color: white; text-decoration: none; padding: 0.6rem 1.2rem; background: #e74c3c; border-radius: 4px; cursor: pointer; border: none; font-size: 1rem; }
        .navbar a:hover { background: #c0392b; transition: 0.3s; }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #34495e 0%, #2c3e50 100%);
            color: white;
            padding: 100px 0 2rem 0;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 8px rgba(0,0,0,0.15);
            z-index: 99;
        }
        
        .sidebar-menu { list-style: none; }
        .sidebar-menu li { border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-menu a {
            display: block;
            color: #ecf0f1;
            text-decoration: none;
            padding: 1rem 1.5rem;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.1);
            border-left-color: #e74c3c;
            padding-left: 1.75rem;
        }
        .sidebar-menu a.active {
            background: #e74c3c;
            border-left-color: #fff;
            font-weight: 600;
        }
        .sidebar-menu .icon { margin-right: 0.75rem; font-size: 1.2rem; }
        
        /* Main content */
        .main-content {
            margin-left: 280px;
            margin-top: 80px;
            flex: 1;
            padding: 2rem;
        }
        
        /* Cards et sections */
        .welcome-card { 
            background: white; 
            padding: 2rem; 
            border-radius: 8px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
            margin-bottom: 2rem;
            border-left: 5px solid #e74c3c;
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
        .section h3 { color: #2c3e50; margin-bottom: 1.5rem; font-size: 1.3rem; border-bottom: 2px solid #ecf0f1; padding-bottom: 0.75rem; }
        
        /* Tables */
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
        
        /* Badges */
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
        
        /* Boutons */
        .btn {
            display: inline-block;
            padding: 0.6rem 1.2rem;
            background: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
            transition: 0.3s;
            margin-right: 0.5rem;
        }
        .btn:hover { background: #c0392b; }
        .btn-success { background: #2ecc71; }
        .btn-success:hover { background: #27ae60; }
        .btn-info { background: #3498db; }
        .btn-info:hover { background: #2980b9; }
        
        .empty { text-align: center; color: #7f8c8d; padding: 2rem; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 4px solid #e74c3c;
        }
        .stat-card .number { font-size: 2.5rem; font-weight: bold; color: #e74c3c; }
        .stat-card .label { color: #7f8c8d; margin-top: 0.5rem; }
        
        /* Formulaires */
        .form-group {
            margin-bottom: 1.5rem;
        }
        label { display: block; margin-bottom: 0.5rem; color: #2c3e50; font-weight: 500; }
        input, textarea, select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
            font-family: inherit;
            font-size: 0.95rem;
        }
        textarea { resize: vertical; min-height: 100px; }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #e74c3c;
            box-shadow: 0 0 5px rgba(231, 76, 60, 0.3);
        }
        
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .grid-2, .grid-3 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🏥 Santé Connect+ - Médecin</h1>
        <div class="navbar-right">
            <span>Dr <?php echo htmlspecialchars($medecin['prenomMedecin'] . ' ' . $medecin['nomMedecin']); ?></span>
            <a href="?logout=true">Déconnexion</a>
        </div>
    </div>

    <div class="container-main">
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="dashboard_medecin.php?page=accueil" class="<?php echo $page === 'accueil' ? 'active' : ''; ?>">
                    <span class="icon">🏠</span> Accueil
                </a></li>
                
                <li><a href="dashboard_medecin.php?page=patients" class="<?php echo $page === 'patients' ? 'active' : ''; ?>">
                    <span class="icon">👥</span> Mes Patients
                </a></li>
                
                <li><a href="add_patient.php" class="<?php echo $page === 'ajouter_patient' ? 'active' : ''; ?>">
                    <span class="icon">➕</span> Ajouter Patient
                </a></li>
                
                <li><a href="dashboard_medecin.php?page=rendezvous" class="<?php echo $page === 'rendezvous' ? 'active' : ''; ?>">
                    <span class="icon">📅</span> Rendez-vous
                </a></li>
                
                <li><a href="schedule_rdv.php" class="<?php echo $page === 'programmer_rdv' ? 'active' : ''; ?>">
                    <span class="icon">📝</span> Programmer RDV
                </a></li>
                
                <li><a href="dashboard_medecin.php?page=consultations" class="<?php echo $page === 'consultations' ? 'active' : ''; ?>">
                    <span class="icon">📋</span> Consultations
                </a></li>
                
                <li><a href="new_consultation.php" class="<?php echo $page === 'nouvelle_consultation' ? 'active' : ''; ?>">
                    <span class="icon">🔍</span> Nouvelle Consultation
                </a></li>
                
                <li><a href="new_ordonnance.php" class="<?php echo $page === 'ordonnances' ? 'active' : ''; ?>">
                    <span class="icon">💊</span> Créer Ordonnance
                </a></li>

                <li><a href="new_resultat.php" class="<?php echo $page === 'resultats' ? 'active' : ''; ?>">
                    <span class="icon">🔬</span> Ajouter Résultat
                </a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <?php if ($page === 'accueil'): ?>
                <div class="welcome-card">
                    <h2>Bonjour Dr <?php echo htmlspecialchars($medecin['prenomMedecin'] . ' ' . $medecin['nomMedecin']); ?> 👋</h2>
                    <p class="info">Service : Cardiologie | Email : <?php echo htmlspecialchars($medecin['email']); ?></p>
                </div>

                <div class="grid-3">
                    <div class="stat-card">
                        <div class="number"><?php echo count($patients); ?></div>
                        <div class="label">Patients</div>
                    </div>
                    <div class="stat-card">
                        <div class="number"><?php echo count($consultations); ?></div>
                        <div class="label">Consultations</div>
                    </div>
                    <div class="stat-card">
                        <div class="number"><?php echo count($rendezvous); ?></div>
                        <div class="label">RDV à venir</div>
                    </div>
                </div>

                <div class="section">
                    <h3>� Liste des patients</h3>
                    <?php if (count($patients) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Téléphone</th>
                                <th>Email</th>
                                <th>Groupe Sanguin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($patients as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($p['nomPatient']); ?></td>
                                <td><?php echo htmlspecialchars($p['prenomPatient']); ?></td>
                                <td><?php echo htmlspecialchars($p['telephone']); ?></td>
                                <td><?php echo htmlspecialchars($p['email']); ?></td>
                                <td><?php echo htmlspecialchars($p['groupeSanguin']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="empty">Aucun patient enregistré.</p>
                    <?php endif; ?>
                </div>

                <div class="section">
                    <h3>�📅 Rendez-vous à venir</h3>
                    <?php if (count($rendezvous) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Heure</th>
                                <th>Patient</th>
                                <th>Motif</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($rendezvous, 0, 5) as $r): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($r['dateRdv'])); ?></td>
                                <td><?php echo substr($r['heureRdv'], 0, 5); ?></td>
                                <td><?php echo htmlspecialchars($r['prenomPatient'] . ' ' . $r['nomPatient']); ?></td>
                                <td><?php echo htmlspecialchars($r['motif']); ?></td>
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
                    <h3>📋 Dernières consultations</h3>
                    <?php if (count($consultations) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Patient</th>
                                <th>Diagnostic</th>
                                <th>Traitement</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($consultations, 0, 5) as $c): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($c['dateConsultation'])); ?></td>
                                <td><?php echo htmlspecialchars($c['prenomPatient'] . ' ' . $c['nomPatient']); ?></td>
                                <td><?php echo htmlspecialchars(substr($c['diagnostic'], 0, 50)); ?></td>
                                <td><?php echo htmlspecialchars(substr($c['traitement'], 0, 50)); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="empty">Aucune consultation pour le moment.</p>
                    <?php endif; ?>
                </div>

            <?php elseif ($page === 'patients'): ?>
                <div class="section">
                    <h3>👥 Mes Patients</h3>
                    <?php if (count($patients) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Téléphone</th>
                                <th>Groupe Sanguin</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($patients as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($p['nomPatient']); ?></td>
                                <td><?php echo htmlspecialchars($p['prenomPatient']); ?></td>
                                <td><?php echo htmlspecialchars($p['telephone']); ?></td>
                                <td><?php echo htmlspecialchars($p['groupeSanguin']); ?></td>
                                <td>
                                    <a href="patient_details.php?id=<?php echo $p['idPatient']; ?>" class="btn btn-info">Voir détails</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="empty">Aucun patient pour le moment.</p>
                    <?php endif; ?>
                </div>

            <?php elseif ($page === 'rendezvous'): ?>
                <div class="section">
                    <h3>📅 Tous mes Rendez-vous</h3>
                    <?php if (count($rendezvous) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Heure</th>
                                <th>Patient</th>
                                <th>Motif</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rendezvous as $r): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($r['dateRdv'])); ?></td>
                                <td><?php echo substr($r['heureRdv'], 0, 5); ?></td>
                                <td><?php echo htmlspecialchars($r['prenomPatient'] . ' ' . $r['nomPatient']); ?></td>
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
                    <h3>📋 Toutes mes Consultations</h3>
                    <?php if (count($consultations) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Patient</th>
                                <th>Diagnostic</th>
                                <th>Traitement</th>
                                <th>Observations</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($consultations as $c): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($c['dateConsultation'])); ?></td>
                                <td><?php echo htmlspecialchars($c['prenomPatient'] . ' ' . $c['nomPatient']); ?></td>
                                <td><?php echo htmlspecialchars($c['diagnostic']); ?></td>
                                <td><?php echo htmlspecialchars($c['traitement']); ?></td>
                                <td><?php echo htmlspecialchars(substr($c['observations'], 0, 50)); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="empty">Aucune consultation pour le moment.</p>
                    <?php endif; ?>
                </div>

            <?php elseif ($page === 'ordonnances'): ?>
                <div class="section">
                    <h3>💊 Ordonnances</h3>
                    <p class="empty">Les ordonnances seront affichées ici.</p>
                </div>

            <?php endif; ?>
        </main>
    </div>
</body>
</html>