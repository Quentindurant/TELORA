<?php
require_once("../database/db.php");
include '../database/clients_request.php';
include '../database/utilisateurs_request.php';

header('Content-Type: application/json');

if (!isset($_GET['query']) || !isset($_GET['idclient'])) {
    http_response_code(400);
    die(json_encode(['error' => 'Paramètres manquants']));
}

$query = $_GET['query'];
$idclient = (int)$_GET['idclient'];

try {
    $UtilisateursForm = new ShowUtilisateursForm($pdo);
    $users = $UtilisateursForm->SearchUtilisateursByClient($idclient, $query);
    
    if (!is_array($users)) {
        throw new Exception('Les résultats de recherche ne sont pas un tableau');
    }
    
    echo json_encode($users);
} catch (Exception $e) {
    error_log("Erreur dans search_users.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
