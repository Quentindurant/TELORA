<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Partner's client list
 * 
 * This page displays:
 * - The list of clients associated with a partner
 * - Links to each client's details
 * - Client management options based on user roles
 * 
 * Main features:
 * - Filtering clients by partner
 * - Access rights management (Admin/Partner)
 * - Maintaining navigation context
 * - Updating session IDs
 */

require_once '../database/db.php';

include '../database/partner_request.php';
include '../database/clients_request.php';
///////////////////// Access rights gestionnary ///////////////////
session_start();

// Authentication verification
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Partenaire')) {
    header('Location: ../login/login.php');
    exit;
}

// Retrieving the partner ID
$partnerId = null;
if (isset($_GET['idpartenaires'])) {
    $partnerId = intval($_GET['idpartenaires']);
    $_SESSION['partner_id'] = $partnerId;
    error_log("[clientlist.php] Updated partner_id in session from GET: " . $partnerId);
} elseif (isset($_SESSION['partner_id'])) {
    $partnerId = $_SESSION['partner_id'];
    error_log("[clientlist.php] Using partner_id from session: " . $partnerId);
} else {
    error_log("[clientlist.php] No partner_id found");
}

// Access rights verification
if ($_SESSION['role'] === 'Partenaire' && $_SESSION['partner_id'] !== $partnerId) {
    header('Location: ../login/login.php');
    exit;
}

///////////////////// END Access rights verification ///////////////////
//Temporaire pour le développement :
if (isset($_GET['idpartenaires']))
    $idpartenaire = $_GET['idpartenaires'];
else
    $idpartenaire = 2;

if (isset($_POST['idpartenaire']))
    $idpartenaire = $_POST['idpartenaire'];

$clientsHandler = new ClientsHandler($pdo);
$partnersHandler = new ShowPartnerForm($pdo);

// Management of deletion via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $clientId = intval($_POST['id']);
    $result = $clientsHandler->deleteClient($clientId);

    header('Content-Type: application/json');
    if ($result === true) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la suppression.']);
    }
    exit;
}

// Claim Partner ID from session or GET
$partnerId = $_SESSION['partner_id'] ?? ($_GET['idpartenaires'] ?? null);

// need to check if partnerId is set
if ($partnerId === null) {
    echo "Erreur : aucun partenaire spécifié.";
    exit;
}

$partnerName = '';
$partnerData = $partnersHandler->PartnerRecoveryById($partnerId);
if (!empty($partnerData) && isset($partnerData[0]['Nom'])) {
    $partnerName = $partnerData[0]['Nom'];
}

$error = null;

// Forms traitement (ajout ET édition)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_client']) && isset($_POST['EditClientId'])) {
        // Modification d'un client
        $idclient = intval($_POST['EditClientId']);
        $nom = $_POST['Nom'];
        $email = $_POST['Email'];
        $tel = $_POST['Telephone'];
        $adresse = $_POST['Adresse'];
        $plateforme = $_POST['Plateforme'];
        $plateformeurl = $_POST['PlateformeURL'];
        $clientsHandler->updateClient($idclient, $nom, $email, $tel, $adresse, $plateforme, $plateformeurl);
        header("Location: clientlist2.php?idpartenaires=$partnerId");
        exit;
    } elseif (isset($_POST['add_client'])) {
        // Ajout d'un client
        $result = $clientsHandler->addClient(
            $_POST['Nom'],
            $_POST['Email'],
            $_POST['Telephone'],
            $_POST['Adresse'],
            $_POST['Plateforme'],
            $_POST['PlateformeURL'],
            $partnerId
        );
        if ($result === true) {
            header("Location: clientlist2.php?idpartenaires=$partnerId");
            exit;
        } else {
            $error = $result;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UI Example</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="clientlist2.css">
</head>
<header>
    <?php include '../partials/V2/headV2.php'; ?>
</header>

<body>
    <div id="loading-spinner" class="loading-spinner"></div>

    <!-- <img src="../admin/logo/Logo-ldap.png" alt="Logo" class="logo-header"> -->
    <?php //include '../partials/header_copy.php'; ?>

    <div class="container">
        <div class="title-section">
            <h1>Liste des clients pour : <span class="partner-name" id="partner-name">
                    <?php
                    if (isset($partnerId)) {
                        $partnerData = $partnersHandler->PartnerRecoveryById($partnerId);
                        if (!empty($partnerData) && isset($partnerData[0]['Nom'])) {
                            echo htmlspecialchars($partnerData[0]['Nom']);
                        } else {
                            echo "Partenaire inconnu";
                        }
                    } else {
                        echo "Aucun identifiant de partenaire fourni.";
                        exit;
                    }
                    ?>
                </span>
            </h1>
        </div>
        <div class="action-bar">
            <form class="client-search-bar" method="get" action="" style="gap:12px;">
                <input type="text" name="search"
                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
                    placeholder="Nom, téléphone, adresse ou email..." style="min-width:180px;" />
                <select name="plateforme" class="form-select">
                    <option value="">Plateforme</option>
                    <option value="Wazo" <?php if (isset($_GET['plateforme']) && $_GET['plateforme'] === 'Wazo')
                        echo 'selected'; ?>>Wazo</option>
                    <option value="OVH" <?php if (isset($_GET['plateforme']) && $_GET['plateforme'] === 'OVH')
                        echo 'selected'; ?>>OVH</option>
                    <option value="Yeastar" <?php if (isset($_GET['plateforme']) && $_GET['plateforme'] === 'Yeastar')
                        echo 'selected'; ?>>Yeastar</option>
                </select>
                <!--
                <select name="statut" style="min-width:110px;">
                    <option value="">Statut</option>
                    <option value="Actif" <?php if (isset($_GET['statut']) && $_GET['statut'] === 'Actif')
                        echo 'selected'; ?>>Actif</option>
                    <option value="Inactif" <?php if (isset($_GET['statut']) && $_GET['statut'] === 'Inactif')
                        echo 'selected'; ?>>Inactif</option>
                </select>
                -->
                <?php if (isset($_GET['search']) && $_GET['search'] !== ''): ?>
                    <a href="?<?php echo http_build_query(array_diff_key($_GET, ['search' => 1, 'plateforme' => 1, 'statut' => 1])) ?>"
                        class="reset-search-btn">✕</a>
                <?php endif; ?>
                <input type="hidden" name="idpartenaires" value="<?php echo htmlspecialchars($idpartenaire); ?>">
                <button type="submit">Rechercher</button>
            </form>
            <!--<input type="search" id="search-input" placeholder="Rechercher un client...">-->
            <?php if ($_SESSION['role'] === 'Admin'): ?>
                <button class="add-btn" id="openAddModal">
                    <svg width="18" height="18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 3v12M3 9h12" stroke="#fff" stroke-width="2" stroke-linecap="round" />
                    </svg>
                    Ajouter un client
                </button>
            <?php endif; ?>
        </div>

        <div class="clients-list" id="client-list">
            <?php
            if (isset($idpartenaire)) {
                $Clients = $clientsHandler->getClientsByPartner($idpartenaire);
                // Filtrage plateforme
                if (isset($_GET['plateforme']) && $_GET['plateforme'] !== '') {
                    $Clients = array_filter($Clients, function ($client) {
                        return isset($client['Plateforme']) && $client['Plateforme'] === $_GET['plateforme'];
                    });
                }
                // Filtrage statut
                if (isset($_GET['statut']) && $_GET['statut'] !== '') {
                    $Clients = array_filter($Clients, function ($client) {
                        return isset($client['Statut']) && $client['Statut'] === $_GET['statut'];
                    });
                }
                // Filtrage par recherche multi-champs
                if (isset($_GET['search']) && trim($_GET['search']) !== '') {
                    $search = mb_strtolower(trim($_GET['search']));
                    $Clients = array_filter($Clients, function ($client) use ($search) {
                        return (
                            mb_strpos(mb_strtolower($client['Nom']), $search) !== false ||
                            mb_strpos(mb_strtolower($client['Telephone']), $search) !== false ||
                            mb_strpos(mb_strtolower($client['Email']), $search) !== false ||
                            mb_strpos(mb_strtolower($client['Adresse']), $search) !== false
                        );
                    });
                }
                $clientsPerPage = 5;
                $totalClients = count($Clients);
                $totalPages = max(1, ceil($totalClients / $clientsPerPage));
                $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
                $start = ($page - 1) * $clientsPerPage;
                $ClientsToShow = array_slice($Clients, $start, $clientsPerPage);
                if (empty($Clients)) {
                    echo '<div class="no-clients">Aucun client pour ce partenaire.</div>';
                } else {
                    echo '<div class="clients-table-modern-wrapper">';
                    echo '<table class="clients-table-modern">';
                    echo '<thead><tr>';
                    echo '<th>Nom</th>';
                    echo '<th>Plateforme</th>';
                    // echo '<th>Statut</th>';
                    echo '<th>Téléphone</th>';
                    echo '<th>Email</th>';
                    echo '<th>Adresse</th>';
                    echo '<th>Actions</th>';
                    echo '</tr></thead><tbody>';
                    foreach ($ClientsToShow as $client) {
                        $classSuffix = strtolower($client['Plateforme']);
                        $class = "platform-badge platform-badge-" . $classSuffix;
                        $adresse = !empty($client['Adresse']) ? $client['Adresse'] : "Aucune localisation indiquée";
                        $plateformeDisplay = '<div class="' . $class . '"><i class="fas fa-server"></i> <span>' . htmlspecialchars($client['Plateforme'] ?? '') . '</span></div>';
                        /*$statut = isset($client['Statut']) && $client['Statut'] === 'Inactif' ? '<span class="status-badge status-inactif">Inactif</span>' : '<span class="status-badge status-actif">Actif</span>';*/
                        echo '<tr>';
                        echo '<td class="client-name-cell"><a href="../clientdetail/clientdetail.php?idclient=' . htmlspecialchars($client['idclients'] ?? '') . '" class="client-link"><i class="fas fa-user"></i> <span class="client-name-text">' . htmlspecialchars($client['Nom'] ?? '') . '</span></a></td>';
                        echo '<td>' . $plateformeDisplay . '</td>';
                        /*echo '<td>' . $statut . '</td>';*/
                        echo '<td>' . htmlspecialchars($client['Telephone'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($client['Email'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($adresse ?? '') . '</td>';
                        echo '<td class="actions">';
                        echo '<a href="../clientdetail/clientdetail.php?idclient=' . htmlspecialchars($client['idclients'] ?? '') . '" class="btn-card-details modern" title="Détails"><i class="fas fa-eye"></i></a> ';
                        if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') {
                            echo '<button class="btn btn-icon btn-edit modern" '
                                . 'data-client-id="' . htmlspecialchars($client['idclients'] ?? '') . '" '
                                . 'data-client-nom="' . htmlspecialchars($client['Nom'] ?? '') . '" '
                                . 'data-client-email="' . htmlspecialchars($client['Email'] ?? '') . '" '
                                . 'data-client-telephone="' . htmlspecialchars($client['Telephone'] ?? '') . '" '
                                . 'data-client-adresse="' . htmlspecialchars($adresse ?? '') . '" '
                                . 'data-client-plateforme="' . htmlspecialchars($client['Plateforme'] ?? '') . '" '
                                . 'data-client-plateformeurl="' . htmlspecialchars($client['PlateformeURL'] ?? '') . '" '
                                . 'onclick="openEditModal(this)">'
                                . '<i class="fas fa-edit"></i>'
                                . '</button> ';
                            echo '<button class="btn btn-icon btn-delete modern" data-client-id="' . htmlspecialchars($client['idclients'] ?? '') . '" onclick="confirmDelete(' . htmlspecialchars($client['idclients'] ?? '') . ')"><i class="fas fa-trash-alt"></i></button>';
                        }
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table></div>';
                }
                // Pagination et bouton revenir en arrière
                echo '<div class="table-footer-bar">';
                echo '  <div class="footer-left">';
                echo '    <a href="javascript:history.back()" class="back-button"><i class="fas fa-arrow-left"></i> <span>Revenir en arrière</span></a>';
                echo '  </div>';
                echo '  <div class="footer-right pagination">';
                $baseUrl = strtok($_SERVER["REQUEST_URI"], '?');
                $queryParams = $_GET;
                $queryParams['idpartenaire'] = $idpartenaire;
                if ($page > 1) {
                    $queryParams['page'] = $page - 1;
                    echo '<a href="' . $baseUrl . '?' . http_build_query($queryParams) . '" class="page-link" title="Précédent"><i class="fas fa-chevron-left"></i></a>';
                }
                for ($i = 1; $i <= $totalPages; $i++) {
                    $queryParams['page'] = $i;
                    $active = $i === $page ? 'active' : '';
                    echo '<a href="' . $baseUrl . '?' . http_build_query($queryParams) . '" class="page-link ' . $active . '">' . $i . '</a>';
                }
                if ($page < $totalPages) {
                    $queryParams['page'] = $page + 1;
                    echo '<a href="' . $baseUrl . '?' . http_build_query($queryParams) . '" class="page-link" title="Suivant"><i class="fas fa-chevron-right"></i></a>';
                }
                echo '  </div>';
                echo '</div>';
            }
            ?>
        </div>

        <!-- Modal d'ajout de client -->
        <div id="addModal" class="modal">
            <div class="modal-content">
                <span class="close-btn" id="closeAddModal">&times;</span>
                <div class="feedback" id="modal-feedback"></div>
                <h2>Ajouter un nouveau client</h2>
                <form id="addClientForm" method="POST">
                    <input type="hidden" name="PartnerId" value="<?= htmlspecialchars($partnerId) ?>">
                    <div class="form-row">
                        <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nom" name="Nom" placeholder="Entrez le nom"
                            required>
                    </div>
                    <div class="form-row">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="Email" placeholder="Entrez l'email"
                            required>
                    </div>
                    <div class="form-row">
                        <label for="telephone" class="form-label">Téléphone <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="telephone" name="Telephone"
                            placeholder="Entrez le numéro de téléphone" required>
                    </div>
                    <div class="form-row">
                        <label for="adresse" class="form-label">Adresse</label>
                        <textarea class="form-control" id="adresse" name="Adresse"
                            placeholder="Entrez l'adresse (facultatif)" rows="3"></textarea>
                    </div>
                    <div class="form-row">
                        <label for="plateforme" class="form-label">Plateforme <span class="text-danger">*</span></label>
                        <select class="form-select" id="plateforme" name="Plateforme" onchange="updatePlatformURL()"
                            required>
                            <option value="">Choisir une plateforme...</option>
                            <option value="Wazo">Wazo</option>
                            <option value="OVH">OVH</option>
                            <option value="Yeastar">Yeastar</option>
                        </select>
                    </div>
                    <div class="form-row" id="tenant" style="display: none;">
                        <label for="tenant_value" class="form-label">Wazo Tenant</label>
                        <select class="form-select" id="tenant_value" name="Tenant" onchange="updatePlatformURL()">
                            <option value="">Choisir un tenant...</option>
                            <?php foreach ($clientsHandler->getPlatforms()['Wazo'] as $name => $url): ?>
                                <option value="<?= htmlspecialchars($url) ?>"><?= htmlspecialchars($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="plateforme_url" class="form-label">URL Plateforme</label>
                        <input type="text" class="form-control" id="plateforme_url" name="PlateformeURL" readonly>
                    </div>
                    <div class="text-center" style="margin-top:18px;">
                        <button type="submit" name="add_client" class="btn btn-success">Ajouter le Client</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal d'édition client -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close-btn-edit">&times;</span>
                <h2>Modifier le client</h2>
                <div class="feedback" id="edit-modal-feedback"></div>
                <form id="editClientForm" method="POST">
                    <input type="hidden" name="EditClientId" id="edit-client-id">
                    <div class="form-row">
                        <label for="edit-nom" class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit-nom" name="Nom" required>
                    </div>
                    <div class="form-row">
                        <label for="edit-email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="edit-email" name="Email" required>
                    </div>
                    <div class="form-row">
                        <label for="edit-telephone" class="form-label">Téléphone <span
                                class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="edit-telephone" name="Telephone" required>
                    </div>
                    <div class="form-row">
                        <label for="edit-adresse" class="form-label">Adresse</label>
                        <textarea class="form-control" id="edit-adresse" name="Adresse" rows="3"></textarea>
                    </div>
                    <div class="form-row">
                        <label for="edit-plateforme" class="form-label">Plateforme <span
                                class="text-danger">*</span></label>
                        <select class="form-select" id="edit-plateforme" name="Plateforme"
                            onchange="updateEditPlatformURL()" required>
                            <option value="">Choisir une plateforme...</option>
                            <option value="Wazo">Wazo</option>
                            <option value="OVH">OVH</option>
                            <option value="Yeastar">Yeastar</option>
                        </select>
                    </div>
                    <div class="form-row" id="edit-tenant" style="display: none;">
                        <label for="edit-tenant_value" class="form-label">Wazo Tenant</label>
                        <select class="form-select" id="edit-tenant_value" name="Tenant"
                            onchange="updateEditPlatformURL()">
                            <option value="">Choisir un tenant...</option>
                            <?php foreach ($clientsHandler->getPlatforms()['Wazo'] as $name => $url): ?>
                                <option value="<?= htmlspecialchars($url) ?>"><?= htmlspecialchars($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="edit-plateforme_url" class="form-label">URL Plateforme</label>
                        <input type="text" class="form-control" id="edit-plateforme_url" name="PlateformeURL" readonly>
                    </div>
                    <div class="text-center" style="margin-top:18px;">
                        <button type="submit" name="edit_client" class="btn btn-success">Enregistrer les
                            modifications</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script>
        // Gestion de la modal d'édition
        function openEditModal(btn) {
            const editModal = document.getElementById('editModal');
            document.getElementById('edit-client-id').value = btn.getAttribute('data-client-id');
            document.getElementById('edit-nom').value = btn.getAttribute('data-client-nom');
            document.getElementById('edit-email').value = btn.getAttribute('data-client-email');
            document.getElementById('edit-telephone').value = btn.getAttribute('data-client-telephone');
            document.getElementById('edit-adresse').value = btn.getAttribute('data-client-adresse');
            document.getElementById('edit-plateforme').value = btn.getAttribute('data-client-plateforme');
            document.getElementById('edit-plateforme_url').value = btn.getAttribute('data-client-plateformeurl');
            editModal.classList.add('active');
        }
        document.addEventListener('DOMContentLoaded', () => {
            const editModal = document.getElementById('editModal');
            const closeBtnEdit = document.querySelector('.close-btn-edit');
            if (closeBtnEdit) {
                closeBtnEdit.addEventListener('click', () => {
                    editModal.classList.remove('active');
                });
            }
            window.addEventListener('click', (event) => {
                if (event.target === editModal) {
                    editModal.classList.remove('active');
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            // Gestion du modal d'ajout
            const addModal = document.getElementById('addModal');
            const openAddModalBtn = document.getElementById('openAddModal');
            const closeAddModalBtn = document.getElementById('closeAddModal');

            if (openAddModalBtn) {
                openAddModalBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    addModal.classList.add('active');
                });
            }
            if (closeAddModalBtn) {
                closeAddModalBtn.addEventListener('click', function () {
                    addModal.classList.remove('active');
                });
            }
            window.addEventListener('click', function (event) {
                if (event.target === addModal) {
                    addModal.classList.remove('active');
                }
            });
        });

        // platform function for URL and tenant display
        // no safe method need improvment (security IP)
        function updatePlatformURL() {
            const platform = document.getElementById('plateforme').value;
            const tenant = document.getElementById('tenant');
            const platformURL = document.getElementById('plateforme_url');

            let url = '';
            if (platform === 'Wazo') {
                tenant.style.display = 'block';
                const tenantValue = document.getElementById('tenant_value').value;
                url = tenantValue;
            } else if (platform === 'OVH') {
                tenant.style.display = 'none';
                url = 'fr.proxysip.eu';
            } else if (platform === 'Yeastar') {
                tenant.style.display = 'none';
                url = '192.168.1.150';
            } else {
                tenant.style.display = 'none';
            }

            platformURL.value = url;
        }
        // Fonction pour la modal d'édition (plateforme/tenant/URL)
        function updateEditPlatformURL() {
            const platform = document.getElementById('edit-plateforme').value;
            const tenant = document.getElementById('edit-tenant');
            const platformURL = document.getElementById('edit-plateforme_url');
            let url = '';
            if (platform === 'Wazo') {
                tenant.style.display = 'block';
                const tenantValue = document.getElementById('edit-tenant_value').value;
                url = tenantValue;
            } else if (platform === 'OVH') {
                tenant.style.display = 'none';
                url = 'fr.proxysip.eu';
            } else if (platform === 'Yeastar') {
                tenant.style.display = 'none';
                url = '192.168.1.150';
            } else {
                tenant.style.display = 'none';
            }
            platformURL.value = url;
        }
    </script>
    <button type="button" class="closeModal">Fermer</button>
    </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Ouvrir le modal d'ajout
            document.getElementById('openAddModal').addEventListener('click', function () {
                document.getElementById('addModal').style.display = 'block';
            });

            // Ouvrir le modal de modification (pour chaque client)
            document.querySelectorAll('.btn-edit').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.getElementById('editModal').style.display = 'block';
                    // Pré-remplir le formulaire si besoin
                });
            });

            // Fermer les modals
            document.querySelectorAll('.closeModal').forEach(btn => {
                btn.addEventListener('click', function () {
                    this.closest('.modal').style.display = 'none';
                });
            });

            // Fermer le modal si on clique en dehors du contenu
            window.onclick = function (event) {
                ['addModal', 'editModal'].forEach(function (modalId) {
                    var modal = document.getElementById(modalId);
                    if (event.target === modal) {
                        modal.style.display = "none";
                    }
                });
            };
        });
    </script>
    <script src="clientlist.js"></script>
</body>

</html>