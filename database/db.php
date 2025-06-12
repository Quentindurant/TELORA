<?php
echo '<!-- DEBUG: db.php utilisé depuis ' . __FILE__ . ' -->';
try {
    // Établir la connexion avec la base de données
    $host = 'localhost';
    $db   = 'LDAP';
    $user = 'telora';
    $pass = 'jmaMy3!OQm1yvvL2';
    echo '<!-- DEBUG: Utilisateur SQL=' . $user . ' -->';
    $charset = 'utf8mb4';
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>