<?php
// Si la requête est de type ?mac=... on sert dynamiquement le fichier .cfg
if (isset($_GET['mac'])) {
    $mac = strtoupper(preg_replace('/[^A-Fa-f0-9]/', '', $_GET['mac']));
    $filename = __DIR__ . "/$mac.cfg";
    if (file_exists($filename)) {
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: inline; filename="' . $mac . '.cfg"');
        readfile($filename);
        exit;
    } else {
        header('HTTP/1.0 404 Not Found');
        echo "# Fichier de configuration introuvable pour $mac\n";
        exit;
    }
}

// Sinon, on affiche le formulaire de génération comme avant
$success = false;
$content = '';
$filename = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mac = strtoupper(preg_replace('/[^A-Fa-f0-9]/', '', $_POST['mac'] ?? ''));
    $modele = $_POST['modele'] ?? '';
    if ($mac && $modele) {
        // Exemple de conf SIP pour Yealink T54W
        $content = "# AutoProv Test Yealink T54W\n";
        $content .= "account.1.enable = 1\n";
        $content .= "account.1.label = TestUser\n";
        $content .= "account.1.display_name = TestUser\n";
        $content .= "account.1.user_name = 1001\n";
        $content .= "account.1.auth_name = 1001\n";
        $content .= "account.1.password = secret\n";
        $content .= "account.1.sip_server.1.address = sip.example.com\n";
        $content .= "account.1.sip_server.1.port = 5060\n";
        $filename = $mac . ".cfg";
        if (!is_writable(__DIR__)) {
            $success = false;
            $content = '';
            $filename = '';
            echo '<p style="color:red">Erreur : le dossier n\'est pas accessible en écriture par PHP !</p>';
        } else {
            if (file_put_contents(__DIR__ . "/" . $filename, $content) === false) {
                $success = false;
                echo '<p style="color:red">Erreur : impossible de créer le fichier ' . htmlspecialchars($filename) . '</p>';
            } else {
                $success = true;
            }
        }
    }
}
caca
?>
<!DOCTYPE html>
<html><body>
<form method="post">
    <label>Adresse MAC (sans : ni -) : <input name="mac" required></label><br>
    <label>Modèle : <input name="modele" value="Yealink T54W" required></label><br>
    <button type="submit">Générer .cfg</button>
</form>
<?php if ($success): ?>
    <p>Fichier généré : <b><?= htmlspecialchars($filename) ?></b></p>
    <pre><?= htmlspecialchars($content) ?></pre>
<?php endif; ?>
</body></html>
