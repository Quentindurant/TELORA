<?php
// Ce fichier est inclus en AJAX dans la modal d'édition
require_once '../database/db.php';
require_once '../database/Annuaire_request.php';
require_once '../utils/functions.php';

$contactId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$clientId = isset($_GET['idclients']) ? (int)$_GET['idclients'] : null;

if (!$contactId || !$clientId) {
    die("Paramètres manquants");
}

$annuaireManager = new AnnuaireManager($pdo);
$contact = $annuaireManager->getContact($contactId);
$clientInfo = $annuaireManager->getClientName($clientId);
$clientName = !empty($clientInfo[0]['Nom']) ? htmlspecialchars($clientInfo[0]['Nom']) : 'Client inconnu';
?>
<div class="modal-content">
    <span class="close" onclick="closeModal()">&times;</span>
    <h2>Modifier un Contact de <?php echo $clientName; ?></h2>
    <form id="editContactForm" class="form">
        <input type="hidden" name="id" value="<?php echo $contactId; ?>">
        <input type="hidden" name="idclients" value="<?php echo $clientId; ?>">
        <div class="form-group">
            <label for="prenom">Prénom <span class="required">*</span></label>
            <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($contact['Prenom']); ?>" required>
        </div>
        <div class="form-group">
            <label for="nom">Nom <span class="required">*</span></label>
            <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($contact['Nom']); ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($contact['Email']); ?>">
        </div>
        <div class="form-group">
            <label for="societe">Société</label>
            <input type="text" id="societe" name="societe" value="<?php echo htmlspecialchars($contact['Societe']); ?>">
        </div>
        <div class="form-group">
            <label for="telephone">Téléphone</label>
            <input type="tel" id="telephone" name="telephone" value="<?php echo htmlspecialchars($contact['Telephone']); ?>">
        </div>
        <div class="form-group">
            <label for="adresse">Adresse</label>
            <input type="text" id="adresse" name="adresse" value="<?php echo htmlspecialchars($contact['Adresse']); ?>">
        </div>
        <div class="form-group">
            <label for="ville">Ville</label>
            <input type="text" id="ville" name="ville" value="<?php echo htmlspecialchars($contact['Ville']); ?>">
        </div>
        <div class="form-group">
            <label for="commentaire">Commentaire</label>
            <textarea id="commentaire" name="commentaire"><?php echo htmlspecialchars($contact['Commentaire']); ?></textarea>
        </div>
        <div class="btn-container">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
    </form>
</div>
