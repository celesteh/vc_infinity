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


$selected = false;
$panel = -1;


if($_SERVER["REQUEST_METHOD"] == "GET"){
 
    // check referral

    if (isset($_GET["id"])){
        $panel = (int) trim($_GET["id"]);
        $selected = true;
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST"){

    if (isset($_post["id"])){

        // check referral
        $panel = (int) trim($_POST["id"]);
        $selected = true;
    }
}



?>
 
 <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Upload Audio</title>
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="infinity.css">

   
</head>
<body>
    <div class="page-header">
        <h1>Upload Your Audio</h1>
</div>
<?php include 'nav-menu.php';?>

<p>First, make sure you're ready. <a href="prepare.html">Read the guide</a>!</p>

<?php

if ($selected){

} else {
    echo "<h2>Active Panels</h2>\n";
    echo "<p>Click to on a panel to view and upload audio.</p>\n";
    $sql = "SELECT page_img_file, page_id FROM `score_pages` WHERE page_active = 1 ORDER BY page_num";
    if($stmt = $pdo->prepare($sql)){
        if($stmt->execute()){
             while($fetch = $stmt->fetch()){
                $imgfile = "../score_pages/" . $fetch['page_img_file'];
                list($width, $height) = getimagesize($imgfile);
                //echo("" . $width . " ". $height);
                $ratio = $width/$height;
                $scaled = $ratio * 180;
                $id = $fetch["page_id"];

                $data = array(
                    "id" => $id,
                );
    
                $url = "submit.php?" . http_build_query($data);

                echo<<<EOL
<div class="overflow score-gallery">
    <a href="$url"><img src="$imgfile" width="$scaled" height="180" alt="$num"/></a>
</div>
EOL;
            }
        }
    }

}
?>

</body>
</html>

