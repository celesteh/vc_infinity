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


if (! lazy_power_check($_SESSION["id"], $pdo, 60)){
    header("location: index.php");
}

if(!empty(trim($_POST['id']))){
    $page_id = trim($_POST['id']);
} elseif(!empty(trim($_GET['id']))){
    $page_id = trim($_GET['id']);
}


$sql = "SELECT page_img_file FROM `score_pages` WHERE page_id = :pnum";
if($stmt = $pdo->prepare($sql)){
    $stmt->bindParam(":pnum", $param_id, PDO::PARAM_INT);
    $param_id = (int) $page_id;
    if($stmt->execute()){

        if($fetch = $stmt->fetch()){
        $imgfile = "../score_pages/" . $fetch['page_img_file'];
        list($width, $height) = getimagesize($imgfile);
        //echo("" . $width . " ". $height);
        $ratio = $width/$height;
        $scaled = $ratio * 180;
        }
    }
}           

//...

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Order</title>
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="infinity.css">

</head>
<body>
    <div class="page-header">
        <h1>Page Order</h1>
    </div>
    <?php include 'nav-menu.php';?>
    <div class="overflow score-gallery">
				<a href="<?php echo $imgfile ?>"><img src="<?php echo $imgfile ?>" width="<?php echo $scaled ?>" height="180" alt="<?php echo $page_num?>"/></a>
            </div>


    </div>
</body>	
</html>