<?php
// F:\projet_v2\public\test_pdo_connection.php

ini_set('display_errors', 1); // Pour afficher les erreurs PHP directement
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = '127.0.0.1';
$db   = 'astropret';    // <<<--- VÉRIFIEZ ET ADAPTEZ LE NOM DE VOTRE BDD
$user = 'root';       // <<<--- VÉRIFIEZ ET ADAPTEZ VOTRE UTILISATEUR BDD
$pass = null;         // <<<--- METTEZ VOTRE MOT DE PASSE BDD ICI s'il y en a un, sinon laissez null ou ""

// Si vous utilisez un port non standard pour MySQL (différent de 3306)
// $port = 3306;
// $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";


$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

echo "Tentative de connexion à la base de données...<br>";
$startTime = microtime(true);

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     $connectionTime = microtime(true) - $startTime;
     echo "<strong>Connexion PDO réussie en " . number_format($connectionTime, 4) . " secondes.</strong><br>";

     // Test d'une requête simple
     $queryStartTime = microtime(true);
     $stmt = $pdo->query('SELECT VERSION()');
     $version = $stmt->fetchColumn();
     $queryTime = microtime(true) - $queryStartTime;
     echo "Version MySQL: " . htmlspecialchars($version) . "<br>";
     echo "Temps d'exécution de la requête simple: " . number_format($queryTime, 4) . " secondes.<br>";

} catch (\PDOException $e) {
     echo "<strong>Échec de la connexion PDO :</strong><br>";
     echo "Message: " . htmlspecialchars($e->getMessage()) . "<br>";
     echo "Code d'erreur: " . (int)$e->getCode() . "<br>";
     echo "<hr>Informations de débogage :<br>";
     echo "DSN utilisé: " . htmlspecialchars($dsn) . "<br>";
     echo "Utilisateur: " . htmlspecialchars($user) . "<br>";
     echo "Vérifiez vos identifiants, le nom de la base de données, et si le serveur MySQL est en cours d'exécution et accessible.<br>";
     echo "Assurez-vous aussi que l'extension PDO_MYSQL est activée dans le php.ini utilisé par votre serveur web.<br>";
}

echo "<hr><h2>Informations PHP (phpinfo) pour la configuration cURL et PDO :</h2>";
// Affiche uniquement les sections pertinentes de phpinfo
ob_start();
phpinfo(INFO_MODULES);
$pinfo = ob_get_contents();
ob_end_clean();

// Extrait les sections PDO et curl
$pdo_info = '';
if (preg_match('/<a name="module_pdo">.+?<\/table>/s', $pinfo, $matches)) {
    $pdo_info .= $matches[0];
}
if (preg_match('/<a name="module_pdo_mysql">.+?<\/table>/s', $pinfo, $matches)) {
    $pdo_info .= $matches[0];
}
if (preg_match('/<a name="module_curl">.+?<\/table>/s', $pinfo, $matches)) {
    $pdo_info .= $matches[0];
}

echo $pdo_info ?: "Impossible d'extraire les sections PDO/cURL de phpinfo.";

?>