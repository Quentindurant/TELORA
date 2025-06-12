<?php

// // Activation des logs d'erreur
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrage de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Includes de base
require_once '../database/db.php';
require_once '../utils/functions.php';
require_once '../utils/encryption.php';

// Includes spécifiques à l'annuaire
require_once '../database/Annuaire_request.php';
require_once '../database/clients_request.php';
require_once '../database/utilisateurs_request.php';

// Configuration et classes LDAP
require_once __DIR__ . '/../ldap/config/ldap_config.php';
require_once __DIR__ . '/../ldap/core/LDAPManager.php';
require_once __DIR__ . '/../ldap/core/TeloraLDAPSync.php';
require_once __DIR__ . '/../ldap/scripts/sync_triggers.php';

// Log pour le debug
error_log("annuaire.php - Tous les fichiers requis ont été chargés");

// // Vérification de l'authentification
if (!isset($_SESSION['role'])) {
    error_log("annuaire.php - Pas de session active, redirection vers login");
    header('Location: ../login/login.php');
    exit;
}

// Récupération des informations de session
$role = $_SESSION['role'];
$partnerId = $_SESSION['partner_id'] ?? null;

// Récupération de l'ID client
// Priorité à l'ID du formulaire POST, sinon prendre celui de l'URL
$clientsId = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idclients'])) {
    $clientsId = intval($_POST['idclients']);
} elseif (isset($_GET['idclients'])) {
    $clientsId = intval($_GET['idclients']);
}

if ($role === 'Client') {
    // Si l'id demandé n'est pas celui de la session, on bloque
    if (!isset($_SESSION['client_id']) || $clientsId != $_SESSION['client_id']) {
        header('Location: ../login/login.php');
        exit;
    }
    // Pour la suite du code, on force $clientsId à la valeur de la session (même si l'URL est trafiquée)
    $clientsId = $_SESSION['client_id'];
}
// Si on n'a pas d'ID client, on redirige selon le rôle
if (!$clientsId) {
    if ($role === 'Admin') {
        header('Location: ../admin/V1_admin.php');
    } elseif ($role === 'Partenaire') {
        header('Location: ../clientlist/clientlist.php');
    } else {
        header('Location: ../login/login.php');
    }
    exit;
}

// Mise à jour du contexte partenaire si on a un ID client
$stmt = $pdo->prepare("SELECT partenaires_idpartenaires FROM Clients WHERE idclients = ?");
$stmt->execute([$clientsId]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if ($client) {
    $partnerId = $client['partenaires_idpartenaires'];
    $_SESSION['partner_id'] = $partnerId;
    // $_SESSION['client_id'] = $clientsId;  // Important pour le contexte
    error_log("annuaire.php - Contexte mis à jour - PartnerId: $partnerId, ClientId: $clientsId");
} else {
    error_log("annuaire.php - Client non trouvé");
    header('Location: ../login/login.php');
    exit;
}

// Vérification des droits d'accès selon le rôle
if ($role === 'Admin') {
    // L'admin a accès à tout
} elseif ($role === 'Partenaire') {
    // Vérification de l'appartenance du client au partenaire
    if ($client['partenaires_idpartenaires'] != $_SESSION['partner_id']) {
        error_log("annuaire.php - Accès refusé pour le partenaire");
        header('Location: ../login/login.php');
        exit;
    }
} elseif ($role === 'Client') {
    if ($clientsId != $_SESSION['client_id']) {
        error_log("annuaire.php - Accès refusé pour le client");
        header('Location: ../login/login.php');
        exit;
    }
}

// Initialiser l'objet AnnuaireManager
$annuaireManager = new AnnuaireManager($pdo);

// Variables pour les messages
$message = '';
$error = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'add':
                $stmt = $pdo->prepare("SELECT p.Nom as partner_name, c.Nom as client_name 
                                     FROM Clients c 
                                     JOIN Partenaires p ON c.partenaires_idpartenaires = p.idpartenaires 
                                     WHERE c.idclients = ?");
                $stmt->execute([$clientsId]);
                $info = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$info) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Impossible de trouver le partenaire et le client associés']);
                    exit;
                }
                $ou = $info['partner_name'] . '-' . $info['client_name'];
                error_log("annuaire.php - OU construit : $ou");
                $result = $annuaireManager->addEntry(
                    $clientsId,
                    $_POST['Prenom'],
                    $_POST['Nom'],
                    $_POST['Email'] ?? '',
                    $_POST['Societe'] ?? '',
                    '', // adresse
                    '', // ville
                    $_POST['Telephone'],
                    $_POST['Commentaire'] ?? ''
                );
                if ($result) {
                    $contactId = $pdo->lastInsertId();
                    error_log("annuaire.php - Contact ajouté en BDD avec ID: $contactId");
                    if (!LDAPSyncTriggers::afterContactSave($contactId)) {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'error' => 'Erreur de synchronisation LDAP lors de l\'ajout']);
                        exit;
                    }
                    echo json_encode(['success' => true, 'message' => 'Contact ajouté avec succès']);
                    exit;
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'ajout du contact']);
                    exit;
                }
                break;
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Suppression d'un contact
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $contactId = (int) $_GET['id'];

    try {
        // Récupère les informations du contact avant la suppression
        $sql = "SELECT ua.*, c.Nom as client_name, p.Nom as partner_name 
               FROM User_annuaire ua
               JOIN Annuaires a ON ua.annuaire_id = a.idAnnuaires
               JOIN Clients c ON a.clients_idclients = c.idclients
               JOIN Partenaires p ON c.partenaires_idpartenaires = p.idpartenaires
               WHERE ua.idUserAnnuaire = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$contactId]);
        $contact = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$contact) {
            throw new Exception("Contact non trouvé");
        }

        // Construction de l'OU
        $ou = $contact['partner_name'] . '-' . $contact['client_name'];

        // Initialise le gestionnaire LDAP
        $ldapManager = new LDAPManager();

        // Supprime d'abord dans LDAP
        if (!$ldapManager->deleteEntry($ou, $contactId)) {
            throw new Exception("Erreur lors de la suppression LDAP");
        }

        // Puis supprime dans la base de données
        if (!$annuaireManager->deleteContact($contactId)) {
            throw new Exception("Erreur lors de la suppression en base de données");
        }

        header("Location: annuaire.php?idclients=" . $clientsId);
        exit;

    } catch (Exception $e) {
        error_log("Erreur lors de la suppression : " . $e->getMessage());
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Récupérer les informations du client
$clientInfo = null;
if ($clientsId) {
    $stmt = $pdo->prepare("SELECT Nom FROM Clients WHERE idclients = ?");
    $stmt->execute([$clientsId]);
    $clientInfo = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = isset($_GET['perPage']) ? (int) $_GET['perPage'] : 10;
$totalContacts = $annuaireManager->countAnnuaireByClient($clientsId);
$offset = ($page - 1) * $perPage;
$totalPages = max(1, ceil($totalContacts / $perPage));
$contacts = $annuaireManager->getAnnuaireByClientPaginated($clientsId, $offset, $perPage);

function paginationAnnuaire($page, $totalPages, $perPage, $clientsId)
{
    $html = '<div class="pagination-controls">';
    if ($page > 1) {
        $html .= '<a href="?idclients=' . $clientsId . '&page=1&perPage=' . $perPage . '" class="pagination-button">«</a>';
        $html .= '<a href="?idclients=' . $clientsId . '&page=' . ($page - 1) . '&perPage=' . $perPage . '" class="pagination-button">‹</a>';
    }
    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);
    for ($i = $start; $i <= $end; $i++) {
        $active = ($i === $page) ? 'active' : '';
        $html .= '<a href="?idclients=' . $clientsId . '&page=' . $i . '&perPage=' . $perPage . '" class="pagination-button ' . $active . '">' . $i . '</a>';
    }
    if ($page < $totalPages) {
        $html .= '<a href="?idclients=' . $clientsId . '&page=' . ($page + 1) . '&perPage=' . $perPage . '" class="pagination-button">›</a>';
        $html .= '<a href="?idclients=' . $clientsId . '&page=' . $totalPages . '&perPage=' . $perPage . '" class="pagination-button">»</a>';
    }
    $html .= '</div>';
    return $html;
}

// Initialiser l'objet ClientsForm
$ClientsForm = new ShowClientForm($pdo);

// Pour la barre latérale
$idclient = $clientsId;

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annuaire - <?php echo htmlspecialchars($clientInfo['Nom']); ?></title>
    <link rel="stylesheet" href="annuaire.css">
    <link rel="icon" type="image/png" href="../admin/logo/Logo-ldap.png">
    <link rel="shortcut icon" type="image/png" href="../admin/logo/Logo-ldap.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <!-- Contenu principal -->
    <div class="main-content">
        <?php include '../partials/V2/headV2.php'; ?>

        <div class="annuaire-container">
            <div class="title-section">
                <h1>Annuaire pour : <?php echo htmlspecialchars($clientInfo['Nom']); ?></h1>
                <div class="actions">

                    <button class="btn btn-outline" id="export-csv-btn">
                        <i class="fas fa-file-export"></i> Exporter CSV
                    </button>
                    <button class="btn btn-outline" id="import-csv-btn">
                        <i class="fas fa-file-import"></i> Importer CSV
                    </button>
                    <button class="btn btn-primary"
                        onclick="document.getElementById('addContactModal').style.display='block'">
                        <i class="fas fa-plus"></i> Ajouter un contact
                    </button>
                </div>
            </div>

            <div class="search-box">
                <input type="text" id="contactSearchInput" placeholder="Rechercher un contact...">
                <i class="fas fa-search search-icon"></i>
            </div>

            <?php if (!empty($contacts)): ?>
                <div class="contact-grid">
                    <?php foreach ($contacts as $contact): ?>
                        <div class="contact-card">
                            <div class="card-logo">
                                <div class="logo-placeholder">
                                    <?php
                                    $initials = strtoupper(substr($contact['Nom'], 0, 2));
                                    echo $initials;
                                    ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="client-name">
                                    <?php echo htmlspecialchars($contact['Societe'] ?: $contact['Nom']); ?>
                                </div>
                                <div class="client-details">
                                    <?php if (!empty($contact['Telephone'])): ?>
                                        <div class="detail-item">
                                            <i class="fas fa-phone"></i>
                                            <span><?php echo htmlspecialchars($contact['Telephone']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($contact['Email'])): ?>
                                        <div class="detail-item">
                                            <i class="fas fa-envelope"></i>
                                            <span><?php echo htmlspecialchars($contact['Email']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-actions">
                                    <button class="btn-icon"
                                        onclick="openEditContactModal(<?php echo $contact['iduser_annuaire']; ?>, <?php echo $clientsId; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-icon btn-delete"
                                        onclick="if(confirm('Êtes-vous sûr de vouloir supprimer ce contact ?')) window.location.href='?action=delete&id=<?php echo $contact['iduser_annuaire']; ?>&idclients=<?php echo $clientsId; ?>'">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-address-book"></i>
                    </div>
                    <h2>Aucun contact trouvé</h2>
                    <p>Commencez par ajouter des contacts à votre annuaire</p>
                    <button class="btn btn-primary"
                        onclick="document.getElementById('addContactModal').style.display='block'">
                        <i class="fas fa-plus"></i> Ajouter un contact
                    </button>
                </div>
            <?php endif; ?>

            <?php echo paginationAnnuaire($page, $totalPages, $perPage, $clientsId); ?>

            <div class="annuaire-per-page" style="margin-top: 14px;">
                <label for="perPage">Par page :</label>
                <select id="perPage"
                    onchange="window.location.href='?idclients=<?php echo $clientsId; ?>&page=1&perPage='+this.value;">
                    <option value="10" <?php if ($perPage == 10)
                        echo 'selected'; ?>>10</option>
                    <option value="25" <?php if ($perPage == 25)
                        echo 'selected'; ?>>25</option>
                    <option value="50" <?php if ($perPage == 50)
                        echo 'selected'; ?>>50</option>
                    <option value="100" <?php if ($perPage == 100)
                        echo 'selected'; ?>>100</option>
                </select>
            </div>
        </div>
    </div>
    </div>

    <!-- Modal d'ajout de contact -->
    <div id="addContactModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('addContactModal').style.display='none'">&times;</span>
            <h2>Ajouter un contact</h2>
            <form id="addContactForm" method="POST">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="idclients" value="<?php echo $clientsId; ?>">
                <div class="form-group">
                    <label for="prenom">Prénom</label>
                    <input type="text" id="prenom" name="Prenom">
                </div>
                <div class="form-group">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="Nom" required>
                </div>
                <div class="form-group">
                    <label for="telephone">Téléphone</label>
                    <input type="tel" id="telephone" name="Telephone" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="Email">
                </div>
                <div class="form-group">
                    <label for="societe">Société</label>
                    <input type="text" id="societe" name="Societe">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-outline"
                        onclick="document.getElementById('addContactModal').style.display='none'">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal d'édition de contact -->
    <div id="editContactModal" class="modal" style="display:none;"></div>

    <script>
        function openEditContactModal(contactId, clientId) {
            fetch('edit_contact_modal.php?id=' + contactId + '&idclients=' + clientId)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('editContactModal').innerHTML = html;
                    document.getElementById('editContactModal').style.display = 'block';
                    // Ajout du submit AJAX
                    const form = document.getElementById('editContactForm');
                    if (form) {
                        form.onsubmit = function (e) {
                            e.preventDefault();
                            const formData = new FormData(form);
                            fetch('update_contact.php', {
                                method: 'POST',
                                body: formData
                            })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        window.location.reload();
                                    } else {
                                        alert(data.error || 'Erreur lors de la modification');
                                    }
                                });
                        };
                    }
                });
        }
        function closeModal() {
            document.getElementById('editContactModal').style.display = 'none';
        }
    </script>

    <script src="annuaire.js"></script>
</body>

</html>