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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Listen to Uploaded Audio</title>
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="infinity.css">

</head>
<body>
    <div class="page-header">
        <h1>Locked Feature</h1>
    </div>
    <?php include 'nav-menu.php';?>
    <div>
<?php
$sql = "SELECT sa_userid, sa_pageid, sa_filename FROM `submitted_audio` WHERE   (`sa_accepted` is NULL) OR (`sa_accepted` = 1) "; 
            if($stmt = $pdo->prepare($sql)){
                if($stmt->execute()){
                    $count = $stmt->rowCount();
                    if($count >= 50 ){
                        echo "\n<ol>\n";
                        while($row = $stmt->fetch()){
                            $local = "../unprocessed_audio/" . $row['sa_filename'];
                            echo '<li><audio controls="controls" src="'.$local.'" type="audio/wav" /></li>';
                            echo "\n";

                        };
                        echo "</ol>\n";
                    } else {
                        echo "<p>This feature will unlock when 50 audio files are uploaded.</p>\n";
                        echo "<p>There are currently " . strval($count) . " submissions. </p>\n";
                    }
                }
            }
    

?>
</div> 
</body>	
</html>