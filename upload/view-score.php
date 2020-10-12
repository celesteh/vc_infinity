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
    <title>Score</title>
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="infinity.css">

</head>
<body>
    <div class="page-header">
        <h1>View Score</h1>
    </div>
    <?php include 'nav-menu.php';?>
<!-- Title and author -->
<?php
$scorecode = get_score_for_user($_SESSION["id"], $pdo);
list($title, $composer, $copyright) = get_score_title_and_composer($scorecode, $pdo);

echo "<h2><i>$title</i> by $composer ©$copyright</h2>\n";
?>
    <?php


$sql = "SELECT page_img_file, page_num, page_id FROM `score_pages` WHERE 1 ORDER BY page_num";
if($stmt = $pdo->prepare($sql)){
    if($stmt->execute()){
         while($fetch = $stmt->fetch()){
            $imgfile = "../score_pages/" . $fetch['page_img_file'];
            list($width, $height) = getimagesize($imgfile);
            //echo("" . $width . " ". $height);
            $ratio = $width/$height;
            $scaled = $ratio * 180;
            $num = $fetch["page_num"];
            $id = $fetch["page_id"];

            $data = array(
                "id" => $id,
            );

            $url = "score-page.php?" . http_build_query($data);

            echo<<<EOL
<div class="overflow score-gallery">
<!--<div class="page-num"><p>$num</p></div>-->
<a href="$url"><img src="$imgfile" width="$scaled" height="180" alt="$num"/></a>
</div>
EOL;
}
}
}
?>
<!--
<a class="btn btn-link" href="index.php">Go Home</a>-->
</div>
</body>	
</html>