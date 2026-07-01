<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] != 'medecin') { 
    header('Location: index.php'); 
    exit(); 
}

require 'db.php';

$success = "";
$erreur = "";
$patientId = isset($_GET['patient']) ? $_GET['patient'] : '';

// Récupérer les patients du médecin
$stmt = $conn->prepare("SELECT * FROM medecin WHERE idMedecin = ?");
$stmt->execute([$_SESSION['id']]);
$medecin = $stmt->fetch();

$patientId = isset($_GET['patient']) ? (int)$_GET['patient'] : 0;

$stmt = $conn->prepare("
    SELECT DISTINCT p.* 
    FROM patient p
    JOIN rendezvous r ON p.idPatient = r.idPatient
    WHERE r.idService = ?
    ORDER BY p.nomPatient
");
$stmt->execute([$medecin['idService']]);
$patients = $stmt->fetchAll();

// Récupérer les services
$stmt = $conn->prepare("SELECT * FROM service");
$stmt->execute();
$services = $stmt->fetchAll();

// Si formulaire soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient = $_POST['patient'];
    $dateRdv = $_POST['date_rdv'];
    $heureRdv = $_POST['heure_rdv'];
    $motif = trim($_POST['motif']);
    $idService = $_POST['service'];

    if (empty($patient) || empty($dateRdv) || empty($heureRdv) || empty($motif)) {
        $erreur = "Tous les champs obligatoires doivent être remplis.";
    } else {
        try {
            $stmt = $conn->prepare("
                INSERT INTO rendezvous (dateRdv, heureRdv, motif, statut, idPatient, idService)
                VALUES (?, ?, ?, 'programme', ?, ?)
            ");
            $stmt->execute([$dateRdv, $heureRdv, $motif, $patient, $idService]);
            
            $success = "Rendez-vous programmé avec succès !";
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
    <title>Programmer RDV | Santé Connect+</title>
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
        
        .container { max-width: 700px; margin: 3rem auto; padding: 2rem; }
        
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
        textarea { resize: vertical; min-height: 100px; }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #e74c3c;
            box-shadow: 0 0 5px rgba(231, 76, 60, 0.3);
        }
        
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        
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
            <h2>📅 Programmer un rendez-vous</h2>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <p><a href="schedule_rdv.php" class="back-link">Programmer un autre RDV</a> | 
                   <a href="dashboard_medecin.php?page=rendezvous" class="back-link">Voir mes RDV</a></p>
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
                                <option value="<?php echo $p['idPatient']; ?>" <?php echo $p['idPatient'] == $patientId ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['prenomPatient'] . ' ' . $p['nomPatient']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Service *</label>
                        <select name="service" required>
                            <option value="">-- Sélectionner un service --</option>
                            <?php foreach ($services as $s): ?>
                                <option value="<?php echo $s['idService']; ?>"><?php echo htmlspecialchars($s['nomService']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Date *</label>
                            <input type="date" name="date_rdv" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Heure *</label>
                            <input type="time" name="heure_rdv" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Motif *</label>
                        <textarea name="motif" required placeholder="Raison de la consultation..."></textarea>
                    </div>

                    <button type="submit" class="btn">Programmer le rendez-vous</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
