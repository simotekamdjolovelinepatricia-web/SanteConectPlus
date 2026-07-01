<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] != 'medecin') { 
    header('Location: index.php'); 
    exit(); 
}

require 'db.php';

$patientId = isset($_GET['id']) ? $_GET['id'] : 0;

// Récupérer les infos du patient
$stmt = $conn->prepare("SELECT * FROM patient WHERE idPatient = ?");
$stmt->execute([$patientId]);
$patient = $stmt->fetch();

if (!$patient) {
    header('Location: dashboard_medecin.php?page=patients');
    exit();
}

// Récupérer les rendez-vous du patient
$stmt = $conn->prepare("
    SELECT r.*, s.nomService 
    FROM rendezvous r
    JOIN service s ON r.idService = s.idService
    WHERE r.idPatient = ?
    ORDER BY r.dateRdv DESC
");
$stmt->execute([$patientId]);
$rendezvous = $stmt->fetchAll();

// Récupérer les consultations du patient
$stmt = $conn->prepare("
    SELECT c.*, m.prenomMedecin, m.nomMedecin, r.dateRdv, r.heureRdv
    FROM consultation c
    JOIN rendezvous r ON c.idRdv = r.idRdv
    JOIN medecin m ON c.idMedecin = m.idMedecin
    WHERE r.idPatient = ?
    ORDER BY c.dateConsultation DESC
");
$stmt->execute([$patientId]);
$consultations = $stmt->fetchAll();

// Récupérer les ordonnances du patient
$stmt = $conn->prepare("
    SELECT o.*, c.diagnostic, c.traitement, r.dateRdv
    FROM ordonnance o
    JOIN consultation c ON o.idConsultation = c.idConsultation
    JOIN rendezvous r ON c.idRdv = r.idRdv
    WHERE r.idPatient = ?
    ORDER BY o.dateOrdonnance DESC
");
$stmt->execute([$patientId]);
$ordonnances = $stmt->fetchAll();

// Récupérer les résultats du patient
$stmt = $conn->prepare("
    SELECT r.*, c.diagnostic, c.traitement
    FROM resultat r
    JOIN consultation c ON r.idConsultation = c.idConsultation
    JOIN rendezvous rdv ON c.idRdv = rdv.idRdv
    WHERE rdv.idPatient = ?
    ORDER BY r.dateTest DESC
");
$stmt->execute([$patientId]);
$resultats = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Patient | Santé Connect+</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        
        .navbar { 
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); 
            color: white; 
            padding: 1rem 2rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .navbar h1 { font-size: 1.5rem; }
        .navbar a { color: white; text-decoration: none; padding: 0.6rem 1.2rem; background: #e74c3c; border-radius: 4px; }
        .navbar a:hover { background: #c0392b; }
        
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        
        .patient-header {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            border-left: 5px solid #e74c3c;
        }
        .patient-header h2 { color: #2c3e50; margin-bottom: 1rem; font-size: 1.8rem; }
        .patient-info { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-top: 1rem; }
        .info-item { background: #f8f9fa; padding: 1rem; border-radius: 4px; }
        .info-label { color: #7f8c8d; font-size: 0.9rem; font-weight: 500; }
        .info-value { color: #2c3e50; font-weight: 600; margin-top: 0.3rem; }
        
        .section { 
            background: white; 
            padding: 2rem; 
            border-radius: 8px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .section h3 { color: #2c3e50; margin-bottom: 1.5rem; font-size: 1.3rem; border-bottom: 2px solid #ecf0f1; padding-bottom: 0.75rem; }
        
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
        
        .btn {
            display: inline-block;
            padding: 0.6rem 1.2rem;
            background: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.95rem;
            transition: 0.3s;
            margin-right: 0.5rem;
        }
        .btn:hover { background: #c0392b; }
        .btn-success { background: #2ecc71; }
        .btn-success:hover { background: #27ae60; }
        
        @media (max-width: 768px) { .patient-info { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🏥 Santé Connect+</h1>
        <a href="dashboard_medecin.php?page=patients">Retour</a>
    </div>

    <div class="container">
        <div class="patient-header">
            <h2><?php echo htmlspecialchars($patient['prenomPatient'] . ' ' . $patient['nomPatient']); ?></h2>
            <div class="patient-info">
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($patient['email']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Téléphone</div>
                    <div class="info-value"><?php echo htmlspecialchars($patient['telephone']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Groupe sanguin</div>
                    <div class="info-value"><?php echo htmlspecialchars($patient['groupeSanguin']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date de naissance</div>
                    <div class="info-value"><?php echo date('d/m/Y', strtotime($patient['dateNaissance'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Sexe</div>
                    <div class="info-value"><?php echo $patient['sexe'] === 'M' ? 'Homme' : 'Femme'; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Adresse</div>
                    <div class="info-value"><?php echo htmlspecialchars($patient['adresse']); ?></div>
                </div>
            </div>
        </div>

        <div class="section">
            <h3>📅 Rendez-vous</h3>
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

        <div class="section">
            <h3>📋 Consultations</h3>
            <?php if (count($consultations) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Médecin</th>
                        <th>Diagnostic</th>
                        <th>Traitement</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($consultations as $c): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($c['dateConsultation'])); ?></td>
                        <td>Dr <?php echo htmlspecialchars($c['prenomMedecin'] . ' ' . $c['nomMedecin']); ?></td>
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

        <div class="section">
            <h3>💊 Ordonnances</h3>
            <?php if (count($ordonnances) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
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

        <div class="section">
            <h3>🔬 Résultats d'examens</h3>
            <?php if (count($resultats) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type de test</th>
                        <th>Résultat</th>
                        <th>Valeur normale</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultats as $r): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($r['dateTest'])); ?></td>
                        <td><?php echo htmlspecialchars($r['typeTest']); ?></td>
                        <td><?php echo htmlspecialchars(substr($r['resultat'], 0, 50)); ?></td>
                        <td><?php echo htmlspecialchars($r['valeurNormale']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="empty">Aucun résultat d'examen.</p>
            <?php endif; ?>
        </div>

        <div style="margin-bottom: 2rem;">
            <a href="schedule_rdv.php?patient=<?php echo $patient['idPatient']; ?>" class="btn btn-success">📅 Programmer un RDV</a>
            <a href="new_consultation.php?patient=<?php echo $patient['idPatient']; ?>" class="btn btn-success">🔍 Nouvelle consultation</a>
        </div>
    </div>
</body>
</html>
