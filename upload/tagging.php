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


$correct_nonce = verify_nonce();
//if (! $correct_nonce){
$_SESSION['nonce'] = set_nonce();
    //}

$edior = lazy_power_check($_SESSION["id"], $pdo, 60);


// get tags

//$tags = array();

$sql = "SELECT tag_shortcode, tag_text, tag_parent FROM `available_tags` WHERE tag_hidden = 0";
if($stmt = $pdo->prepare($sql)){
    if($stmt->execute()){
        while($row = $stmt->fetch()){


            //$fshortcode = htmlspecialchars($row["tag_shortcode"]);
            //$ftext = htmlspecialchars($row["tag_text"]);
            //$fparent  = htmlspecialchars($row["tag_parent"]);
            //$fhidden = $row["tag_hidden"];

            //array_push($tags, $fshortcode);
            $text = htmlspecialchars($row["tag_text"]);
            $shortcode = $row["tag_shortcode"];
            $tags[$text] = $shortcode;
        }
    }

    // Close statement
    unset($stmt);
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tagging</title>
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="infinity.css">
    <style>
#target {
  width: 350px;
  height: 70px;
  padding: 10px;
  border: 1px solid #aaaaaa;
}
</style>
<script>
//function allowDrop(ev) {
//  ev.preventDefault();
//}

function drag(ev) {
  ev.dataTransfer.setData("text", ev.target.id);
}

function drop(ev) {
  ev.preventDefault();
  var data = ev.dataTransfer.getData("text");
  ev.target.appendChild(document.getElementById(data));
}
</script>


</head>
<body>
    <div class="page-header">
        <h1><b>Audio Tagging</b></h1>
    </div>

    <?php include 'nav-menu.php';?>
    <div>
    <p><a href="manage-tags.php">Manage available tags</a></p>
    </div>
    <div>
    <p>This is experimental dev code below</p>
    <h3>Tags</h3>
    <div id="tags"></div>
		<script type="text/javascript">
		//var names = ['vitosh','academy','dot','com'];
        var ul = document.createElement('ul');
		document.getElementById('tags').appendChild(ul);

        var tags = <?php echo json_encode($tags) ?>;// don't use quotes
        $.each(tags, function(key, value) {
			var li = document.createElement('li');
			ul.appendChild(li);
			li.innerHTML += key;

        });
 
		</script>
    </div>
    <p>Drag the W3Schools image into the rectangle:</p>


<div id="target" ondrop="drop(event)" ondragover="allowDrop(event)"></div>
<br>
<img id="drag1" src="img_logo.gif" draggable="true" ondragstart="drag(event)" width="336" height="69">


    </body>
</html>
