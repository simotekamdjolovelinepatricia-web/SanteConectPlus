<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] != 'medecin') { 
    header('Location: index.php'); 
    exit(); 
}

require 'db.php';

$success = "";
$erreur = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $dateNaissance = $_POST['date_naissance'];
    $sexe = $_POST['sexe'];
    $adresse = trim($_POST['adresse']);
    $groupeSanguin = $_POST['groupe_sanguin'];

    // Validation
    if (empty($nom) || empty($prenom) || empty($email) || empty($dateNaissance) || empty($sexe)) {
        $erreur = "Les champs obligatoires doivent être remplis.";
    } else {
        try {
            // Vérifier si l'email existe déjà
            $stmt = $conn->prepare("SELECT COUNT(*) FROM patient WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $erreur = "Cet email est déjà utilisé.";
            } else {
                // Mot de passe par défaut du patient
                $defaultPassword = '1234';
                $hash = password_hash($defaultPassword, PASSWORD_DEFAULT);
                // Insérer le patient
                $stmt = $conn->prepare("
                    INSERT INTO patient (nomPatient, prenomPatient, email, motDePasse, telephone, adresse, dateNaissance, sexe, groupeSanguin, dateCreationDossier)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
                ");
                $stmt->execute([$nom, $prenom, $email, $hash, $telephone, $adresse, $dateNaissance, $sexe, $groupeSanguin]);
                
                $success = "Patient créé avec succès ! Le mot de passe par défaut est : 1234";
            }
        } catch (PDOException $e) {
            $erreur = "Erreur : " . $e->getMessage();
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM medecin WHERE idMedecin = ?");
$stmt->execute([$_SESSION['id']]);
$medecin = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter Patient | Santé Connect+</title>
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
        .navbar h1 { font-size: 1.5rem; font-weight: 600; }
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
            <h2>➕ Ajouter un nouveau patient</h2>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <p><a href="add_patient.php" class="back-link">Ajouter un autre patient</a> | 
                   <a href="dashboard_medecin.php?page=patients" class="back-link">Voir mes patients</a></p>
            <?php else: ?>
                <?php if ($erreur): ?>
                    <div class="alert alert-danger"><?php echo $erreur; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nom *</label>
                            <input type="text" name="nom" required>
                        </div>
                        <div class="form-group">
                            <label>Prénom *</label>
                            <input type="text" name="prenom" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Téléphone</label>
                            <input type="tel" name="telephone">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Date de naissance *</label>
                            <input type="date" name="date_naissance" required>
                        </div>
                        <div class="form-group">
                            <label>Sexe *</label>
                            <select name="sexe" required>
                                <option value="">-- Sélectionner --</option>
                                <option value="M">Homme</option>
                                <option value="F">Femme</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Adresse</label>
                        <textarea name="adresse"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Groupe sanguin</label>
                        <select name="groupe_sanguin">
                            <option value="">-- Sélectionner --</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                    </div>

                    <div class="alert alert-success" style="background:#eaf4ff; color:#1e5a8a; border-color:#b7d8f7;">
                        Le mot de passe du patient sera automatiquement défini à <strong>1234</strong>.
                    </div>

                    <button type="submit" class="btn">Créer le patient</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
