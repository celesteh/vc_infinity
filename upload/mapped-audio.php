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


if($_SERVER["REQUEST_METHOD"] == "GET"){
 
    // check referral

    if (isset($_GET["id"])){
        $panel =  trim($_GET["id"]);
        $selected = true;
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST"){

    if (isset($_post["id"])){

        // check referral
        $panel = trim($_POST["id"]);
        $selected = true;
    }
}


if ($selected){

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
                    $ratio = $width/$height;
                    $scaleh = 360;
                    $scalew = $ratio * $scaleh;
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
    <title>Listen In Context</title>
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="infinity.css">
    <style>
#myCanvas
{
    pointer-events: none;       /* make the canvas transparent to the mouse - needed since canvas is position infront of image */
    position:absolute;
    z-index: 2;
}

#con{
/* overflow: hidden; */
/* height: 600px;
width: 100%; */
height: <?php echo $scaleh ?>px;
width: <?php echo $scaled ?>px;
}
#mape{
    /*
width:100%;
height:100%;
*/
height: <?php echo $scaleh ?>px;
width: <?php echo $scaled ?>px;
position:relative;

}
    </style>

   
</head>
<body>
    <div class="page-header">
        <h1>Listen in Context</h1>
</div>
<?php include 'nav-menu.php';?>


<?php


if (! $selected) {

    $scorecode = get_score_for_user($_SESSION["id"], $pdo);
    list($title, $composer, $copyright) = get_score_title_and_composer($scorecode, $pdo);

    echo "<h2><i>$title</i> by $composer ©$copyright</h2>\n";
    echo "<p>Click on a $page_called to listen to submitted audio.</p>";

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

                $url = "mapped-audio.php?" . http_build_query($data);

                echo<<<EOL
<div class="scroller score-gallery">
<!--<div class="page-num"><p>$num</p></div>-->
<a href="$url"><img src="$imgfile" width="$scaled" height="180" alt="$num"/></a>
</div>
EOL;
            }
        }
        unset($stmt);
    }
} else {
    echo<<<EOL
    <div class="scroller full-width" id="con" width="$scalew" min-width="$scalew" height="$scaleh">
                    <canvas id="myCanvas" class="scroller" width="$scalew" height="$scaleh"></canvas>
                    <img src="$imgfile" alt="" id="mape" usemap="#img_map" width="$scalew" height="$scaleh" class="scroller">
                    <map name="img_map">
EOL;

    
    $sql = "SELECT sa_x, sa_y, sa_filename FROM `submitted_audio` WHERE sa_pageid = :id";
    if($fstmt = $pdo->prepare($sql)){
        // Bind variables to the prepared statement as parameters
        $fstmt->bindParam(":id", $param_id, PDO::PARAM_INT);
        $param_id = (int)$panel;
        // Attempt to execute the prepared statement
        if($fstmt->execute()){
            while($fetch = $fstmt->fetch()){
                $x = $fetch["sa_x"];
                $y = $fetch["sa_y"];

                $percentx = ($x/$width); //* 100;
                $percenty = ($y/$height);
                $scalex = floor($scalew * $percentx);
                $scaley = floor($scaleh * $percenty);



                $audio ="../unprocessed_audio/" . $fetch['sa_filename'];

                echo <<<EOL
                        <area class="snippet" shape="circle" coords="$scalex,$scaley,5" href="$audio">\n
EOL;
            }
        }
        unset($fstmt);
    }
        
    // <area shape="circle" coords="90,58,3" href="mercur.htm" alt="Mercury">


    echo<<<EOL
                    </map>
    </div>
              
    <script type="text/javascript">
    function drawCir(coOrdStr) {
        var mCoords = coOrdStr.split(',');
        var x, y, r;
        x = mCoords[0];
        y = mCoords[1];
        r = mCoords[2];
        hdc.beginPath();
        hdc.arc(x, y, r, 0, 2 * Math.PI);
        hdc.fill();
        hdc.stroke();
    }
    
    
    function myInit() {
        // get the target image
        var img = document.getElementById('mape'); //byId('mape');
    
        var x, y, w, h;

        //img.height = img.width * (img.clientHeight / img.clientWidth);
        //img.width = img.height * $ratio;
        img.height = img.width / $ratio;
    
        // get it's position and width+height
        x = img.offsetLeft;
        y = img.offsetTop;
        w = img.clientWidth;
        h = img.clientHeight;
    
        // move the canvas, so it's contained by the same parent as the image
        var imgParent = img.parentNode;
        var can = document.getElementById('myCanvas');
        //
         imgParent.appendChild(can);
    
        // place the canvas in front of the image
        can.style.zIndex = 1;
    
        // position it over the image
        can.style.left = x + 'px';
        can.style.top = y + 'px';
    
        // make same size as the image
        //can.setAttribute('width', w + 'px'); //was w
        //can.setAttribute('height', h + 'px'); //was h

        //////can.height = can.width * (can.clientHeight / can.clientWidth);
        //can.height = can.width / $ratio;
    
        // get it's context
        hdc = can.getContext('2d');
    
        // set the 'default' values for the colour/width of fill/stroke operations
        hdc.fillStyle = 'red';
        hdc.strokeStyle = 'red';
        hdc.lineWidth = 2;

        //function arrD(item, index) {
        //    var coordStr = item.getAttribute("coords");
        //    drawCir(coordStr);
        //}
    

        var areas = Array.prototype.slice.call(document.getElementsByTagName("area")); 
        console.log(areas);
        console.log(typeof areas);
        areas.forEach (function (item, index) {
            var coordStr = item.getAttribute("coords");
            drawCir(coordStr);
        })


    
        //$("area").each(function() {
    
        //    var coordStr = $(this).attr('coords');
        //    drawCir(coordStr);
        //});
    
    
    }
    
    

    myInit();
    </script>
EOL;
}


?>

</body>	
</html>