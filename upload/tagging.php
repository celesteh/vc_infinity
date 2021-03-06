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
    $next = $self.  "?pageno=".($pageno + 1); $nclass =""; $nsclass = "";
}
$last = $self. "?pageno=". $total_pages; 



// get available tags and metadata
$avail_tags = get_available_tags($pdo);
$avail_metadata = get_available_metadata($pdo);


// Handle Post data
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $tag_codes = array_keys($avail_tags);

    foreach ($_POST as $key => $value){
        //if (isset($value) && ($value != "")){
        unset($audio_id);
        unset($shortcode);
        list($audio_id, $shortcode) = explode( "_", $key); // This is a stupid name for a function
        if ((isset($shortcode)) && ($shortcode != "")){
            $shortcode = clean_shortcode($shortcode);
            if ($shortcode == "tags"){
                // handle tags
                $db_tags = get_tags($audio_id, $pdo);
                if (isset($value) && ($value != "")){
                    $post_tags = explode(", ", $value);
                } else {
                    $post_tags = array();
                }
                //$post_tags = array_unique($post_tags);
                $db_tags = array_unique($db_tags);

                // clean up whatever came in from the form
                $lower_az_p_tags = array();
                foreach($post_tags as $ptag){
                    //echo "unclean ptag: " . $ptag . "<br>\n";
                    $ptag = clean_shortcode($ptag);
                    //echo "clean ptag: " . $ptag . "<br>\n";
                    if (in_array($ptag, $tag_codes)) {
                        $lower_az_p_tags[] = $ptag;
                        //echo "ptag: " . $ptag . "<br>\n";
                    }
                }
                $lower_az_p_tags = array_unique($lower_az_p_tags);

                //$only_in_db = array_diff($db_tags, $lower_az_p_tags);
                $only_in_post = array_diff($lower_az_p_tags, $db_tags);


                //echo $db_tags;
                //echo $post_tags;

                // First go through db_tags
                foreach($db_tags as $dtag){
                    //echo "db $audio_id: $dtag <br>\n";
                    $dtag = trim($dtag);
                    if (isset($dtag) && ($dtag != "")){
                        if(! in_array($dtag, $lower_az_p_tags)) { // try thisinstead of a diff
                        //echo "db: " . $dtag . "\n";
                        // A tag has been removed
                            //echo "delete: $dtag $key $value <br>\n";

                            $sql = "DELETE FROM `tags` WHERE `ed_audio_id` = :audio_id AND `tag_shortcode` = :shortcode";
                            if($stmt = $pdo->prepare($sql)){
                                // Bind variables to the prepared statement as parameters
                                $stmt->bindParam(":shortcode", $param_shortcode, PDO::PARAM_STR);
                                $stmt->bindParam(":audio_id", $param_audio_id, PDO::PARAM_STR);
                                $param_shortcode = $dtag;
                                $param_audio_id = $audio_id;
                                $stmt->execute(); // Don't test if it worked. If it fails, then the item was probably already blank
                                unset($stmt);
                            }
                        } //else { echo "$dtag in post for $audio_id<br>\n";}
                    } 
                }

                // Any tags left in the post_tags list need to be added to the db
                foreach($only_in_post as $ptag){
                    //$ptag = trim($ptag);
                    if (isset($ptag) && ($ptag != "")){
                        //echo "add: $ptag $key $value <br>\n";
                        //function set_tag($shortcode, $id, $pdo)
                        set_tag($ptag, $audio_id, $pdo);
                    }
                }

            } else { 
                // handle metadata
                if (isset($value) && ($value != "")){
                    if ($value == -1){
                        //echo "Unset metadata $audio_id shortcode $shortcode value $value<br>\n";
                        // remove this metadata item
                        $sql = "DELETE FROM `metadata` WHERE `ed_audio_id` = :audio_id AND `metadata_shortcode` = :shortcode";
                        if($stmt = $pdo->prepare($sql)){
                            // Bind variables to the prepared statement as parameters
                            $stmt->bindParam(":shortcode", $param_shortcode, PDO::PARAM_STR);
                            $stmt->bindParam(":audio_id", $param_audio_id, PDO::PARAM_STR);
                            $param_shortcode = $shortcode;
                            $param_audio_id = $audio_id;
                            $stmt->execute(); // Don't test if it worked. If it fails, then the item was probably already blank
                            unset($stmt);
                        }
                    } else {
                        //echo "Set metadata $audio_id shortcode $shortcode value $value<br>\n";
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
                            $stmt->execute();
                            unset($stmt);
                        }
                
                    }
                }
            }
        }
        //}
    }
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
    <!link rel="stylesheet" href="range.css">
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



var removable_tag = function(li, ul, input_key, tag_key) {
    //console.log("removable_tag");
    return function remove_tag() {
        // do something here
        //console.log("remove_tag");

        var input = document.getElementById(input_key);
        //console.log(input.value);
        values = input.value.split(', ');
        //console.log(values);

        index = values.indexOf(tag_key);
        if (index > -1) {
          values.splice(index, 1);
        }

        input.value = values.join(", ");

        ul.removeChild(li);
        return false; // dont' open a link
    }
}

function remove_by_id (li_id, ul_id, input_key, tag_key){
    var li = document.getElementById(li_id);
    var ul = document.getElementById(ul_id);
    //console.log("remove_by_id")
    var func =  removable_tag(li, ul, input_key, tag_key);
    return func();
}


function allowDrop(ev) {
//  ev.preventDefault();
}

function dragstart_handler(ev) {
 //console.log("dragStart");
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
        li.classList.add("intable");

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
        //a.href = "https://www.geeksforgeeks.org";

        a.addEventListener("click", removable_tag(li, ul, input_key, tag_key));
                    
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
    //console.log("hovering");
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
        <h3 class="tagheader">Tags</h3>
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
                        $score="";
                    }
                    echo '<option value="-1" ' . $selected . '>&nbsp;</option>\n';
                    
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
                $hiddenid = $key . '_tags';
                echo '<td><div ondragover="on_hover(event)" ondrop="drop_handler(event)" class="bordered" id="'. $key . '"><ul id="'. $ulid . '"> ';
                $hidden = '<input name="' . $hiddenid . '" id = "'. $hiddenid . '" type="hidden" value="'; 
                foreach ($tags as $tag){
                    $liid = $key . '_' . $tag;
                    echo '<li  class = "intable" id="' . $liid . '">' . $avail_tags[$tag] . '&nbsp<a title="Click to remove tag"
                    onclick="remove_by_id(\''.$liid .'\', \''. $ulid .'\', \''.$hiddenid .'\', \'' . $tag . '\');return false;">x</a></li>'; // draggable="true"
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
                <!input type="reset" class="btn btn-default" value="Reset"> &nbsp;
            </div>
        </div>
        </form>


   <div>
       <h2>How to tag</h2>
       <p>If you are accessing this page via a tablet or mobile device, you should use portait mode.</p>
       <p>Audio files have scores from 1-5 for some types of metadata and can also have tags.</p>
       <p>Listen to a file and score it using the 
           drop down menus to decide if it is at the low end of the range or high. When you are scoring, please be sure that you are 
           using the whole range, so that you have 1s and 5s in your scores. You may need to listen to several files before starting to score.
           (Except in the case of the quality score, where hopefully all the files are of relatively high quality.)</p>
        <p>You can assign tags to files by dragging them to the right-most column.  To erase a tag that you think is is inappropriate 
            (or that you assigned by accident), click the 'x' at the end of the tag.  You can safely try this using the test tag.</p>
        <p>If you would like to create a tag, go to <a href="manage-tags.php">Manage available tags</a>.</p>
        <p>There are three submit buttons. Submit sends your changes to the database and then shows you all current values. Submit and Next
            sends your values to the database and then takes you to the next page of audio files. Submit and Previous takes you
            to the pevious page.  There is no Reset button, so if you need to clear all your changes, you'll have to close the page in your 
            browser.</p>
        <h3>How to create a new tag</h3>
        <p>On the <a href="manage-tags.php">Manage available tags</a> page, you will see a list of existing tags. They have a shortcode,
        which is a single, lower-case word and the tag text, which is what you see in the list of tags.</p>
        <p>They may also have a parent.
        The parent of the "Alto" tag is "Voiced", forexample. If you wanted to create a Tenor tag, for example, it would also 
        have the same parent.  If you wanted to create a tag describing a specific kind of tenor, that might have Tenor as it's parent.</p>
        <p>Tags may be visible or hidden.  The tag "Voiced" is hidden.  Anything that is voiced should be tagged with one of 
            it's children, which will be more precise about what kind of voiced sound it is. You can make new children when you need them.</p>
    </body>
</html>
