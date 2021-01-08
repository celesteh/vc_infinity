<?php
// Initialize the session
session_start();
include_once "config.php";
include_once "functions.php";
 
// Check if the user is logged in, if not then redirect them to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}


// Power check
if (! lazy_power_check($_SESSION["id"], $pdo, 40)){
    header("location: index.php");
}

if(isset($_SESSION["page_called"])){
    $page_called = $_SESSION["page_called"];
} else {
    $page_called = get_page_called_for_user($_SESSION["id"], $pdo);
    $_SESSION["page_called"] = $page_called;
}
$upper = ucfirst($page_called);


// Get an array of active panels
$active = array();
// And more information about inactive panels
$inactive = array();

$sql = "SELECT page_id, page_active, page_num, page_scorecode FROM `score_pages` WHERE 1 ORDER BY page_num";
if($stmt = $pdo->prepare($sql)){
    if($stmt->execute()){
         while($fetch = $stmt->fetch()){

            $id = $fetch["page_id"];
            $is_active = $fetch["page_active"];

            if ($is_active == 1){
                $active[] = $id;
            } else {
                $code = $fetch["page_scorecode"];
                $num = $fetch["page_num"];
                $inactive[$id] = [$code, $num, $id];
            }
         }

    }
    unset($stmt);
}

/*
$sql = "SELECT page_id FROM `score_pages` WHERE page_active = 1";
if($stmt = $pdo->prepare($sql)){
    if($stmt->execute()){
         while($fetch = $stmt->fetch()){
            $imgfile = "../score_pages/" . $fetch['page_img_file'];
            list($width, $height) = getimagesize($imgfile);
            //echo("" . $width . " ". $height);
            $ratio = $width/$height;
            $scaled = $ratio * 180;
            $id = $fetch["page_id"];

            $active[] = $id;
         }

    }
    unset($stmt);
}
*/


// get a list of all tar files in the directory except ones that match $page_called_$id where id is one of the active panels
// 1. Disallowed files
/*
$disallowed = array();
foreach ($active as $id) {
    $disallowed[] = $page_called . "_" . $id;
}
*/

// 2. Get a list of all files in ../to_process

$tar_dir = '../to_process/';
$tars = glob($tar_dir . '*.tar.gz');

// 3. Deal witht e filter if at the print loop stage


// Ok, did the user ask us to package anything?  

if($_SERVER["REQUEST_METHOD"] == "GET"){
 
    // check referral

    if (isset($_GET["id"])){
        $panel =  trim($_GET["id"]);
        $selected = true;
    }
}

// do the packaging

if ($selected) {
    // 1. check permissions
    if (lazy_power_check($_SESSION["id"], $pdo, 50)){
        // 2. check if active
        if (! in_array($panel, $active)) {

            // what file name will we use
            $record = $inactive[$panel];
            $code = $record[0];
            $num = $record[1];
            $tar_target = $code . "_" . $num . ".tar";
            $target_filename =  $tar_target . ".gz";
            // 3. check if the tar.gz already exists
            if (! in_array($target_filename, $tars)) {

                // is there a working directory
                $working_dir = $tar_dir . "working/";
                if( !file_exists( $working_dir ) ) {

                    // make the directory
                    mkdir ($working_dir);
            
                    // make the tar

                    try 
                    {
                        $arc = new PharData($working_dir . $tar_target);

                        // get the list of files to include
                        $dirs = array();
                        $files = array();
                        $sql = "SELECT id, sa_filename FROM `submitted_audio` WHERE (`sa_accepted` is NULL) AND (sa_pageid = :id)";
                        if($fstmt = $pdo->prepare($sql)){
                            // Bind variables to the prepared statement as parameters
                            $fstmt->bindParam(":id", $param_id, PDO::PARAM_INT);
                            $param_id = (int)$panel;
                            // Attempt to execute the prepared statement
                            if($fstmt->execute()){
                                while($fetch = $fstmt->fetch()){
                                    $sa_id = $fetch["id"];
                                    //$accepted = $fetch["accepted"];
                                    $audio_file =  $fetch['sa_filename'];
                                    $source = "../unprocessed_audio/" . $audio_file;

                                    // make a folder in the working dir
                                    $new_dir = $working_dir . $sa_id;
                                    mkdir($new_dir);
                                    $dirs[] = $new_dir;
                                    $new_file = $new_dir . "/" . $audio_file;
                                    $files[] = $new_file;
                                    copy($source, $new_file);

                                    //$arc->buildFromDirectory($new_dir);
                
                                }
                            }   
                            unset($fstmt);
                        }

                        $arc->buildFromDirectory($working_dir, "/.*[^t][^a][^r]$/");
                        //$arc->buildFromDirectory($working_dir);
                    } 
                    catch (Exception $e) 
                    {
                        //    echo &quot;Exception : &quot; . $e;
                        $err = "Archive creation exception: " . $e;
                    }

                    try {
                    //Now compress to tar.gz
                    //file_put_contents('archive.tar.gz' , gzencode(file_get_contents('archive.tar')));
                    file_put_contents($tar_dir . $target_filename,  gzencode(file_get_contents($working_dir . $tar_target)));
                    } catch (Exception $e) {}

                    // Delete the working directory
                    //foreach($files as $file){
                    //    unlink($file);
                    //}
                    //foreach($dirs as $dir){
                    //    rmdir($dir);
                    //}
                    //unlink($working_dir . $tar_target);
                    //rmdir($working_dir);


                } else {
                    // workding dir exists
                    $err = "Another process is creating an archive (or failed to end properly.)  Wait a few minutes and try again. If this persists, please contact your IT manager.";
                }
            } else {
                // the archive exists
                $err = "Your chosen archive already exists";
            }
        } else {
            // We shouldn't export an active panel
            $err = "That $page_called is active";
        }
    } else {
        // Not enough Power
        $err = "I'm sorry Dave. I'm afraid I can't do that.";
    }
}
  
// Now, again! Get a list of all files in ../to_process

$tar_dir = '../to_process/';
$tars = glob($tar_dir . '*.tar.gz');



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Download Audio</title>
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="infinity.css">

</head>
<body>
    <div class="page-header">
        <h1>Download Audio</h1>
    </div>
    <?php include 'nav-menu.php';?>
    </div>
    <div><?php

    // debug
    //echo "<table>\n";
    //foreach($inactive as $record){
    //    echo "<tr><td>$record[0]</td><<td>$record[1]</td><td>$record[2]</td></tr>\n";
    //}
    //echo "</table>\n";

    //echo var_dump($inactive) . "<br>";

// Make a form for powerful users
if (lazy_power_check($_SESSION["id"], $pdo, 50)){

    $self = htmlspecialchars($_SERVER["PHP_SELF"]);
    
    echo<<<EOL
    <div class="wrapper">
    <h2>Export Audio to Tar</h2>
    <p>You can create archives of inactive panels, if the archive you want does not apready exist.</p>
    <form action="$self" method="get">
        <div class="form-group <?php echo (!empty($err)) ? 'has-error' : ''; ?>">
            <label>Choose $upper</label>

            <select name="id" id="id">
EOL;

    // Ok construct an option list
    // <option value="volvo">Volvo</option>
    foreach ($inactive as $available) {
        echo "<option value = \"$available[2]\">$available[0] $page_called $available[1]</option>\n";
        
    }
    


    echo<<<EOL
            </select>
            <span class="help-block">$err</span>
        </div>
        <div class="form-group">
            <input type="submit" class="btn btn-primary" value="Submit">
            <input type="reset" class="btn btn-default" value="Reset">
        </div>
    </form>
    </div>
EOL;
}
    ?>

    <div>
    <h2>Download an Archive</h2>
    <h2>Don't use this yet</h2>
    <p>This system does <em>not</em> yet track if anyone else has already gotten any particular
    arive, so please communicate with your fellow engineers to ensure that you are not duplicating
    effort.</p>
    <p>Each file comes in it's own folder. Every folder is named a number after the file's database ID.</p>
    <p>As you edit, please <em>replace</em> the original file with your edited file(s).</p>
    <p>If the file is too low quality to use, please put in the folder a file called "REJECTED". This can
    be any file type.</p>
    <p>If you notice that the file is a duplicate of another file, please put in the folder a file called
    "DUPLICATE<i>x</i>" where <i>x</i> is the number it duplicates.  For example, if you notice that the 
    file in folder 2 is the same as the one in folder 1, you would put into folder 2 a file called 
    "DUPLICATE1".<p>
    <h3>Files</h3>
    <ul>

    <?php
    foreach($tars as $file){
        echo "<li><a href=\"$file\">$file</a></li>\n";
    }
    ?>
    </ul>
    </div>

</html>