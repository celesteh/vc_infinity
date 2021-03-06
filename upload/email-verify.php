<?php
// Initialize the session
session_start();
 
// Check if the user is already logged in, if yes then redirect him to welcome page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php");
    exit;
}
 
// Include config file
require "config.php";
require_once "functions.php";

// Define variables and initialize with empty values
$password = $confirm_password = $email = "";
$password_err = $confirm_password_err = "";
$iderror = $hasherror = $unknownerror = $novalueserror = $notrequestederror = "";

$username = "";

$success = FALSE;

// Define variables and initialize with empty values
$new_password = $confirm_password = "";
$new_password_err = $confirm_password_err = "";


function verify_user($fuid, $fhash){

    // using globals is bad practice and should not be done. 
    global $iderror;
    global $hasherror;
    global $pdo;
    global $unknownerror;
    global $notrequestederror;
    global $username;


    $success = FALSE;

    if (empty($fuid)){
        $iderror = _("Please check your email for a link.");
    }

    if (empty($fhash)){
        $hasherror = _("Please make sure you use the entire link sent to you from the most recent email sent to you. [Empty]");
    }

    if ((empty($iderror) && empty($hasherror))){

        $sql = "SELECT username, userid, temp_password FROM users WHERE userid = :userid";

        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":userid", $param_userid, PDO::PARAM_STR);
            
            // Set parameters
            $param_userid = (int)$fuid;
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Check if username exists, if yes then verify password
                if($stmt->rowCount() == 1){
                    if($row = $stmt->fetch()){
                        $id = $row["userid"];
                        $username = $row["username"];
                        $hashed_password = $row["temp_password"];
                        
                        if ($hashed_password == ""){
                            $notrequestederror = "Password (re)set request not sent";
                        } else {

                            //if(password_verify($fhash, $hashed_password)){
                            if ($fhash == $hashed_password){
                                // we can show the password change form
                                $success = True;
                            } else {
                                $hasherror = _("Please make sure you use the entire link sent to you from the most recent email sent to you. [Wrong]");
                                $success = false;
                            }
                        }
                    }   
                } else {
                    $unknownerror = _("Please make sure you use the entire link sent to you.");
                }
            }else{
                echo _("Oops! Something went wrong. Please try again later.");
            }
           // Close statement
           unset($stmt);
        }
    }
    
    return $success;
    
}
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "GET"){
 
    // check referral

    if (isset($_GET["id"]) && isset($_GET["hash"])) {
        $userid = trim($_GET["id"]);
        $hash = trim($_GET["hash"]);


        $success = verify_user($userid, $hash);
    } else {

        $novalueserror = _("Please check your email for a link to this page and be sure to include everything after the ?");
        $success = false;

    }

    
    // Close connection
    unset($pdo);

} elseif ($_SERVER["REQUEST_METHOD"] == "POST"){

        // check referral
    $userid = trim($_POST["id"]);
    $hash = trim($_POST["hash"]);
    
    $success = verify_user($userid, $hash);

    if($success){
        //Validate new password
        if(empty(trim($_POST["new_password"]))){
            $new_password_err = _("Please enter the new password.");     
        } elseif(strlen(trim($_POST["new_password"])) < 6){
            $new_password_err = _("Password must have atleast 6 characters.");
        } else{
            $new_password = trim($_POST["new_password"]);
        }
        
        // Validate confirm password
        if(empty(trim($_POST["confirm_password"]))){
            $confirm_password_err = _("Please confirm the password.");
        } else{
            $confirm_password = trim($_POST["confirm_password"]);
            if(empty($new_password_err) && ($new_password != $confirm_password)){
                $confirm_password_err = _("Password did not match.");
            }
        }
            
        // Check input errors before updating the database
        if(empty($new_password_err) && empty($confirm_password_err)){
            // Prepare an update statement
            $sql = "UPDATE users SET u_password = :password WHERE userid = :id";
            
            if($stmt = $pdo->prepare($sql)){
                // Bind variables to the prepared statement as parameters
                $stmt->bindParam(":password", $param_password, PDO::PARAM_STR);
                $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
                
                // Set parameters
                $param_password = password_hash($new_password, PASSWORD_DEFAULT);
                $param_id = (int)$userid;
                //$param_id = $_SESSION["id"];
                
                // Attempt to execute the prepared statement
                if($stmt->execute()){
                    // Password updated successfully. Destroy the session, and redirect to login page
                    clear_temp_password($userid, $pdo);
                    session_destroy();
                    header("location: login.php");
                    exit();
                } else{
                    echo _("Oops! Something went wrong. Please try again later.");
                }
    
                // Close statement
                unset($stmt);
            }
        }
    }

    
    // Close connection
    unset($pdo);
}
?>

 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password</title>
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="infinity.css">

</head>
<body>


    <div class="wrapper <?php echo ($success) ? 'invisible' : ''; ?>">
        <h2>Error</h2>
        <p><?php echo $iderror ?> <?php echo $hasherror ?> <?php echo $unknownerror ?> <?php echo $novalueserror ?>
            <?php echo $notrequestederror ?> 
        </p>
    </div>
    <div class="wrapper <?php echo ($success) ? '' : 'invisible'; ?>"> 
        <h2>Set Password</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post"> 
        <div class="form-group">
                <label>Username</label>
                <input type="text" id="username" name="username" value="<?php echo $username ?>" readonly><br>
            </div>
            <div class="form-group <?php echo (!empty($new_password_err)) ? 'has-error' : ''; ?>">
                <label>New Password</label>
                <input type="password" name="new_password" class="form-control" value="<?php echo $new_password; ?>">
                <span class="help-block"><?php echo $new_password_err; ?></span>
            </div>
            <div class="form-group <?php echo (!empty($confirm_password_err)) ? 'has-error' : ''; ?>">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control">
                <span class="help-block"><?php echo $confirm_password_err; ?></span>
            </div>
            <input name="id" type="hidden" value="<?php echo $userid; ?>" />
            <input name="hash" type="hidden" value="<?php echo $hash; ?>" />  
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Submit">
                <input type="reset" class="btn btn-default" value="Reset">
            </div>
        </form>
    </div>    
    </body>
</html>