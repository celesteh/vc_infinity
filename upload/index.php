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

if(!isset($_SESSION["powerlevel"])){
    //echo "unset";
    $_SESSION["powerlevel"] = get_power_level_for_user($_SESSION["id"], $pdo);
} 

?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome</title>
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="infinity.css">

</head>
<body>
    <div class="page-header">
        <h1><b><?php echo htmlspecialchars($_SESSION["realname"]); ?></b> @ <?php echo $_SITE["title"]; ?></h1>
    </div>

    <?php include 'nav-menu.php';?>


    <?php 
    //echo $_SESSION["powerlevel"];
    if ($_SESSION["powerlevel"] < 20){
        $approval_required = _("Your account must be approved before you can participate.");
        echo "<p>" . $approval_required . "</p>\n";
    }
    ?>
    
   



</body>
</html>
