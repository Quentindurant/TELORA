<?php
require_once '../database/db.php';
require_once '../database/Annuaire_request.php';

header('Content-Type: application/json');

$clientId = isset($_GET['idclients']) ? (int)$_GET['idclients'] : null;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (!$clientId) {
    echo json_encode(['success'=>false, 'error'=>'ID client manquant']);
    exit;
}

$annuaireManager = new AnnuaireManager($pdo);
$contacts = $annuaireManager->getAnnuaireByClient($clientId);

if ($q !== '') {
    $q = mb_strtolower($q);
    $filtered = array_filter($contacts, function($c) use ($q) {
        return mb_stripos($c['Nom'], $q) !== false
            || mb_stripos($c['Prenom'], $q) !== false
            || mb_stripos($c['Email'], $q) !== false
            || mb_stripos($c['Societe'], $q) !== false
            || mb_stripos($c['Telephone'], $q) !== false;
    });
    $contacts = array_values($filtered);
}

echo json_encode(['success'=>true, 'contacts'=>$contacts]);
