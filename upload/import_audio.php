<?php
// Initialize the session
session_start();
 
include_once "config.php";
include_once "functions.php";
 
// Check if the user is logged in, if not then redirect him to login page
//if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
//    header("location: login.php");
//    exit;
//}


//if (! lazy_power_check($_SESSION["id"], $pdo, 60)){
//    header("location: index.php");
//}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Testing directory traversal</title>
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="infinity.css">

</head>
<body>
    <div class="page-header">
        <h1><b>Testing directory traversal</h1>
    </div>

    <?php include 'nav-menu.php';?>
    <div>
    <ul>



<?php


$wav_dir = "../wavs/";
$flac_dir = "../processed_audio/";

$flacless = array();
$duplicates = array();

$error = "";

function accept($accedpted_id){
    set_accepted(1,$accedpted_id);
}


function reject($rejected_id){
    set_accepted(0, $rejected_id);
}


function set_accepted($accepted, $id){

    global $pdo;

    $sa_sql = "UPDATE submitted_audio SET sa_accepted =:accept WHERE id = :id";
    if($astmt = $pdo->prepare($sa_sql)){
        // Bind variables to the prepared statement as parameters
        $astmt->bindParam(":accept", $param_accept, PDO::PARAM_INT);
        $astmt->bindParam(":id", $param_id, PDO::PARAM_INT);
        
        // Set parameters
        $param_accept = $accepted;
        $param_id = (int)$id;
        //$param_id = $_SESSION["id"];
        
        // Attempt to execute the prepared statement
        if($astmt->execute()){
            
            echo "added to the database and marked as accepted\n";
        } else{
            echo _("Oops! Something went wrong. Please try again later.");
        }

        // Close statement
        unset($astmt);
    } else {

        //$error = _("Upload failed");
    }

}


function get_ids($original_id){

    global $pdo;

    $ids = array();

    $sql = "SELECT audio_id FROM edited_audio WHERE original_id = :id";
    //echo "$sql\n";
    
    
    if($stmt = $pdo->prepare($sql)){
        // Bind variables to the prepared statement as parameters
        $stmt->bindParam(":id", $param_id, PDO::PARAM_STR);
        
        // Set parameters
        $param_id = $original_id;
        
        // Attempt to execute the prepared statement
        if($stmt->execute()){
            if($stmt->rowCount() >= 1){
                $exists = true;
                // does flac version of the file exist, though?
                $flac_in_db = false;
                while($row = $stmt->fetch()){
                    $ids[] = $row["audio_id"];
                }
            }
        }
        unset($stmt);
    }
    return $ids;

}




if ($handle = opendir($wav_dir)) {
    while (false !== ($file = readdir($handle))) {
        //echo "reading files\n";
        if ($file != "." && $file != "..") {
            //echo "$wav_dir/$file\n";
            if( is_dir( "$wav_dir/$file" ) ){
                $id = $file;
                echo "<li> $id\n";
                if($wav_handle = opendir("$wav_dir/$file")) {
                    echo "<ul>\n";
                    while (false !== ($wavfile = readdir($wav_handle))) {
                        $wav_path = "$wav_dir/$file/$wavfile";
                        if (is_file($wav_path)){
                            if ($wavfile != "." && $wavfile != ".." && $wavfile != "" && (! str_starts_with($wavfile, "."))) {
                                $path = pathinfo($wav_path);
                                $name = (string) $path['filename']; // it must be a string
                                $new_id = -1;
                                

                                //echo "<li>$name\n";
                                
                                $rejected = str_ends_with($name, 'REJECT'); 
                                //echo "$rejected\n";
                                // check if the file is a reject
                                if (! $rejected) {
                                    echo "not rejected";
                                
                                    // check if the file is a duplicate
                                    if (str_contains($name, 'DUP')) {
                                        // If so, add it to an array of duplicates -- we'll get to them later
                                        //echo "duplicated\n";
                                        // Parse the file name to find the duplication

                                        list($prefix, $oldid) = explode("DUP",$name);

                                        $duplicates[] = array($oldid, $id);
                                        echo "<li>$name $oldid\n";

                                    } else {
                                        // check if the ID exists in the DB as a submitted file -
                                        $found = false;
                                        
                                        // Prepare a select statement
                                        $sql = "SELECT sa_filename sa_accepted FROM submitted_audio WHERE id = :id";
                                        
                                        if($stmt = $pdo->prepare($sql)){
                                            // Bind variables to the prepared statement as parameters
                                            $stmt->bindParam(":id", $param_id, PDO::PARAM_STR);
                                            
                                            // Set parameters
                                            $param_id = $id;
                                            
                                            // Attempt to execute the prepared statement
                                            if($stmt->execute()){
                                                if($stmt->rowCount() == 1){
                                                    $found = true;
                                                    if($row = $stmt->fetch()){
                                                        $accepted = $row["sa_accepted"];
                                                        if ($accepted == 0){
                                                            $rejected = true;
                                                        }
                                                    }
                                                }
                                            } else{
                                                echo _("Oops! Something went wrong. Please try again later.");
                                            }

                                            // Close statement
                                            unset($stmt);
                                        }

                                        //echo "found $found rejected $rejected\n";

                                        // check if a flac version exists of this file
                                        $flac_path = "$flac_dir/$file/$name.flac";
                                        //echo "flac file $flac_path\n";

                                        $flac_in_db = false;

                                        
                                        
                                        // check if the edited audio table already includes this ID.
                                        // if present, does it have a compressed and uncompressed version?
                                        if ($found && (! $rejected)){
                                            //$exists = true; //assume we're NOT going to add it
                                            $sql = "SELECT compressed_format, audio_filename FROM edited_audio WHERE original_id = :id";
                                            //echo "$sql\n";
                                            
                                            
                                            if($stmt = $pdo->prepare($sql)){
                                                // Bind variables to the prepared statement as parameters
                                                $stmt->bindParam(":id", $param_id, PDO::PARAM_STR);
                                                
                                                // Set parameters
                                                $param_id = $id;
                                                
                                                // Attempt to execute the prepared statement
                                                if($stmt->execute()){
                                                    if($stmt->rowCount() >= 1){
                                                        $exists = true;
                                                        // does flac version of the file exist, though?
                                                        $flac_in_db = false;
                                                        while($row = $stmt->fetch()){
                                                            $flac = $row["compressed_format"];
                                                            $wav = $row["audio_filename"];
                                                            if (isset($flac)){
                                                                //$exists = true;
                                                                //echo "flac is $flac name is $name\n";
                                                                if ($flac == "$name.flac"){
                                                                    $exists = true;
                                                                    $flac_in_db = true;
                                                                }
                                                            } else {
                                                                //$exists = false;
                                                                // we can't make assumptions
                                                            }
                                                        } 
                                                        $exists = $flac_in_db;
                                                    } else {
                                                        $exists = false;
                                                    }
                                                } else{
                                                    echo _("Oops! Something went wrong. Please try again later.");
                                                }

                                                // Close statement
                                                unset($stmt);
                                            }
                                            
                                        }
                                        //echo "flac_in_db $flac_in_db\n";
                                        /*
                                        // if no flac version, and the wav isn't in the table, save the ID to an array and just add the wav
                                        // this really shouldn't happen
                                        // so we'll skip it
                                        if (not file_exists($flac_path)){
                                            if (not isset($wav)){
                                                $sql = "INSERT INTO edited_audio (audio_filename, original_id) VALUES (:wav_file, :id)";
                                                if($stmt = $pdo->prepare($sql)){
                                                    // Bind variables to the prepared statement as parameters
                                                    //stopped here
                                                    $stmt->bindParam(":wav_file", $param_wav, PDO::PARAM_STR);
                                                    $stmt->bindParam(":id", $param_id, PDO::PARAM_STR);

                                                    $param_wav = "$file/$name\.wav";
                                                    $param_id = $id;
                                        

                                                    //if($stmt->execute()){
                                                        // success!!
                                                        //$conn->exec($sql);
                                                    //    $new_id = $pdo->lastInsertId();

                                                        //header("location: submit.php?success=1");
                                                    //} else {

                                                    //    $error = _("Failed");
                                                    //}

                                                    echo "no flac $sql\n";

                                                }
                                                unset($stmt);
                                            }
                                            $flacless[] = $id;
                                        }
                                        else {*/
                                        if (file_exists($flac_path)){ 
                                            if (! $flac_in_db && ! $rejected){ 
                                                
                                                // if no record, add the file and the flac version to the DB
                                                $sql = "INSERT INTO edited_audio (compressed_format, audio_filename, original_id) VALUES (:flac_file,  :wav_file, :id)";
                                                //echo "$sql\n";
                                                if($stmt = $pdo->prepare($sql)){
                                                    
                                                    // Bind variables to the prepared statement as parameters
                                                    //stopped here
                                                    $stmt->bindParam(":flac_file", $param_flac, PDO::PARAM_STR);
                                                    $stmt->bindParam(":wav_file", $param_wav, PDO::PARAM_STR);
                                                    $stmt->bindParam(":id", $param_id, PDO::PARAM_STR);

                                                    $param_flac = "$name.flac";
                                                    $param_wav = "$name.wav";
                                                    $param_id = $id;
                                        

                                                    
                                                    if($stmt->execute()){
                                                        // success!!

                                                        $new_id = $pdo->lastInsertId();

                                                        //header("location: submit.php?success=1");
                                                        accept($id);
                                                    }
                                                    
                                                    unset($stmt);
                                                }
                                                
                                                
                                            }
                                            
                                        } //$flac_path is a file
                                        
                                    } //not a duplicate
                                       
                                    // check if the file is processed
                                    // if processed - auto-tag it
                                    //unset($tag);
                                    $tags = array();
                                    if (str_contains($name, 'REP')) {
                                        // repeats
                                        $tags[] = "rep";
                                    } 
                                    if (str_contains($name, 'REV')) {
                                        $tags[] = "rev";
                                    }
                                    if (str_contains($name, 'SLOW')) {
                                        $tags[] = "slow";
                                    }
                                    if ($new_id > -1) {
                                        foreach ($tags as $tag){
                                            set_tag($tag, $new_id, $pdo);
                                            /*
                                            $sql = "INSERT INTO tags (tag_shortcode, ed_audio_id) VALUES (:tag_code, :id)";
                                            if($stmt = $pdo->prepare($sql)){
                                                // Bind variables to the prepared statement as parameters
                                                //stopped here
                                                $stmt->bindParam(":tag_code", $param_tag, PDO::PARAM_STR);
                                                $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);

                                                $param_tag = $tag;
                                                $param_id = $new_id;
                                    

                                                if($stmt->execute()){
                                                    // success!!

                                                    //header("location: submit.php?success=1");
                                                } else {

                                                    $error = _("Failed");
                                                }
                                            }
                                            */
                                        }
                                        
                                    }
                                    
                                } else {// is a reject
                                    //$sql = "UPDATE users SET u_password = :password WHERE userid = :id";
                                    // set the rejection flag
                                    /*
                                    $sql = "UPDATE submitted_audio SET sa_accepted =:accept WHERE id = :id";
                                    if($stmt = $pdo->prepare($sql)){
                                        // Bind variables to the prepared statement as parameters
                                        $stmt->bindParam(":accept", $param_accept, PDO::PARAM_INT);
                                        $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
                                        
                                        // Set parameters
                                        $param_accept = 0; // reject
                                        $param_id = (int)$id;
                                        //$param_id = $_SESSION["id"];
                                        
                                        // Attempt to execute the prepared statement
                                        if($stmt->execute()){
                                            echo "reject logged\n";    
                                        } else{
                                            echo _("Oops! Something went wrong. Please try again later.");
                                        }
                            
                                        // Close statement
                                        unset($stmt);
                                        echo "$sql UPDATE submitted_audio SET sa_accepted = $param_accept WHERE id = $param_id\n";
                                    }
                                    */

                                    reject($id);
                                    
                                    //echo "rejected\n";
                                }     
                            } // if not a dot file
                        } // if is_file()     
                    } // while wav_handle
                    closedir($wav_handle);
                    echo "</ul>\n";
                } // if we opened the wav_handle
                   
            
            } else {// if we're in a sub directory
                echo "<li>not a directory\n";
            }
        } // if not a .file
    } // while reading wav_dir
    closedir($handle);

    // handle duplicates
    foreach ($duplicates as $duplicate){
        //$duplicates[] = array($oldid, $id);
        $oldid = $duplicate[0];
        $newid = $duplicate[1];

        // get the edited audio id for the old id
        $found = get_ids($oldid);

        foreach ($found as $processed){

            // check if it's already in the table
            $sqls = "SELECT o_id_a FROM duplicates WHERE ed_audio_id = :edited_id AND o_id_b = :o_new_id";
            //echo "$sql\n";
            
            
            if($stmts = $pdo->prepare($sqls)){

                // Bind variables to the prepared statement as parameters
                $stmts->bindParam(":edited_id", $param_edited_id, PDO::PARAM_STR);
                $stmts->bindParam(":o_new_id", $param_o_new_id, PDO::PARAM_STR);
                
                // Set parameters
                $param_edited_id = $processed;
                $param_o_new_id = $newid;
                
                // Attempt to execute the prepared statement
                if($stmts->execute()){
                    if($stmts->rowCount() < 1){

                        // if it's not already in the table, put it in
                        $sql = "INSERT INTO duplicates (o_id_a, o_id_b, ed_audio_id) VALUES (:old_id, :new_id, :edited_id)";
                        if($stmt = $pdo->prepare($sql)){
                            // Bind variables to the prepared statement as parameters
                            //stopped here
                            $stmt->bindParam(":old_id", $param_oldid, PDO::PARAM_STR);
                            $stmt->bindParam(":new_id", $param_newid, PDO::PARAM_STR);
                            $stmt->bindParam(":edited_id", $param_eddid, PDO::PARAM_STR);

                            $param_oldid = $oldid;
                            $param_newid = $newid;
                            $param_eddid = $processed;


                            if($stmt->execute()){
                                // success!!

                                //header("location: submit.php?success=1");
                                accept($newid);
                            } else {

                                $error = _("Failed");
                            }
                            unset($stmt);
                        }
                    }
                }
                unset($stmts);
            }
        }
    }



} else {// open wav dir
    echo "$wav_dir did not open\n";
}
?>
    </ul>
    </div>
    <div><p>We tried to put new files in the DB. <?php echo $error ?></p></div>
</body>
</html>    
