<?php
/* Database credentials. Assuming you are running MySQL
server with default setting (user 'vc_infinity' with no password) */
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'vc_infinity');
define('DB_PASSWORD', '');
define('DB_NAME', 'vc_infinity');
 
/* Attempt to connect to MySQL database */
try{
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    die("ERROR: Could not connect. " . $e->getMessage());
}

$_SITE = array("title" => "Constructing Infinity")

?>
