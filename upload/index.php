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

if(isset($_SESSION["page_called"])){
    $page_called = $_SESSION["page_called"];
} else {
    $page_called = get_page_called_for_user($_SESSION["id"], $pdo);
    $_SESSION["page_called"] = $page_called;
}
$upper = ucfirst($page_called);

?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome</title>
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="infinity.css">

</head>
<body>
    <div class="page-header">
        <h1><b><?php echo htmlspecialchars($_SESSION["realname"]); ?></b> @ <?php echo $_SITE["title"]; ?></h1>
    </div>

    <?php include 'nav-menu.php';?>
    <div>
        <h2>News</h2>
            <p><a href=" https://drive.google.com/drive/folders/1VC-zzSXsKdw8d9Q9BDMyomGY8dglAX3x?usp=sharing">Upload your videos!</a> For our film for the festival.</p>
            <p>New <a href="prepare.html">how-to video</a> for phone users!</p>
            <p><a href="mapped-audio.php">View anchor points</a> used so far!</p>
            <p>We have passed the 50 upload threshold! <a href="https://infinity.vocalconstructivists.com/upload/user-audio.php">Listen here</a> to what others have submitted!</p>
            <p>It is now possible to record directly from your phone or tablet!</p>
    </div>
    <?php 
    //echo $_SESSION["powerlevel"];
    if ($_SESSION["powerlevel"] < 20){
        $approval_required = _("Your account must be approved before you can participate.");
        echo "<p>" . $approval_required . "</p>\n";
    } else {
        // Get list of active pages
        // Make links to submit audio pased on page_id

        echo "<div>\n<h2>Active {$upper}s</h2>\n";
        echo "<p>Click on a {$page_called} to zoom in and upload audio.</p>\n</div>\n";
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
