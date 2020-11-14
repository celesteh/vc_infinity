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


if (isset($_GET['pageno'])) {
    $pageno = $_GET['pageno'];
} else {
    $pageno = 1;
}

$count = $pdo->query("select count(*) FROM `submitted_audio` WHERE   (`sa_accepted` is NULL) OR (`sa_accepted` = 1)")->fetchColumn(); 
$total_pages = ceil($count / $no_of_records_per_page);

if ($pageno < 1) {
    $pageno = 1;
} elseif ($pageno > $total_pages) {
    $pageno  = $total_pages;
}


$no_of_records_per_page = 20;
$offset = ($pageno-1) * $no_of_records_per_page; 

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
        <h1>Listen to Uploaded Audio</h1>
    </div>
    <?php include 'nav-menu.php';?>
    <div>
<?php

#$csql = "SELECT sa_userid, sa_pageid, sa_filename FROM `submitted_audio` WHERE   (`sa_accepted` is NULL) OR (`sa_accepted` = 1) "; 
#if($cstmt = $pdo->prepare($csql)){
#    if($cstmt->execute()){
#        $count = $cstmt->rowCount();
#    }
#}

 
$sql = "SELECT sa_userid, sa_pageid, sa_filename FROM `submitted_audio` WHERE   (`sa_accepted` is NULL) OR (`sa_accepted` = 1) LIMIT $offset, $no_of_records_per_page"; 
            if($stmt = $pdo->prepare($sql)){
                if($stmt->execute()){
                    //$count = $stmt->rowCount();
                    //if($count >= 50 ){
                        
                        //echo "<p>$total_pages pages</p>\n";
                        $self = htmlspecialchars($_SERVER["PHP_SELF"]);
                        $first = "$self?pageno=1";
                        if ($pageno <= 1) { $prev = "#"; $pclass = "disbaled";} else { $prev = $self . "?pageno=".($pageno - 1); $pclass = ""; }
                        if($pageno >= $total_pages){  $next = '#'; $nclass = "disabled";}  else { $next = $self.  "?pageno=".($pageno + 1); $cnlass =""; }
                        $last = $self. "?pageno=". $total_pages; 
                        echo <<< EOT
<ul class="pagination">
<li><a href="$first">First</a></li>
<li class="$pclass">
    <a href="$prev">Prev</a>
</li>
<li class ="$nclass">
    <a href="$next">Next</a>
</li>
<li><a href="$last">Last</a></li>
</ul>
EOT;

                        echo "\n<ol start=\"". (($no_of_records_per_page * ($pageno - 1)) + 1) ."\">\n";
                        while($row = $stmt->fetch()){
                            $local = "../unprocessed_audio/" . $row['sa_filename'];
                            echo '<li><audio controls="controls" src="'.$local.'" type="audio/wav" /></li>';
                            echo "\n";

                        };
                        echo "</ol>\n";
                    //} else {
                    //    echo "<p>This feature will unlock when 50 audio files are uploaded.</p>\n";
                    //    echo "<p>There are currently " . strval($count) . " submissions. </p>\n";
                    //}
                }
            }
    

?>
</div> 
</body>	
</html>