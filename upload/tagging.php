<?php
// Initialize the session
session_start();
 
include_once "config.php";
include_once "functions.php";
 
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}


if (! lazy_power_check($_SESSION["id"], $pdo, 20)){
    header("location: index.php");
}

if(isset($_SESSION["page_called"])){
    $page_called = $_SESSION["page_called"];
} else {
    $page_called = get_page_called_for_user($_SESSION["id"], $pdo);
    $_SESSION["page_called"] = $page_called;
}
$upper = ucfirst($page_called);


$correct_nonce = verify_nonce();
//if (! $correct_nonce){
$_SESSION['nonce'] = set_nonce();
    //}

$edior = lazy_power_check($_SESSION["id"], $pdo, 60)



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tagging</title>
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="infinity.css">

</head>
<body>
    <div class="page-header">
        <h1><b>Audio Tagging</b<</h1>
    </div>

    <?php include 'nav-menu.php';?>
    <div>
    <h2>Coming Soon<h2>
    <p>Tag your audio!</p>
    <p><a href="manage-tags.php">Manage available tags</a></p>
    </div>
    <div>
    
    </body>
</html>
