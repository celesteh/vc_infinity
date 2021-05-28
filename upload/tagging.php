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

// Navigation

if (isset($_GET['pageno'])) {
    $pageno = $_GET['pageno'];
} else {
    $pageno = 1;
}
$no_of_records_per_page = 10;

$count = $pdo->query("select count(*) FROM `edited_audio` WHERE   `compressed_format` IS NOT NULL")->fetchColumn(); 
$total_pages = ceil($count / $no_of_records_per_page);

if ($pageno < 1) {
    $pageno = 1;
} elseif ($pageno > $total_pages) {
    $pageno  = $total_pages;
}


$offset = ($pageno-1) * $no_of_records_per_page; 


$self = htmlspecialchars($_SERVER["PHP_SELF"]);
$first = "$self?pageno=1";
if ($pageno <= 1) { $prev = "#"; $pclass = "disabled";} else { $prev = $self . "?pageno=".($pageno - 1); $pclass = ""; }
if($pageno >= $total_pages){  $next = '#'; $nclass = "disabled";}  else { $next = $self.  "?pageno=".($pageno + 1); $cnlass =""; }
$last = $self. "?pageno=". $total_pages; 


// get tags

//$tags = array();

$sql = "SELECT tag_shortcode, tag_text, tag_parent, tag_hidden FROM `available_tags` WHERE 1";
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
            $avail_tags[$shortcode] = $text;
        }
    }

    // Close statement
    unset($stmt);
}

$avail_metadata = array();
// metadata_shortcode, metadata_text, metadata_low_label, metadata_high_label
$sql = "SELECT metadata_shortcode, metadata_text, metadata_low_label, metadata_high_label FROM `available_metadata` WHERE 1";
if($stmt = $pdo->prepare($sql)){
    if($stmt->execute()){
        while($row = $stmt->fetch()){


            //$fshortcode = htmlspecialchars($row["tag_shortcode"]);
            //$ftext = htmlspecialchars($row["tag_text"]);
            //$fparent  = htmlspecialchars($row["tag_parent"]);
            //$fhidden = $row["tag_hidden"];

            //array_push($tags, $fshortcode);
            $text = htmlspecialchars($row["metadata_text"]);
            $shortcode = htmlspecialchars($row["metadata_shortcode"]);
            $low = htmlspecialchars($row["metadata_low_label"]);
            $high = htmlspecialchars($row["metadata_high_label"]);
            $avail_metadata[] = [$text, $shortcode, $low, $high]; 
        }
    }

    // Close statement
    unset($stmt);
}

// Audio files
$sql = "SELECT audio_id, compressed_format, original_id  FROM `edited_audio` WHERE   `compressed_format` IS NOT NULL LIMIT $offset, $no_of_records_per_page"; 
if($stmt = $pdo->prepare($sql)){
    if($stmt->execute()){
        while($row = $stmt->fetch()){
            $audio_id = $row["audio_id"];
            // tags & metdata
            $tags = get_tags($audio_id, $pdo);
            $metadata = get_metadata($audio_id, $pdo);
            $audio[$audio_id] = [$row["original_id"], $row["compressed_format"], $tags, $metadata];
        }
    }
    unset($stmt);
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Metadata</title>
    <link rel="stylesheet" href="range.css">
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

var tags = <?php echo json_encode($avail_tags) ?>;// don't use quotes
//var dragee;

function allowDrop(ev) {
//  ev.preventDefault();
}

function dragstart_handler(ev) {
 console.log("dragStart");
 //dragee = item;
 ev.dataTransfer.setData("text", ev.target.id);
 ev.dataTransfer.dropEffect = "copy";
}

function drop_handler(event) {
    var tag_key, audio_key, ul_key, ul, li, input_key, input, values;
    console.log("drop_handler");
    event.preventDefault();
    tag_key = event.dataTransfer.getData("Text");
    console.log(tag_key);
    audio_key = event.target.id;
    console.log(audio_key);

    // Add the item to the hidden input
    //id = "'. $key . '_input"
    input_key = audio_key + "_tags";
    console.log(input_key);
    input = document.getElementById(input_key);
    console.log(input.value);
    values = input.value.split(', ');
    console.log(values);

    if (values.indexOf(tag_key)< 0) { // This tag is not already present
        input.value += (tag_key + ", ");

        // now add it to the visible list
        ul_key = audio_key.concat("_ul");
        console.log(ul_key);
        ul = document.getElementById(ul_key);
        console.log(ul.id);
        li = document.createElement('li');
        li.draggable = true;
        //li.addEventListener("drop", function (evt) {
            //
        //});
        //li.ondragstart="dragstart_handler(event)";
  
        li.innerHTML += tags[tag_key];
        li.id = audio_key + '_' + tag_key;

        li.addEventListener("dragstart", dragstart_handler);
        //li.addEventListener("click", function() {console.log("click")});

        ul.appendChild(li);
    }
    /*
    var id = ev.target.id;
    // Get the id of the target and add the moved element to the target's DOM
    const data = ev.dataTransfer.getData("text/plain");
    var li = document.createElement('li');
    li.innerHTML += tags[data];
    li.id = data + "_li";
    ////ev.target.appendChild(document.getElementById(data));
    //var li = document.getElementById(data);
    ////var id = li.id;
    ////ul = ev.target.getElements("ul");
    var ul = document.getElementById(data + "_input" );
    ul.appendChild(li);
    */
}

function on_hover(ev) {
    console.log("hovering");
    ev.preventDefault();
}

function make_tag_list(){
    //var ul = document.createElement('ul');
    //document.getElementById('tags').appendChild(ul);

    var ul = document.getElementById("taglist");

   
    //$.each(tags, function(key, value) {
    var value;
    Object.keys(tags).forEach((key) => {
        value = tags[key];
        //console.log('stuff : ' + key + ", " + value);
        var li = document.createElement('li');
        li.draggable = true;
        //li.addEventListener("drop", function (evt) {
            //
        //});
        //li.ondragstart="dragstart_handler(event)";
  
        li.innerHTML += value;
        li.id = key;

        li.addEventListener("dragstart", dragstart_handler);
        li.addEventListener("click", function() {console.log("click")});

        ul.appendChild(li);

    });

}

</script>


</head>
<body onload="make_tag_list();">
    <div class="page-header">
        <h1><b>Audio Metadata</b></h1>
    </div>

    <?php include 'nav-menu.php';?>
    
    
    <div>
    <p><a href="manage-tags.php">Manage available tags</a></p>
    </div>
    
    
    <?php
        echo <<< EOT
        <ul class="pagination">
            <li><a href="$first">First</a></li>
            <li class="$pclass"><a href="$prev">Prev</a></li>
            <li class ="$nclass"><a href="$next">Next</a></li>
            <li><a href="$last">Last</a></li>
        </ul>
EOT;
    ?>
    

    <div class="tagbox" id="tags">
        <!div class = "container" id = "tags">
            <h3>Tags</h3>
            <ul id="taglist">
            </ul>
         <!/div>
    </div>


    <div class="container">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class = "overflow">
        <table>

            <?php
            echo "<tr><th>ID</th><th>Audio</th>"; 
            foreach($avail_metadata as $datum){
                //$avail_metadata[] = [$text, $shortcode, $low, $high];
                echo "<th>" . $datum[0] . " (" . $datum[2] . " - " .  $datum[3] . ")</th>";
            }
            echo "<th>Tags</th></tr>\n";

            foreach ($audio as $key => $values){
                //echo "Key is: ".$key.", "."Value is: ".$val;
                //echo "<br>";
                //$audio[$audio_id] = [$row["original_id"], $row["compressed_format"], $tags, $metadata];
                $dir = $values[0];
                $file = $values[1];
                $local = "../processed_audio/$dir/$file";

                $tags = $values[2];
                $metadata = $values[3];

                // first column, the ouput:
                echo '<tr><td>' . $key . '</td><td><audio controls="controls" src="'.$local.'" type="audio/flac" /></td>';

                // next n columns - the scores
                foreach($avail_metadata as $datum){
                    $shortcode = $datum[1];
                    unset($score);

                    if (isset($metadata)) {
                        $score = $metadata[$shortcode];
                    }

                    $id = $key . "_" . $shortcode;
                    echo '<td><select name="' . $id . '" id="' . $id  . '">';
                    $selected = "";
                    if (! isset($score)) {
                        $selected = "selected";
                    }
                    echo '<option value="-1" ' . $selected . '>' . $score . '</option>\n';
                    
                    for ($x = 1; $x <= 5; $x++) {
                        $selected = "";
                        if (isset($score)){
                            if ($score == $x){
                                $selected = "selected";
                            }
                        }
                        echo '<option value="' . $x . '" ' . $selected . ">" . $x . '</option>\n'; 
                    }
                    echo "</select></td>";
                }

                // last cloumn, tags
                echo '<td><div ondragover="on_hover(event)" ondrop="drop_handler(event)" class="bordered" id="'. $key . '"><ul id="' . $key. '_ul"> ';
                $hidden = '<input name="' . $key . '_tags" id = "'. $key . '_tags" type="hidden" value="'; 
                foreach ($tags as $tag){
                    echo '<li draggable="true" id="' . $key . '_' . $tag . '">' . $avail_tags[$tag] . "</li>";
                    $hidden = $hidden . $tag . ", ";
                }
                echo '</ul>'. $hidden . '"></div></td>';
                


                // end the row
                echo "</tr>\n";
            }

            //<div class="range-slider">
            //<input class="range-slider__range" type="range" value="3" min="1" max="5">
            //<span class="range-slider__value">3</span>
            //</div>
            ?>
        </table>
    </div>


    <!--
    <div>
    <p>This is experimental dev code below</p>
    <h3>Tags</h3>
    <div id="tags"></div>
        <ul id="taglist">
        </ul>
    </div>
    <h3>Target</h3>
    <table id="audiolist">
        <tr id="tr1" ondrop="drop('row1')"><td>Sample 1</td><td id="row1" ></td></tr>
        <tr id="tr2"><td>Sample 1</td><td id="row2" ondrop="drop(event)"></td></tr>
    </table>
    <p>Drag the W3Schools image into the rectangle:</p>


<div id="target" ondrop="drop(event)" ondragover="allowDrop(event)"></div>
<br>
<img id="drag1" src="img_logo.gif" draggable="true" ondragstart="drag(event)" width="336" height="69">
    -->
        </div>
    </body>
</html>
