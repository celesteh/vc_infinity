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
$no_of_records_per_page = 10;


$next_submit = "Submit and Next";
$prev_submit = "Submit and Previous";
$stationary_submit = "Submit";


if (isset($_GET['pageno'])) {
    $pageno = $_GET['pageno'];
} elseif (isset ($_POST['pageno'])){
    $pageno = $_POST['pageno'];
} else {
    $pageno = 1;
}

if (isset($_POST['submit'])){
    $submit = $_POST['submit'];
    if ($submit == $next_submit){
        $pageno += 1;
    } elseif ($submit == $prev_submit){
        $pageno -= 1;
    }
}




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
if ($pageno <= 1) { 
    $prev = "#"; $pclass = "disabled";
    $psclass = "btn-disabled";
} else { 
    $prev = $self . "?pageno=".($pageno - 1); $pclass = ""; $psclass = "";
}

if($pageno >= $total_pages){  
    $next = '#'; $nclass = "disabled";
    $nsclass = "btn-disabled";
}  else { 
    $next = $self.  "?pageno=".($pageno + 1); $cnlass =""; $nsclass = "";
}
$last = $self. "?pageno=". $total_pages; 


// Handle Post data
if($_SERVER["REQUEST_METHOD"] == "POST"){
    foreach ($_POST as $key => $value){
        list($audio_id, $shortcode) = split($key, "_");
        if ((isset($shortcode)) && ($shortcode != "")){
            if ($shortcode == "tags"){
                // handle tags
                $db_tags = get_tags($audio_id, $pdo);
                $post_tags = split($value, ", ");

                // First go through db_tags
                foreach($db_tags as $dtag){
                    $found = array_search($dtag, $post_tags);
                    if(! $found){
                        // A tag has been removed
                        $sql = "DELETE FROM `tags` WHERE `ed_audio_id` = :audio_id AND `tag_shortcode` = :shortcode";
                        if($stmt = $pdo->prepare($sql)){
                            // Bind variables to the prepared statement as parameters
                            $stmt->bindParam(":shortcode", $param_shortcode, PDO::PARAM_STR);
                            $stmt->bindParam(":audio_id", $param_audio_id, PDO::PARAM_STR);
                            $param_shortcode = $shortcode;
                            $param_audio_id = $audio_id;
                            //$stmt->execute(); // Don't test if it worked. If it fails, then the item was probably already blank
                            unset($stmt);
                        }
                    } else {
                        // remove the item from the tag array
                        unset($post_tags[$found]);
                    }
                }

                // Any tags left in the post_tags list need to be added to the db
                foreach($post_tags as $ptag){
                    $sql = "INSERT INTO metadata (tag_shortcode, ed_audio_id) VALUES (:shortcode,  :audio_id)";
                    if($stmt = $pdo->prepare($sql)){
                        // Bind variables to the prepared statement as parameters
                        $stmt->bindParam(":shortcode", $param_shortcode, PDO::PARAM_STR);
                        $stmt->bindParam(":audio_id", $param_audio_id, PDO::PARAM_STR);
                        $param_shortcode = $ptag;
                        $param_audio_id = $audio_id;
                        //$stmt->execute();
                        unset($stmt);
                    }
                }

            } else { 
                // handle metadata
                if ($value == -1){
                    // remove this metadata item
                    $sql = "DELETE FROM `metadata` WHERE `ed_audio_id` = :audio_id AND `metadata_shortcode` = :shortcode";
                    if($stmt = $pdo->prepare($sql)){
                        // Bind variables to the prepared statement as parameters
                        $stmt->bindParam(":shortcode", $param_shortcode, PDO::PARAM_STR);
                        $stmt->bindParam(":audio_id", $param_audio_id, PDO::PARAM_STR);
                        $param_shortcode = $shortcode;
                        $param_audio_id = $audio_id;
                        //$stmt->execute(); // Don't test if it worked. If it fails, then the item was probably already blank
                        unset($stmt);
                    }
                } else {
                    // set this metadata item      
                    $sql = "INSERT INTO metadata (metadata_shortcode, ed_audio_id, metadata_value) VALUES (:shortcode,  :audio_id, :score)";
                    if($stmt = $pdo->prepare($sql)){
                        // Bind variables to the prepared statement as parameters
                        $stmt->bindParam(":shortcode", $param_shortcode, PDO::PARAM_STR);
                        $stmt->bindParam(":audio_id", $param_audio_id, PDO::PARAM_STR);
                        $stmt->bindParam(":score", $param_score, PDO::PARAM_INT);
                        $param_shortcode = $shortcode;
                        $param_audio_id = $audio_id;
                        $param_score = (int) $value;
                        //$stmt->execute();
                        unset($stmt);
                    }
             
                }
            }
        }
    }
}



// get tags

//$tags = array();

$sql = "SELECT tag_shortcode, tag_text, tag_parent, tag_hidden FROM `available_tags` WHERE tag_hidden =0";
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



var removable_tag = function(li, ul) {
    console.log("removable_tag");
    return function remove_tag(event) {
        // do something here
        console.log("remove_tag");
        ul.removeChild(li);
        return true;
    }
}

var remove_by_id = function(li_id, ul_id){
    var li = document.getElementById(li_id);
    var ul = document.getElementById(ul_id);
    console.log("remove_by_id")
    return removable_tag(li, ul);
}


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
    //console.log("drop_handler");
    event.preventDefault();
    tag_key = event.dataTransfer.getData("Text");
    //console.log(tag_key);
    audio_key = event.target.id;
    audio_key = audio_key.split("_")[0]; //This line really shouldn't be necessary and yet it is
    //console.log(audio_key);

    // Add the item to the hidden input
    //id = "'. $key . '_input"
    input_key = audio_key + "_tags";
    //console.log(input_key);
    input = document.getElementById(input_key);
    //console.log(input.value);
    values = input.value.split(', ');
    //console.log(values);

    if (values.indexOf(tag_key)< 0) { // This tag is not already present
        input.value += (tag_key + ", ");

        //console.log(input.id);

        // now add it to the visible list
        ul_key = audio_key.concat("_ul");
        //console.log(ul_key);
        ul = document.getElementById(ul_key);
        //console.log(ul.id);
        li = document.createElement('li');
        //li.draggable = true;
        //li.addEventListener("drop", function (evt) {
            //
        //});
        //li.ondragstart="dragstart_handler(event)";
  
        li.innerHTML += tags[tag_key] + "&nbsp";
        li.id = audio_key + '_' + tag_key;

        //li.addEventListener("dragstart", dragstart_handler);
        //li.addEventListener("click", function() {console.log("click")});


        // Make tags removable

        // Create anchor element.
        var a = document.createElement('a'); 
                  
        // Create the text node for anchor element.
        var link = document.createTextNode("x");
                    
        // Append the text node to anchor element.
        a.appendChild(link); 
                    
        // Set the title.
        a.title = "Remove tag"; 
                    
        // Set the href property.
        a.href = "https://www.geeksforgeeks.org";

        a.addEventListener("click", removable_tag(li, ul));
                    
        // Append the anchor element to the list item
        li.appendChild(a);

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
        //li.addEventListener("click", function() {console.log("click")});
        li.classList.add("tagitem");

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
    
    <! debugging >
    <!--
    <pre>
        <?php var_dump($_POST); ?>
    </pre>
    -->

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
            <h3 class = "tagheader">Tags</h3>
            <ul id="taglist">
            </ul>
         <!/div>
    </div>


    <div class="container">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <input type="hidden" name="pageno" id="pageno" value="<?php echo htmlspecialchars($pageno); ?>">
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
                $ulid = $key. '_ul';
                echo '<td><div ondragover="on_hover(event)" ondrop="drop_handler(event)" class="bordered" id="'. $key . '"><ul id="'. $ulid . '"> ';
                $hidden = '<input name="' . $key . '_tags" id = "'. $key . '_tags" type="hidden" value="'; 
                foreach ($tags as $tag){
                    $liid = $key . '_' . $tag;
                    echo '<li  id="' . $liid . '">' . $avail_tags[$tag] . '&nbsp<a title="Click to remove tag"
                    onclick="remove_by_id(\''.$liid .'\',\''. $ulid .'\');return false;">x</a></li>'; // draggable="true"
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
    <div class="row">
                <div class="col-50l">&nbsp;</div><div class="col-50r">
                <input type="submit" class="btn btn-primary <?php echo $nsclass ?>" name="submit" value="<?php echo $next_submit ?>"> &nbsp;
                <input type="submit" class="btn btn-default" name="submit" value="<?php echo $stationary_submit ?>"> &nbsp;
                <input type="submit" class="btn btn-default <?php echo $psclass ?>" name="submit" value="<?php echo $prev_submit ?>"> &nbsp;
                <input type="reset" class="btn btn-default" value="Reset"> &nbsp;
            </div>
        </div>
        </form>

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
