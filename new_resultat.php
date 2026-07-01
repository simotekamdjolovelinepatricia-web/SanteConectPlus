<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] != 'medecin') { 
    header('Location: index.php'); 
    exit(); 
}

require 'db.php';

$success = "";
$erreur = "";

// Récupérer les consultations du médecin
$stmt = $conn->prepare("
    SELECT c.*, p.prenomPatient, p.nomPatient, r.dateRdv, r.heureRdv
    FROM consultation c
    JOIN rendezvous r ON c.idRdv = r.idRdv
    JOIN patient p ON r.idPatient = p.idPatient
    WHERE c.idMedecin = ?
    ORDER BY c.dateConsultation DESC
");
$stmt->execute([$_SESSION['id']]);
$consultations = $stmt->fetchAll();

// Si formulaire soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $consultationId = $_POST['consultation'];
    $typeTest = trim($_POST['type_test']);
    $resultat = trim($_POST['resultat']);
    $valeurNormale = trim($_POST['valeur_normale']);

    if (empty($consultationId) || empty($typeTest) || empty($resultat)) {
        $erreur = "Les champs obligatoires doivent être remplis.";
    } else {
        try {
            $stmt = $conn->prepare("
                INSERT INTO resultat (typeTest, resultat, valeurNormale, dateTest, idConsultation)
                VALUES (?, ?, ?, CURDATE(), ?)
            ");
            $stmt->execute([$typeTest, $resultat, $valeurNormale, $consultationId]);
            
            $success = "Résultat d'examen créé avec succès !";
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
    <title>Ajouter Résultat | Santé Connect+</title>
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
            <h2>🔬 Ajouter un résultat d'examen</h2>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <p><a href="new_resultat.php" class="back-link">Ajouter un autre résultat</a> | 
                   <a href="dashboard_medecin.php" class="back-link">Retour au dashboard</a></p>
            <?php else: ?>
                <?php if ($erreur): ?>
                    <div class="alert alert-danger"><?php echo $erreur; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Consultation *</label>
                        <select name="consultation" required>
                            <option value="">-- Sélectionner une consultation --</option>
                            <?php foreach ($consultations as $c): ?>
                                <option value="<?php echo $c['idConsultation']; ?>">
                                    <?php echo htmlspecialchars($c['prenomPatient'] . ' ' . $c['nomPatient']); ?> - 
                                    <?php echo date('d/m/Y', strtotime($c['dateConsultation'])); ?> - 
                                    <?php echo htmlspecialchars(substr($c['diagnostic'], 0, 40)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Type de test *</label>
                        <input type="text" name="type_test" required placeholder="Ex: Analyse sanguine, Radiographie, ECG, etc.">
                    </div>

                    <div class="form-group">
                        <label>Résultat *</label>
                        <textarea name="resultat" required placeholder="Détails du résultat..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Valeur normale</label>
                        <input type="text" name="valeur_normale" placeholder="Ex: 70-100 mg/dl">
                    </div>

                    <button type="submit" class="btn">Enregistrer le résultat</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
