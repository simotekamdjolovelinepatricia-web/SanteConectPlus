<?php
// dashboard.php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php'); // Si pas connecté, on renvoie à la connexion
    exit();
}

// Déconnexion
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord</title>
</head>
<body>
    <h1>Tableau de bord</h1>
    <p>Bienvenue sur ton espace de gestion.</p>
    <a href="dashboard.php?logout=true">Se déconnecter</a>
</body>
</html>
