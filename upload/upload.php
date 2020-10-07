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

$selected = false;
$panel = -1;


 if ($_SERVER["REQUEST_METHOD"] == "POST"){

    if (isset($_POST["id"]) && isset($_POST["panel_x"]) && isset($_POST["panel_y"]) && isset($_POST["scaled_width"]) && isset($_POST["scaled_height"]) ){

        // check referral
        $panel = trim($_POST["id"]);
        $selected = true;
        $scaled_x = trim($_POST["panel_x"]);
        $scaled_y = trim($_POST["panel_y"]);
        $scaled_width = trim($_POST["scaled_width"]);
        $scaled_height = trim($_POST["scaled_height"]);
    } else {
        header("location: submit.php?id=" . $_POST["id"]);
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "GET"){

    // panel.x=503& panel.y=187& id=8& scaled_width=2520& scaled_height=360
    if (isset($_GET["id"]) && isset($_GET["panel_x"]) && isset($_GET["panel_y"]) && isset($_GET["scaled_width"]) && isset($_GET["scaled_height"]) ){

        // check referral
        $panel = trim($_GET["id"]);
        $selected = true;
        $scaled_x = trim($_GET["panel_x"]);
        $scaled_y = trim($_GET["panel_y"]);
        $scaled_width = trim($_GET["scaled_width"]);
        $scaled_height = trim($_GET["scaled_height"]);
    } else {
        header("location: submit.php?id=" . $_GET["id"]);
    }
}

if ($selected){

    $active = false;

    $sql = "SELECT page_img_file, page_id, page_active, page_num FROM `score_pages` WHERE page_id = :id";
    if($fstmt = $pdo->prepare($sql)){
        // Bind variables to the prepared statement as parameters
        $fstmt->bindParam(":id", $param_id, PDO::PARAM_INT);
        $param_id = (int)$panel;
        // Attempt to execute the prepared statement
        if($fstmt->execute()){
            // Check if username exists, if yes then get id
            if($fstmt->rowCount() == 1){
                if($row = $fstmt->fetch()){
                    $active = (bool) $row["page_active"];
                    $imgfile =  "../score_pages/" . $row["page_img_file"];
                    $page_num = (int) $row["page_num"];

                    list($width, $height) = getimagesize($imgfile);
                    //echo("" . $width . " ". $height);

                    $x = ($scaled_x * $width) / $scaled_width;
                    $y = ($scaled_y + $height) / $scaled_height;
                }
            }
        }
        unset($fstmt);
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
        <h1>Submit Your Audio</h1>
</div>
<?php include 'nav-menu.php';?>
<?php echo "id {$panel} scaled_x {$scaled_x} scaled_y {$scaled_y} scaled_width {$scaled_width} scaled height {$scaled_height} X {$x} Y {$y} panel {$panel} active {$active}"; ?>
<div class="wrapper">

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
  
  <div class="form-group">
  Select an audio file to upload:
  <label class="custom-file-upload">
 
    <input type="file" name="fileToUpload" id="fileToUpload">
    <i class="fa fa-cloud-upload"></i> Browse…
</label>
  </div>
  <input type="hidden" id="x", name = "x", value="<?php echo $x ?>">
  <input type="hidden" id="y", name = "y", value="<?php echo $y ?>">
  <input type="hidden" id="id", name = "id", value="<?php echo $panel ?>">
  <input type="submit" value="Upload Audio" name="submit">
</form>
</div>

</body>
</html>

