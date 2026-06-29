<?php 
require 'db.php';

// Si déjà connecté, rediriger
if (isset($_SESSION['user'])) {
    if ($_SESSION['role'] == 'medecin') { 
        header('Location: dashboard_medecin.php'); 
        exit();
    } else { 
        header('Location: dashboard_patient.php'); 
        exit();
    }
}

$erreur = "";
$success = "";

// Traitement de l'inscription
if (isset($_POST['inscription'])) {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $telephone = trim($_POST['telephone']);
    $adresse = trim($_POST['adresse']);
    $dateNaissance = $_POST['date_naissance'];
    $sexe = $_POST['sexe'];
    $groupeSanguin = $_POST['groupe_sanguin'];
    $role = $_POST['role_inscription'];

    // Validation
    if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
        $erreur = "Tous les champs obligatoires doivent être remplis.";
    } elseif ($password !== $confirm_password) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 2) {
        $erreur = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        try {
            if ($role == 'medecin') {
                // Vérifier si l'email existe déjà chez les médecins
                $stmt = $conn->prepare("SELECT COUNT(*) FROM medecin WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetchColumn() > 0) {
                    $erreur = "Cet email est déjà utilisé par un médecin.";
                } else {
                    // Hacher le mot de passe
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    // Insérer le médecin (avec un service par défaut = 1)
                    $stmt = $conn->prepare("
                        INSERT INTO medecin (nomMedecin, prenomMedecin, email, motDePasse, telephone, idService, numOrdre)
                        VALUES (?, ?, ?, ?, ?, 1, ?)
                    ");
                    $numOrdre = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
                    $stmt->execute([$nom, $prenom, $email, $hash, $telephone, $numOrdre]);
                    
                    $success = "Compte médecin créé avec succès ! Vous pouvez maintenant vous connecter.";
                    $_SESSION['inscription_success'] = $success;
                }
            } else {
                // Vérifier si l'email existe déjà chez les patients
                $stmt = $conn->prepare("SELECT COUNT(*) FROM patient WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetchColumn() > 0) {
                    $erreur = "Cet email est déjà utilisé par un patient.";
                } else {
                    // Hacher le mot de passe
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    // Insérer le patient
                    $stmt = $conn->prepare("
                        INSERT INTO patient (nomPatient, prenomPatient, email, motDePasse, telephone, adresse, dateNaissance, sexe, groupeSanguin)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$nom, $prenom, $email, $hash, $telephone, $adresse, $dateNaissance, $sexe, $groupeSanguin]);
                    
                    $success = "Compte patient créé avec succès ! Vous pouvez maintenant vous connecter.";
                    $_SESSION['inscription_success'] = $success;
                }
            }
        } catch (PDOException $e) {
            $erreur = "Erreur lors de l'inscription : " . $e->getMessage();
        }
    }
}

// Traitement de la connexion
if (isset($_POST['connexion'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    if ($role == 'medecin') {
        session_start();
        $stmt = $conn->prepare("SELECT * FROM medecin WHERE email = ? or telephone = ?");
        $stmt->execute([$email, $email]);
        $utilisateur = $stmt->fetch();
      

        if ($utilisateur && password_verify($password, $utilisateur['motDePasse'])) {

            // var_dump($utilisateur);  

            $_SESSION['user'] = $utilisateur['prenomMedecin'] . ' ' . $utilisateur['nomMedecin'];
            $_SESSION['id'] = $utilisateur['idMedecin'];
            $_SESSION['role'] = 'medecin';

            header('Location: dashboard_medecin.php');

            exit();

        } else {

            $erreur = "Email ou mot de passe incorrect pour le médecin.";

        }

    } else {
        session_start();
        $stmt = $conn->prepare("SELECT * FROM patient WHERE email = ? or telephone = ?");
        $stmt->execute([$email, $email]);
        $utilisateur = $stmt->fetch();
        
        if ($utilisateur && password_verify($password, $utilisateur['motDePasse'])) {
            $_SESSION['user'] = $utilisateur['prenomPatient'] . ' ' . $utilisateur['nomPatient'];
            $_SESSION['id'] = $utilisateur['idPatient'];
            $_SESSION['role'] = 'patient';
            header('Location: dashboard_patient.php');
            exit();
        } else {
            $erreur = "Email ou mot de passe incorrect pour le patient.";
        }
    }
}

// Récupérer le message de succès si présent
if (isset($_SESSION['inscription_success'])) {
    $success = $_SESSION['inscription_success'];
    unset($_SESSION['inscription_success']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Santé Connect+ | Connexion & Inscription</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 450px;
            max-width: 100%;
            padding: 2rem;
            position: relative;
        }
        .container h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }
        .container .subtitle {
            text-align: center;
            color: #7f8c8d;
            margin-bottom: 1.5rem;
        }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 0.5rem;
        }
        .tab-btn {
            flex: 1;
            padding: 10px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            color: #7f8c8d;
            transition: all 0.3s;
            border-radius: 8px;
        }
        .tab-btn.active {
            color: #667eea;
            background: #f0f2ff;
        }
        .tab-btn:hover {
            background: #f0f2ff;
            color: #667eea;
        }
        .form {
            display: none;
        }
        .form.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.3rem;
            color: #555;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: border-color 0.3s;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-success {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.4);
        }
        .error {
            background: #fde8e8;
            color: #e74c3c;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #e74c3c;
            font-size: 0.9rem;
        }
        .success {
            background: #e8f8e8;
            color: #27ae60;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #27ae60;
            font-size: 0.9rem;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        @media (max-width: 480px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        .demo-info {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 0.85rem;
            color: #555;
        }
        .demo-info strong {
            color: #2c3e50;
        }
        .demo-info p {
            margin: 3px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🏥 Santé Connect+</h2>
        <p class="subtitle">Gestion des rendez-vous médicaux</p>

        <?php if ($erreur): ?>
            <div class="error"><?php echo htmlspecialchars($erreur); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Onglets -->
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('connexion')">🔐 Connexion</button>
            <button class="tab-btn" onclick="showTab('inscription')">📝 Inscription</button>
        </div>

        <!-- Formulaire de Connexion -->
        <div id="connexion" class="form active">
            <form method="post">
                <div class="form-group">
                    <label for="role">Je suis :</label>
                    <select name="role" id="role" required>
                        <option value="patient">Patient</option>
                        <option value="medecin">Médecin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="email">Email ou telephone :</label>
                    <input type="text" name="email" placeholder="exemple@email.com" required>
                </div>
                <div class="form-group">
                    <label for="password">Mot de passe :</label>
                    <input type="password" name="password" placeholder="Votre mot de passe" required>
                </div>
                <button type="submit" name="connexion" class="btn btn-primary">Se connecter</button>
            </form>
            <div class="demo-info">
                <strong>🔑 Comptes de test :</strong>
                <p>👨‍⚕️ Médecin : fankem.michael@hopital.cm / motdepasse123</p>
                <p>🧑‍⚕️ Patient : cyrias.talla@gmail.com / motdepasse123</p>
            </div>
        </div>

        <!-- Formulaire d'Inscription -->
        <div id="inscription" class="form">
            <form method="post">
                <div class="form-group">
                    <label for="role_inscription">Je veux créer un compte :</label>
                    <select name="role_inscription" id="role_inscription" required>
                        <option value="patient">Patient</option>
                        <option value="medecin">Médecin</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="nom">Nom *</label>
                        <input type="text" name="nom" placeholder="Votre nom" required>
                    </div>
                    <div class="form-group">
                        <label for="prenom">Prénom *</label>
                        <input type="text" name="prenom" placeholder="Votre prénom" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" name="email" placeholder="exemple@email.com" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Mot de passe *</label>
                        <input type="password" name="password" placeholder="Min 6 caractères" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirmer *</label>
                        <input type="password" name="confirm_password" placeholder="Confirmez" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="telephone">Téléphone</label>
                    <input type="tel" name="telephone" placeholder="06 12 34 56 78">
                </div>
                <div class="form-group">
                    <label for="adresse">Adresse</label>
                    <input type="text" name="adresse" placeholder="Votre adresse">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_naissance">Date de naissance</label>
                        <input type="date" name="date_naissance">
                    </div>
                    <div class="form-group">
                        <label for="sexe">Sexe</label>
                        <select name="sexe">
                            <option value="M">Masculin</option>
                            <option value="F">Féminin</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="groupe_sanguin">Groupe sanguin</label>
                    <select name="groupe_sanguin">
                        <option value="">Sélectionner</option>
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
                <button type="submit" name="inscription" class="btn btn-success">Créer mon compte</button>
            </form>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Cacher tous les formulaires
            document.querySelectorAll('.form').forEach(form => {
                form.classList.remove('active');
            });
            // Désactiver tous les boutons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            // Afficher le formulaire sélectionné
            document.getElementById(tabName).classList.add('active');
            // Activer le bouton correspondant
            event.target.classList.add('active');
        }
    </script>
</body>
</html>