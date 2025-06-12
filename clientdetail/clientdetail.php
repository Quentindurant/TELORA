<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../database/db.php");
require_once("../database/postes_request.php");
include '../database/clients_request.php';
include '../database/utilisateurs_request.php';
include '../classes/provisionning.php';

if (!isset($pdo)) {
    die("Erreur de connexion à la base de données.");
}

///////////////////// Vérification des rôles ///////////////////
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Partenaire', 'Client'])) {
    header('Location: ../login/login.php');
    exit;
}

// Récupération de l'ID client
if (isset($_GET['idclient']))
    $idclient = $_GET['idclient'];
if (isset($_POST['idclient']))
    $idclient = $_POST['idclient'];

// vérification stricte pour le rôle "Client"
if ($_SESSION['role'] === 'Client') {
    // Si l'id demandé n'est pas celui de la session, on bloque
    if (!isset($_SESSION['client_id']) || $idclient != $_SESSION['client_id']) {
        header('Location: ../login/login.php');
        exit;
    }
    // On force l'id utilisé dans le code à celui de la session
    $idclient = $_SESSION['client_id'];
}
// Récupération du nom du client pour l'affichage dans le header
$clientInfo = null;
if (isset($idclient)) {
    $stmt = $pdo->prepare("SELECT Nom FROM Clients WHERE idclients = ?");
    $stmt->execute([$idclient]);
    $clientInfo = $stmt->fetch(PDO::FETCH_ASSOC);
}



// Récupération et mise à jour de l'ID partenaire dans la session
if (isset($idclient)) {
    $stmt = $pdo->prepare("SELECT partenaires_idpartenaires FROM Clients WHERE idclients = ?");
    $stmt->execute([$idclient]);
    $client = $stmt->fetch();
    if ($client) {
        $_SESSION['partner_id'] = $client['partenaires_idpartenaires'];
        error_log("[clientdetail.php] Updated partner_id in session: " . $client['partenaires_idpartenaires']);
    }
}

// Vérification pour un partenaire ou un client
if ($_SESSION['role'] === 'Partenaire') {
    // Le partenaire ne peut accéder qu'aux détails des clients associés à son partenaire ID
    if (!isset($idclient) || !in_array($idclient, getClientsForPartner($_SESSION['partner_id']))) {
        header('Location: ../login/login.php');
        exit;
    }
} elseif ($_SESSION['role'] === 'Client') {
    // Le client ne peut accéder qu'à son propre détail
    if (!isset($idclient) || $idclient != $_SESSION['client_id']) {
        header('Location: ../login/login.php');
        exit;
    }
}

// Fonction pour récupérer les IDs des clients pour un partenaire
function getClientsForPartner($partnerId)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT idclients FROM Clients WHERE partenaires_idpartenaires = :partnerId");
    $stmt->bindParam(':partnerId', $partnerId, PDO::PARAM_INT);
    $stmt->execute();
    return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'idclients');
}
///////////////////// FIN vérif des rôles ///////////////////

// Instanciation des objets nécessaires
$UtilisateursForm = new ShowUtilisateursForm($pdo);
$TypePostesForm = new ShowTypePostesForm($pdo);

if (isset($_GET['idclient']))
    $idclient = $_GET['idclient'];
if (isset($_POST['idclient']))
    $idclient = $_POST['idclient'];

// Gestion des actions POST
if (isset($_POST['DeleteUser'])) {
    $UtilisateursForm->UtilisateursDelete($_POST['idutilisateur']);
}

if (isset($_POST['AutoBLF'])) {
    $utilisateurs = $UtilisateursForm->UtilisateursRecoveryByClient($idclient);
    foreach ($utilisateurs as $utilisateur) {
        $Provisionning->AutoBLF($UtilisateursForm, $utilisateur["idutilisateurs"]);
    }
}

// Pagination
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$perPage = isset($_GET['perPage']) ? ($_GET['perPage'] === 'all' ? PHP_INT_MAX : (int) $_GET['perPage']) : 10;

// Récupérer le nombre total d'utilisateurs d'abord
$totalUsers = $UtilisateursForm->CountUtilisateursByClient($idclient);

// Calculer l'offset et le nombre de pages
$offset = ($page - 1) * ($perPage === PHP_INT_MAX ? $totalUsers : $perPage);
$totalPages = $perPage === PHP_INT_MAX ? 1 : ceil($totalUsers / $perPage);

// Récupérer les utilisateurs pour la page courante
if ($perPage === PHP_INT_MAX) {
    $utilisateurs = $UtilisateursForm->UtilisateursRecoveryByClient($idclient, "Extension ASC");
} else {
    $utilisateurs = $UtilisateursForm->UtilisateursRecoveryByClientPaginated($idclient, "Extension ASC", $offset, $perPage);
}

if (isset($_POST['EditClient'])) {
    //Mise à jour des informations du client

    if ($_POST["EditNom"] != "")
        $nom = $_POST["EditNom"];
    else
        $nom = "";
    if ($_POST["EditEmail"] != "")
        $mail = $_POST["EditEmail"];
    else
        $mail = "";
    if ($_POST["EditTelephone"] != "")
        $tel = $_POST["EditTelephone"];
    else
        $tel = "";
    if ($_POST["EditAdresse"] != "")
        $adresse = $_POST["EditAdresse"];
    else
        $adresse = "";
    if ($_POST["EditPlateforme"] != "")
        $plateforme = $_POST["EditPlateforme"];
    else
        $plateforme = "";
    if ($_POST["EditPlateformeURL"] != "")
        $plateformeurl = $_POST["EditPlateformeURL"];
    else
        $plateformeurl = "";

    $ClientsForm->ClientsUpdate($idclient, $nom, $mail, $tel, $adresse, $plateforme, $plateformeurl);

}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telora - Détail Client</title>
    <link rel="icon" type="image/png" href="../admin/logo/Logo-ldap.png">
    <link rel="shortcut icon" type="image/png" href="../admin/logo/Logo-ldap.png">
    <link rel="stylesheet" href="clientdetail.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>

<body>
    <!-- Contenu principal -->
    <main class="main-content">
        <?php include '../partials/V2/headV2.php'; ?>
        <div class="clientdetail-container">
            <header class="main-header">
                <h1>Administration - <?php echo htmlspecialchars($clientInfo['Nom']); ?></h1>
            </header>

            <div class="header-actions">
                <div class="search-filters">
                    <input type="text" class="search-input" placeholder="rechercher un utilisateur..." id="searchInput"
                        autocomplete="off">
                    <div id="searchResults" class="search-results"></div>
                </div>
                <div class="action-group">
                    <a href="../annuaire/annuaire.php?idclients=<?php echo $idclient; ?>"
                        class="btn-action btn-secondary">
                        Gérer l'annuaire
                    </a>
                    <button class="btn-action btn-primary"
                        onclick="document.getElementById('addUserModal').style.display='block'">Nouveau</button>
                </div>
            </div>

            <div style="padding: 0 20px;">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Type Poste</th>
                            <th>Statut</th>
                            <th>MAC</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($utilisateurs as $utilisateur):
                            $initials = strtoupper(substr($utilisateur['Nom'], 0, 2));
                            ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar"><?php echo $initials; ?></div>
                                        <div class="user-details">
                                            <div class="user-name"><?php echo htmlspecialchars($utilisateur['Nom']); ?>
                                            </div>
                                            <div class="user-extension">
                                                <?php echo htmlspecialchars($utilisateur['Extension']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="user-details">
                                        <div class="user-name"><?php echo htmlspecialchars($utilisateur['TypePoste']); ?>
                                        </div>
                                        <div class="user-extension">
                                            <?php echo htmlspecialchars($utilisateur['SIPServeur']); ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-active">Actif</span>
                                </td>
                                <td><?php echo htmlspecialchars($utilisateur['AdresseMAC']); ?></td>
                                <td class="">
                                    <div class="action-buttons">
                                        <form method="POST" action="../utilisateurdetail/utilisateurdetail.php"
                                            style="display:inline;">
                                            <input type="hidden" name="idutilisateur"
                                                value="<?php echo htmlspecialchars($utilisateur['idutilisateurs']); ?>">
                                            <input type="hidden" name="idclient"
                                                value="<?php echo htmlspecialchars($idclient); ?>">
                                            <button type="submit" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="idutilisateur"
                                                value="<?php echo htmlspecialchars($utilisateur['idutilisateurs']); ?>">
                                            <button type="submit" class="text-red-500 hover:text-red-700"
                                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="pagination">
                    <div class="pagination-info">
                        <div class="items-per-page">
                            <select onchange="changePerPage(this.value)">
                                <option value="10" <?php echo $perPage == 10 ? 'selected' : ''; ?>>10 entrées</option>
                                <option value="25" <?php echo $perPage == 25 ? 'selected' : ''; ?>>25 entrées</option>
                                <option value="50" <?php echo $perPage == 50 ? 'selected' : ''; ?>>50 entrées</option>
                                <option value="100" <?php echo $perPage == 100 ? 'selected' : ''; ?>>100 entrées</option>
                                <option value="all" <?php echo $perPage === PHP_INT_MAX ? 'selected' : ''; ?>>Tous
                                </option>
                            </select>
                        </div>
                        <div class="showing-entries">
                            <?php if ($perPage === PHP_INT_MAX): ?>
                                Affichage de tous les <?php echo $totalUsers; ?> utilisateurs
                            <?php else: ?>
                                Affichage <?php echo $totalUsers > 0 ? ($offset + 1) : 0; ?> à
                                <?php echo min($offset + $perPage, $totalUsers); ?> sur <?php echo $totalUsers; ?>
                                utilisateurs
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($perPage !== PHP_INT_MAX): ?>
                        <div class="pagination-controls">
                            <?php if ($page > 1): ?>
                                <a href="?idclient=<?php echo $idclient; ?>&page=1&perPage=<?php echo $perPage; ?>"
                                    class="pagination-button">«</a>
                                <a href="?idclient=<?php echo $idclient; ?>&page=<?php echo ($page - 1); ?>&perPage=<?php echo $perPage; ?>"
                                    class="pagination-button">‹</a>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end = min($totalPages, $page + 2);

                            for ($i = $start; $i <= $end; $i++): ?>
                                <a href="?idclient=<?php echo $idclient; ?>&page=<?php echo $i; ?>&perPage=<?php echo $perPage; ?>"
                                    class="pagination-button <?php echo ($i === $page) ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?idclient=<?php echo $idclient; ?>&page=<?php echo ($page + 1); ?>&perPage=<?php echo $perPage; ?>"
                                    class="pagination-button">›</a>
                                <a href="?idclient=<?php echo $idclient; ?>&page=<?php echo $totalPages; ?>&perPage=<?php echo $perPage; ?>"
                                    class="pagination-button">»</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div style="padding: 20px;">
                <?php
                // Correction : s'assurer que $client est bien défini et recharger si besoin
                if (!isset($client) || !isset($client['partenaires_idpartenaires'])) {
                    if (isset($idclient)) {
                        $stmt = $pdo->prepare("SELECT partenaires_idpartenaires FROM Clients WHERE idclients = ?");
                        $stmt->execute([$idclient]);
                        $client = $stmt->fetch();
                    }
                }
                echo "<form method='POST' action=\"../clientlist/clientlist.php\" style='display:inline;'>
                    <input type='hidden' name='idpartenaire' value='" . (isset($client['partenaires_idpartenaires']) ? htmlspecialchars($client['partenaires_idpartenaires']) : "") . "'>
                    <button name='RetourArriere' class='back-button' type='submit'>Revenir en arrière</button>
                </form>";
                ?>
            </div>
        </div>
    </main>

    <!-- Modal d'ajout d'utilisateur -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('addUserModal').style.display='none'">&times;</span>
            <h2 class="adduser-modal-title">Ajouter un utilisateur</h2>
            <form method="POST" action="clientdetail.php">
                <input type="hidden" name="idclient" value="<?php echo $idclient; ?>">

                <div class="form-group">
                    <label for="nom">Nom :</label>
                    <input type="text" id="nom" name="EditNom" required>
                </div>

                <div class="form-group">
                    <label for="extension">Extension :</label>
                    <input type="text" id="extension" name="EditExtension" required>
                </div>

                <div class="form-group">
                    <label for="typeposte">Type de poste :</label>
                    <select id="typeposte" name="EditTypePoste">
                        <?php
                        $typespostes = $TypePostesForm->TypePostesRecovery();
                        foreach ($typespostes as $type) {
                            echo "<option value=\"" . htmlspecialchars($type['TypePoste']) . "\">" . htmlspecialchars($type['TypePoste']) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="mac">Adresse MAC :</label>
                    <input type="text" id="mac" name="EditMAC">
                </div>

                <div class="form-group">
                    <label for="sn">N° série :</label>
                    <input type="text" id="sn" name="EditSN">
                </div>

                <div class="form-group">
                    <label for="sipserver">SIP Serveur :</label>
                    <input type="text" id="sipserver" name="EditSIPServeur">
                </div>

                <div class="form-group">
                    <label for="siplogin">SIP Login :</label>
                    <input type="text" id="siplogin" name="EditSIPLogin">
                </div>

                <div class="form-group">
                    <label for="sippassword">SIP Password :</label>
                    <input type="password" id="sippassword" name="EditSIPPassword">
                </div>

                <div class="form-actions">
                    <button type="submit" name="NewUser" class="btn-action btn-primary">Enregistrer</button>
                    <button type="button" class="btn-action btn-secondary"
                        onclick="document.getElementById('addUserModal').style.display='none'">Annuler</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function changePerPage(value) {
            const url = new URL(window.location.href);
            url.searchParams.set('perPage', value);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        }

        // Fonction pour échapper les caractères HTML
        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Recherche en temps réel
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('searchInput');
            const searchResults = document.getElementById('searchResults');
            let debounceTimer;

            // Fonction pour mettre à jour les résultats de recherche
            async function updateSearchResults(query) {
                console.log('Recherche pour:', query);
                if (query.length === 0) {
                    searchResults.style.display = 'none';
                    return;
                }

                try {
                    const url = `../api/search_users.php?query=${encodeURIComponent(query)}&idclient=<?php echo $idclient; ?>`;
                    console.log('URL de recherche:', url);

                    const response = await fetch(url);
                    console.log('Statut de la réponse:', response.status);

                    const data = await response.json();
                    console.log('Données reçues:', data);

                    if (data.error) {
                        throw new Error(data.error);
                    }

                    if (!Array.isArray(data) || data.length === 0) {
                        searchResults.innerHTML = '<div class="search-result-item">Aucun résultat trouvé</div>';
                        searchResults.style.display = 'block';
                        return;
                    }

                    let html = '';
                    data.forEach(user => {
                        const initials = user.Nom.substring(0, 2).toUpperCase();
                        html += `
                        <div class="search-result-item" onclick="openUser(${user.idutilisateur}, '${escapeHtml(user.Nom)}')">
                            <div class="search-result-avatar">${escapeHtml(initials)}</div>
                            <div class="search-result-info">
                                <div class="search-result-name">${escapeHtml(user.Nom)}</div>
                                <div class="search-result-extension">${escapeHtml(user.Extension)}</div>
                            </div>
                        </div>
                    `;
                    });

                    searchResults.innerHTML = html;
                    searchResults.style.display = 'block';
                } catch (error) {
                    console.error('Erreur:', error);
                    searchResults.innerHTML = `<div class="search-result-item">Erreur: ${error.message}</div>`;
                    searchResults.style.display = 'block';
                }
            }

            // Gestionnaire d'événement pour la saisie
            searchInput.addEventListener('input', function () {
                console.log('Saisie détectée:', this.value);
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    updateSearchResults(this.value);
                }, 300);
            });

            // Fonction pour ouvrir la modification d'un utilisateur
            window.openUser = function (userId, userName) {
                window.location.href = `../utilisateurdetail/utilisateurdetail.php?idutilisateur=${userId}&idclient=<?php echo $idclient; ?>`;
            };

            // Fermer les résultats quand on clique ailleurs
            document.addEventListener('click', function (e) {
                if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.style.display = 'none';
                }
            });

            // Filtrer le tableau
            window.filterTable = function (name) {
                const rows = document.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const userName = row.querySelector('.user-name').textContent;
                    row.style.display = userName.toLowerCase().includes(name.toLowerCase()) ? '' : 'none';
                });
                searchResults.style.display = 'none';
                searchInput.value = name;
            };

            // Gestionnaire d'événement pour la fermeture de la modale
            const addUserModal = document.getElementById('addUserModal');
            const closeModalButton = addUserModal.querySelector('.close');
            closeModalButton.addEventListener('click', function () {
                addUserModal.style.display = 'none';
            });

            // Gestionnaire d'événement pour l'annulation de la modale
            const cancelAddUserButton = addUserModal.querySelector('.cancel');
            cancelAddUserButton.addEventListener('click', function () {
                addUserModal.style.display = 'none';
            });
        });
    </script>
</body>

</html>