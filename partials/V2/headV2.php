<?php
// Vérification des rôles
if (!isset($_SESSION['role'])) {
    header('Location: ../login/login.php');
    exit;
}
$role = $_SESSION['role'];

// Récupération des IDs de la session
$partnerId = $_SESSION['partner_id'] ?? null;
$clientId = $_SESSION['client_id'] ?? null;

// Récupérer l'ID client depuis GET si disponible
if (isset($_GET['idclients'])) {
    $_SESSION['client_id'] = intval($_GET['idclients']);
    $clientId = $_SESSION['client_id'];
}

// Détecter la page actuelle
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$showClientButton = in_array($currentPage, ['clientdetail.php', 'annuaire.php']);
?>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UI Example</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../partials/V2/css/header.css">
    <!-- <link rel="stylesheet" href="styles.scss"> -->
</head>
<header>

    <nav class="header-nav-v2">
        <div class="header-nav-content">

            <div class="logo-title-group">
                <img src="../admin/logo/Logo-ldap.png" alt="Logo" class="header-logo-header">
                <span class="telora-title-header">TELORA</span>
            </div>
            <ul class="header-nav-links">
                <?php if ($role === 'Admin'): ?>
                    <li><a href="../admin/V1_admin.php" class="menu"><i class="fas fa-building"></i> Partenaires</a></li>
                    <li><a href="../clientlist/clientlist.php<?= $partnerId ? '?idpartenaires=' . htmlspecialchars($partnerId) : '' ?>"
                            class="menu"><i class="fas fa-users"></i> Clients</a></li>
                    <?php if ($clientId && $showClientButton): ?>
                        <li><a href="../clientdetail/clientdetail.php?idclient=<?= htmlspecialchars($clientId) ?>"
                                class="menu"><i class="fas fa-user"></i> Retour client</a></li>
                    <?php endif; ?>
                <?php elseif ($role === 'Partenaire'): ?>
                    <li><a href="../clientlist/clientlist.php?idpartenaires=<?= htmlspecialchars($_SESSION['partner_id']) ?>"
                            class="menu"><i class="fas fa-users"></i> Clients</a></li>
                    <?php if ($clientId && $showClientButton): ?>
                        <li><a href="../clientdetail/clientdetail.php?idclient=<?= htmlspecialchars($clientId) ?>"
                                class="menu"><i class="fas fa-user"></i> Retour client</a></li>
                    <?php endif; ?>
                <?php elseif ($role === 'Client'): ?>
                    <?php if ($showClientButton): ?>
                        <li><a href="../clientdetail/clientdetail.php?idclient=<?= htmlspecialchars($_SESSION['client_id']) ?>"
                                class="menu"><i class="fas fa-home"></i> Accueil</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            <a href="../login/logout.php" class="logout-btn btn-orange"><i class="fas fa-sign-out-alt"></i>
                Déconnexion</a>
        </div>
    </nav>
</header>