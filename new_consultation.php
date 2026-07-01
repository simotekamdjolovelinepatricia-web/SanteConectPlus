<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] != 'medecin') { 
    header('Location: index.php'); 
    exit(); 
}

require 'db.php';

$success = "";
$erreur = "";
$patientId = isset($_GET['patient']) ? (int)$_GET['patient'] : 0;

// Récupérer les infos du médecin
$stmt = $conn->prepare("SELECT * FROM medecin WHERE idMedecin = ?");
$stmt->execute([$_SESSION['id']]);
$medecin = $stmt->fetch();

// Récupérer la liste des patients
$stmt = $conn->prepare("SELECT idPatient, prenomPatient, nomPatient FROM patient ORDER BY nomPatient, prenomPatient");
$stmt->execute();
$patients = $stmt->fetchAll();

// Patient sélectionné
$selectedPatientId = $patientId;

// Récupérer les RDV disponibles
if ($selectedPatientId > 0) {
    $stmt = $conn->prepare("
        SELECT r.*, p.prenomPatient, p.nomPatient 
        FROM rendezvous r
        JOIN patient p ON r.idPatient = p.idPatient
        WHERE r.idService = ? AND r.idPatient = ? AND r.statut IN ('programme', 'confirme') AND NOT EXISTS (
            SELECT 1 FROM consultation c WHERE c.idRdv = r.idRdv
        )
        ORDER BY r.dateRdv, r.heureRdv
    ");
    $stmt->execute([$medecin['idService'], $selectedPatientId]);
} else {
    $stmt = $conn->prepare("
        SELECT r.*, p.prenomPatient, p.nomPatient 
        FROM rendezvous r
        JOIN patient p ON r.idPatient = p.idPatient
        WHERE r.idService = ? AND r.statut IN ('programme', 'confirme') AND NOT EXISTS (
            SELECT 1 FROM consultation c WHERE c.idRdv = r.idRdv
        )
        ORDER BY r.dateRdv, r.heureRdv
    ");
    $stmt->execute([$medecin['idService']]);
}
$rdvs = $stmt->fetchAll();

// Si formulaire soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedPatientId = isset($_POST['patient']) ? (int)$_POST['patient'] : $patientId;
    $rdvId = isset($_POST['rdv']) ? trim($_POST['rdv']) : '';
    $diagnostic = trim($_POST['diagnostic']);
    $traitement = trim($_POST['traitement']);
    $observations = trim($_POST['observations']);

    if ($selectedPatientId <= 0) {
        $erreur = "Veuillez sélectionner un patient avant de créer une consultation.";
    } elseif (empty($diagnostic) || empty($traitement)) {
        $erreur = "Le diagnostic et le traitement sont obligatoires.";
    } else {
        try {
            if (!empty($rdvId)) {
                $selectedRdvId = $rdvId;
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO rendezvous (dateRdv, heureRdv, motif, statut, idPatient, idService)
                    VALUES (?, ?, ?, 'programme', ?, ?)
                ");
                $stmt->execute([
                    date('Y-m-d'),
                    date('H:i:s'),
                    'Consultation médicale',
                    $selectedPatientId,
                    $medecin['idService']
                ]);
                $selectedRdvId = $conn->lastInsertId();
            }

            if (empty($erreur) && !empty($selectedRdvId)) {
                $stmt = $conn->prepare("
                    INSERT INTO consultation (diagnostic, traitement, observations, dateConsultation, idMedecin, idRdv)
                    VALUES (?, ?, ?, CURDATE(), ?, ?)
                ");
                $stmt->execute([$diagnostic, $traitement, $observations, $_SESSION['id'], $selectedRdvId]);
                $success = "Consultation créée avec succès !";
            }
        } catch (PDOException $e) {
            $erreur = "Erreur : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Consultation | Santé Connect+</title>
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
        
        .container { max-width: 800px; margin: 3rem auto; padding: 2rem; }
        
        .form-card { 
            background: white; 
            padding: 2rem; 
            border-radius: 8px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 5px solid #e74c3c;
        }
        
        .form-card h2 { color: #2c3e50; margin-bottom: 1.5rem; font-size: 1.5rem; }
        
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; color: #2c3e50; font-weight: 500; }
        input, textarea, select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
            font-family: inherit;
            font-size: 0.95rem;
        }
        textarea { resize: vertical; min-height: 120px; }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #e74c3c;
            box-shadow: 0 0 5px rgba(231, 76, 60, 0.3);
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: 0.3s;
            width: 100%;
        }
        .btn:hover { background: #c0392b; }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            font-weight: 500;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .back-link { display: inline-block; margin-top: 1rem; color: #3498db; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🏥 Santé Connect+</h1>
        <a href="dashboard_medecin.php">Retour</a>
    </div>

    <div class="container">
        <div class="form-card">
            <h2>🔍 Créer une nouvelle consultation</h2>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <p><a href="new_consultation.php" class="back-link">Créer une autre consultation</a> | 
                   <a href="dashboard_medecin.php?page=consultations" class="back-link">Voir mes consultations</a></p>
            <?php else: ?>
                <?php if ($erreur): ?>
                    <div class="alert alert-danger"><?php echo $erreur; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Patient *</label>
                        <select name="patient" required>
                            <option value="">-- Sélectionner un patient --</option>
                            <?php foreach ($patients as $p): ?>
                                <option value="<?php echo $p['idPatient']; ?>" <?php echo $p['idPatient'] == $selectedPatientId ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['prenomPatient'] . ' ' . $p['nomPatient']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Rendez-vous *</label>
                        <select name="rdv">
                            <option value="">-- Nouveau RDV --</option>
                            <?php foreach ($rdvs as $r): ?>
                                <option value="<?php echo $r['idRdv']; ?>" <?php echo $r['idPatient'] == $selectedPatientId ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($r['prenomPatient'] . ' ' . $r['nomPatient']); ?> - 
                                    <?php echo date('d/m/Y', strtotime($r['dateRdv'])); ?> <?php echo substr($r['heureRdv'], 0, 5); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Diagnostic *</label>
                        <textarea name="diagnostic" required placeholder="Diagnostic établi..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Traitement *</label>
                        <textarea name="traitement" required placeholder="Traitement recommandé..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Observations</label>
                        <textarea name="observations" placeholder="Observations supplémentaires..."></textarea>
                    </div>

                    <button type="submit" class="btn">Créer la consultation</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
