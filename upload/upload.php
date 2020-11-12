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
$upload = false;
$ok = false;
$active = false;

$correct_nonce = verify_nonce();
//if (! $correct_nonce){
$_SESSION['nonce'] = set_nonce();
    //}



 if ($_SERVER["REQUEST_METHOD"] == "POST"){

    if (isset($_POST["id"]) && isset($_POST["panel_x"]) && isset($_POST["panel_y"]) && isset($_POST["scaled_width"]) && isset($_POST["scaled_height"]) ){

        // check referral
        $panel = trim($_POST["id"]);
        $selected = true;
        $scaled_x = trim($_POST["panel_x"]);
        $scaled_y = trim($_POST["panel_y"]);
        $scaled_width = trim($_POST["scaled_width"]);
        $scaled_height = trim($_POST["scaled_height"]);
    
    
    }elseif (isset($_POST["submit"]) && isset($_POST["id"]) && isset($_POST["x"]) && isset($_POST["y"])) {


        $submit = $_POST["submit"];

        // is this an upload?

        // was the nonce ok?


        if (! $correct_nonce){
            header("location: submit.php?err=doubled");
        }
        
        $panel = trim($_POST["id"]);
        $x = trim($_POST["x"]);
        $y = trim($_POST["y"]);

        
        $upload = true;

        $file_type=$_FILES['audio']['type'];
        $is_audio = preg_match("/^audio/", $file_type) && (! preg_match("/midi/", $file_type));

        if (! $is_audio){
            $error = _("Please upload an audio recording.");
        } else {

            // carry on

            $file_size =$_FILES['audio']['size'];
            // maximum file size should be 100 MB
            // or can we get file duration??

            if ($file_size > 104857600) {
                $error = _("The maximum duration is 90 seconds and this file is too big.");
            }  else{

                // move the file

                $target_dir = "../unprocessed_audio/";
                //$target_file = $target_dir . basename($_FILES["audio"]["name"]); // fix this to name the file correctly

                //get the file path
                $finfo = pathinfo($_FILES["audio"]["name"]);

                // generate a file name
                $date = new DateTime();
                        //echo $date->getTimestamp();
                $filename = $date->getTimestamp() . "_" . $_SESSION["id"]  . "." . $finfo['extension']; 
                $target_file = "{$target_dir}/{$filename}"; // hopefully unique

                $i = 1;
                while (file_exists($target_file)){
                    $filename =  $date->getTimestamp() . "_" .  $_SESSION["id"]  . "_{$i}." . $finfo['extension']; // we'll get there
                    $target_file = "{$target_dir}/{$filename}";
                    $i = $i+1;
                }

                       
                if (move_uploaded_file($_FILES["audio"]["tmp_name"], $target_file)) { 


                    $ok = true;
                }

            }

        }
        

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

if ($selected || $upload ){

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

                    if (! $upload) {

                        list($width, $height) = getimagesize($imgfile);
                        //echo("" . $width . " ". $height);

                        $x = ($scaled_x * $width) / $scaled_width;
                        $y = ($scaled_y * $height) / $scaled_height;
                    } elseif( $ok) {

                        // make a database record

                        $sql = "INSERT INTO submitted_audio (sa_userid, sa_pageid, sa_x, sa_y, sa_filename) VALUES (:userid,  :pageid, :x, :y, :filename)";
                        if($stmt = $pdo->prepare($sql)){
                            // Bind variables to the prepared statement as parameters
                            $stmt->bindParam(":userid", $param_userid, PDO::PARAM_INT);
                            $stmt->bindParam(":pageid", $param_pageid, PDO::PARAM_INT);
                            $stmt->bindParam(":x", $param_x, PDO::PARAM_STR);
                            $stmt->bindParam(":y", $param_y, PDO::PARAM_STR);
                            $stmt->bindParam(":filename", $filename, PDO::PARAM_STR);

                            $param_userid = $_SESSION["id"];
                            $param_pageid = (int)$panel;
                            $param_x = (string) $x;
                            $param_y = (string) $y;

                            if($stmt->execute()){
                                // success!!

                                header("location: submit.php?success=1");
                            } else {

                                $error = _("Upload failed");
                            }

                        }
                        unset($stmt);
                    }
                }
            }
        }
        unset($fstmt);
    }
}

if (! $active){
    header("location: submit.php?err=inactive");
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
<?php echo "<!-- is_audio {$is_audio} id {$panel} scaled_x {$scaled_x} scaled_y {$scaled_y} scaled_width {$scaled_width} scaled height {$scaled_height} X {$x} Y {$y} panel {$panel} active {$active} submit {$submit} ok {$ok} selected {$selected} upload {$upload} error {$error}  -->"; ?>
<div class="wrapper">

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
<div class="form-group <?php echo (!empty($error)) ? 'has-error' : ''; ?>">
                <span class="help-block"><?php echo $error; ?></span>
            </div>
  <div class="form-group">
  Select an audio file to upload:
  <label class="custom-file-upload">
 
    <input type="file" name="audio" id="audio">
    <!--<i class="fa fa-cloud-upload"></i> Browse… -->
</label>
  </div>
  <input type="hidden" id="x", name = "x", value="<?php echo $x ?>">
  <input type="hidden" id="y", name = "y", value="<?php echo $y ?>">
  <input type="hidden" id="id", name = "id", value="<?php echo $panel ?>">
  <input type="hidden" id="nonce", name ="nonce", value="<?php echo $_SESSION['nonce'] ?>">
  <input type="submit" value="Upload Audio" name="submit">
</form>
</div>
<!-- Record from phone -->
<div class="wrapper">
<hr noshade>
<form action="recorder.php" method="post">
<div class="form-group">
<p><b>Testing</b></p>
<p>Phone and tablet users can record directly from their microphones:</p>
</div> 
  <input type="hidden" id="x", name = "x", value="<?php echo $x ?>">
  <input type="hidden" id="y", name = "y", value="<?php echo $y ?>">
  <input type="hidden" id="id", name = "id", value="<?php echo $panel ?>">
  <input type="hidden" id="nonce", name ="nonce", value="<?php echo $_SESSION['nonce'] ?>">
  <input type="submit" value="Record Audio" name="submit">
</form>
</div>
</body>
</html>

