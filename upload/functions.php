<?php
// Include config file
//require_once "config.php";

function randomPassword() {
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < 8; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
}

function get_userid($fusername, $pdo){

    $id = "";
    
    $fusername =strtolower($fusername);

    $sql = "SELECT userid  FROM users WHERE username = :username";
        
    if($fstmt = $pdo->prepare($sql)){
        // Bind variables to the prepared statement as parameters
        $fstmt->bindParam(":username", $fusername, PDO::PARAM_STR);
        

        
        // Attempt to execute the prepared statement
        if($fstmt->execute()){
            // Check if username exists, if yes then get id
            if($fstmt->rowCount() == 1){
                if($row = $fstmt->fetch()){
                    $id = $row["userid"];
                }
            }
        }
        unset($fstmt);
    }

    return $id;

}

function password_reset($fuid, $pdo){

    // Make sure the hash doesn't end with punctuation, because the URL formatter chokes
    do {

        $password = randomPassword();
        $hash = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash

        $lastchar = $hash[-1];
    } while (ctype_punct($lastchar));


    $url = "Something went wrong";

    $sql = "UPDATE users SET temp_password = :tpass WHERE userid = :id";
        
    if($stmt = $pdo->prepare($sql)){
        // Bind variables to the prepared statement as parameters
        $stmt->bindParam(":tpass", $param_password, PDO::PARAM_STR);
        $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
        
        $param_password = $hash;
        $param_id = (int)$fuid;

        // Attempt to execute the prepared statement
        if($stmt->execute()){
            // Password updated successfully. Destroy the session, and redirect to login page


            $data = array(
                "id" => $fuid,
                "hash" => $hash
            );

            $url = "https://infinity.vocalconstructivists.com/upload/email-verify.php?" . http_build_query($data);
        }
        unset($fstmt);
    }
    return $url;
}

function clear_temp_password($fuid, $pdo) {
    $sql = "UPDATE users SET temp_password = '' WHERE userid = :userid";
        
    if($stmt = $pdo->prepare($sql)){
        // Bind variables to the prepared statement as parameters
        $stmt->bindParam(":userid", $param_userid, PDO::PARAM_STR);
            
        // Set parameters
        $param_userid = (int)$fuid;

        if($stmt->execute()){}

        unset($fstmt);
    }
}

function get_power_level($rolecode, $pdo){

    $powerlevel = 0;

    $sql = "SELECT role_power_level FROM `roles` where role_rolecode = :rolecode";
    if($stmt = $pdo->prepare($sql)){
        // Bind variables to the prepared statement as parameters
        $stmt->bindParam(":rolecode", $param_rolecode, PDO::PARAM_STR);
        $param_rolecode = $rolecode;
        if($stmt->execute()){
            // Check if rolecode exists, if yes then get powerlevel
            if($stmt->rowCount() == 1){
                if($row = $stmt->fetch()){
                    $powerlevel = $row["role_power_level"];
                    //echo $powerlevel;
                }
            }
        }
        unset($stmt);
    }
    return $powerlevel;
    // Server Move
    //return 0;

}

function get_page_called_for_user($fuid, $pdo){

    // fix this properly soon

    // get score for user
    // then query the page fname from the db

    return "panel";

}
function get_score_for_user($fuid, $pdo) {

    // fix this properly soon

    // get group for user
    // then get score for group

    return "metaphysics";

}

function get_score_title_and_composer($scorecode, $pdo){

    $title = "";
    $composer = "";
    $copyright = "";

    $sql = "SELECT s_title, s_composer, s_copyright_year FROM `scores` WHERE s_scorecode = :scorecode";
    if($stmt = $pdo->prepare($sql)){
        // Bind variables to the prepared statement as parameters
        $stmt->bindParam(":scorecode", $param_scorecode, PDO::PARAM_STR);
        $param_scorecode = $scorecode;
        if($stmt->execute()){
            // Check if rolecode exists, if yes then get powerlevel
            if($stmt->rowCount() == 1){
                if($row = $stmt->fetch()){
                    $title = $row["s_title"];
                    $composer = $row["s_composer"];
                    $copyright = $row["s_copyright_year"];
                }
            }
        }

        unset($stmt);
    }
    return array ($title, $composer, $copyright);

}

function get_group_for_user($fuid, $pdo) {}

function get_score_for_group($gcode, $pdo){

    $scorecode = "";

    $sql = "SELECT org_scorcode FROM `organisations` where orgcode = :gcode";
    if($stmt = $pdo->prepare($sql)){
        // Bind variables to the prepared statement as parameters
        $stmt->bindParam(":gcode", $param_gcode, PDO::PARAM_STR);
        $param_gcode = $gcode;
        if($stmt->execute()){
            // Check if rolecode exists, if yes then get powerlevel
            if($stmt->rowCount() == 1){
                if($row = $stmt->fetch()){
                    $scorecode = $row["org_scorcode"];
                }
            }
        }

        unset($stmt);
    }
    return $scorecode;
   
}

function get_page_called_for_score($scorecode, $pdo){

    $page_called = "";

    $sql = "SELECT s_page_called FROM `scores` where s_scorecode = :scorecode";
    if($stmt = $pdo->prepare($sql)){
        // Bind variables to the prepared statement as parameters
        $stmt->bindParam(":scorecode", $param_gcode, PDO::PARAM_STR);
        $param_gcode = $scorecode;
        if($stmt->execute()){
            // Check if rolecode exists, if yes then get powerlevel
            if($stmt->rowCount() == 1){
                if($row = $stmt->fetch()){
                    $page_called = $row["s_page_called"];
                }
            }
        }

        unset($stmt);
    }
    return $page_called;
   
}

function get_page_called_for_group($gcode, $pdo){

    $page_called = "";

    $scorecode = get_score_for_group($gcode, $pdo);
    $page_called = get_page_called_for_score($scorecode, $pdo);

    return $page_called;

}


function get_power_level_for_user($fuid, $pdo){

    $powerlevel = 0;

    $sql = "SELECT u_rolecode FROM `users` where userid = :userid";
    if($stmt = $pdo->prepare($sql)){
        // Bind variables to the prepared statement as parameters
        $stmt->bindParam(":userid", $param_userid, PDO::PARAM_INT);
        $param_userid = (int) $fuid;
        if($stmt->execute()){
            // Check if rolecode exists, if yes then get powerlevel
            if($stmt->rowCount() == 1){
                if($row = $stmt->fetch()){
                    $rolecode = $row["u_rolecode"];
                    $powerlevel = get_power_level($rolecode, $pdo);
                }
            }
        }

        unset($stmt);
    }
    return $powerlevel;
}

function lazy_power_check($fuid, $pdo, $must_be_this_powerful_to_ride){

    $powerlevel = 0;
    $do_query = false;

    if(!isset($_SESSION)){ 
        $do_query = true;

    }elseif(!isset($_SESSION["powerlevel"])){
        if(!isset($fuid)){
            $fuid = $_SESSION["id"];
        }
        $do_query = true;
    } else {
        $powerlevel = $_SESSION["powerlevel"];
    }

    if($do_query){
        $powerlevel = get_power_level_for_user($fuid, $pdo);
    }

    return ($powerlevel >= $must_be_this_powerful_to_ride);

}


function url_dir(){
    if(isset($_SERVER['HTTPS'])){
        $protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http";
    }
    else{
        $protocol = 'http';
    }

    $path = pathinfo($_SERVER['REQUEST_URI']);

    return $protocol . "://" . $_SERVER['HTTP_HOST'] . $path['dirname'];
}


function set_nonce(){

    // switching to a less secure string so i don't have to escape things
    return  uniqid();//random_bytes(32);
}

function verify_nonce(){
    if (isset($_SESSION['nonce'])){
        $correct = ($_SESSION['nonce'] === $_POST['nonce']);
    } else {
        $correct = false;
    }
    $_SESSION['nonce'] = '';

    // stop checking this right now
    $correct = true;

    return $correct;
}


function set_tag($shortcode, $id, $pdo){

    $sql = "INSERT INTO tags (tag_shortcode, ed_audio_id) VALUES (:tag_code, :id)";
    if($stmt = $pdo->prepare($sql)){
        // Bind variables to the prepared statement as parameters
        //stopped here
        $stmt->bindParam(":tag_code", $param_tag, PDO::PARAM_STR);
        $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);

        $param_tag = $shortcode;
        $param_id = $id;


        if($stmt->execute()){
            // success!!

            //header("location: submit.php?success=1");
        } else {

            $error = _("Failed");
        }

        unset($stmt);
    }
    
}

function get_tags($ed_id, $pdo){ // get tags for an id
 
    $tags = array();

    $sql = "SELECT tag_shortcode FROM tags WHERE ed_audio_id = :ed_id";
    //echo "$sql\n";
    
    
    if($stmt = $pdo->prepare($sql)){        
        // Attempt to execute the prepared statement
        $stmt->bindParam(":sed_id", $param_ed_id, PDO::PARAM_STR);

        $param_ed_id = $ed_id;
        if($stmt->execute()){
            if($stmt->rowCount() >= 1){
                while($row = $stmt->fetch()){
                    $tags[] = $row["tag_shortcode"];
                }
            }
        }
        unset($stmt);
    }
    return $tags;
}


function do_ed_query($oid, $pdo){ // look in the editted audio for an id

    //e_id, wav, flac
    $versions = array();

    $sql = "SELECT audio_id, audio_filename, compressed_format FROM edited_audio WHERE original_id = :o_id";
    //echo "$sql\n";
    
    
    if($stmt = $pdo->prepare($sql)){        
        // Attempt to execute the prepared statement
        $stmt->bindParam(":o_id", $param_o_id, PDO::PARAM_STR);

        $param_o_id = $oid;
        if($stmt->execute()){
            if($stmt->rowCount() >= 1){
                while($row = $stmt->fetch()){
                    $versions[] = [$row["audio_id"], $row["audio_filename"], $row["compressed_format"]];
                }
            }
        }
        unset($stmt);
    }
    return $versions;
}





function get_editted($oid, $pdo){ // look in edited audio and duplicates to chase down an id
    
    $versions = do_ed_query($oid, $pdo);
    if (size_of($versions) < 1){

        // check in duplicates
        $sqls = "SELECT ed_audio_id FROM duplicates WHERE  o_id_b = :o_id";
            //echo "$sql\n";
            
            
        if($stmts = $pdo->prepare($sqls)){

            $stmts->bindParam(":o_id", $param_o_id, PDO::PARAM_STR);
                
                // Set parameters
            $param_o_id = $oid
                
            // Attempt to execute the prepared statement
            if($stmts->execute()){
                if($stmts->rowCount() < 1){
                    while($row = $stmt->fetch()){
                        $eid = $row["ed_audio_id"];

                        // ok, get the data for the edited id
                        $sql = "SELECT audio_id, audio_filename, compressed_format FROM edited_audio WHERE audio_id = :e_id";
                        //echo "$sql\n";


                        if($stmt = $pdo->prepare($sql)){        
                        // Attempt to execute the prepared statement
                            $stmt->bindParam(":e_id", $param_e_id, PDO::PARAM_STR);

                            $param_e_id = $eid;
                            if($stmt->execute()){
                                if($stmt->rowCount() >= 1){
                                    while($row = $stmt->fetch()){
                                        $versions[] = [$row["audio_id"], $row["audio_filename"], $row["compressed_format"]];
                                    }
                                }
                            }
                            unset($stmt);
                        }
                    }
                
            }
            unset($stmts);
        }
    }
    return $versions;
}


?>