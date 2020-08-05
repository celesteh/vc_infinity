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
        $stmt->bindParam(":tpass", $hash, PDO::PARAM_STR);
        $stmt->bindParam(":id", $fid, PDO::PARAM_INT);
        
        
        // Attempt to execute the prepared statement
        if($stmt->execute()){
            // Password updated successfully. Destroy the session, and redirect to login page


            $data = array(
                "id" => $fuid,
                "hash" => $hash
            );

            $url = http_build_query($data);
        }
        unset($fstmt);
    }
    return $url;
}

function clear_temp_password($fuid, $pdo) {
    $sql = "UPDATE users SET temp_password = '' WHERE userid = :id";
        
    if($stmt = $pdo->prepare($sql)){
        if($stmt->execute()){}

        unset($fstmt);
    }
}

?>