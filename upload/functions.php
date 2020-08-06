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

    $password = randomPassword();
    $hash = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash

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

    $sql = "SELECT role_power_level in roles where role_rolecode = :rolecode";
    if($stmt = $pdo->prepare($sql)){
        // Bind variables to the prepared statement as parameters
        $stmt->bindParam(":rolecode", $role_code, PDO::PARAM_STR);
        if($stmt->execute()){
            // Check if rolecode exists, if yes then get powerlevel
            if($stmt->rowCount() == 1){
                if($row = $stmt->fetch()){
                    $powerlevel = $row["role_power_level"];
                }
            }
        }
        unset($stmt);
    }
    return $powerlevel;

}

function get_power_level_for_user($fuid, $pdo){

    $powerlevel = 0;

    $sql = "SELECT u_rolecode in users where userid = :userid";
    if($stmt = $pdo->prepare($sql)){
        // Bind variables to the prepared statement as parameters
        $stmt->bindParam(":userid", $fuid, PDO::PARAM_STR);
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

?>