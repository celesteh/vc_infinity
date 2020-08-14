<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
 
// Include config file
require_once "config.php";
require_once "functions.php";


// Get marketing preference
$legacy_marketing = False;
if(isset($_SESSION["marketing"])) {
    $legacy_marketing = $_SESSION["marketing"];
} else {
    $sql = "SELECT u_can_contact FROM users WHERE userid = :id";
    if($stmt = $pdo->prepare($sql)){
        // Bind variables to the prepared statement as parameters
        $stmt->bindParam(":id", $_SESSION["id"], PDO::PARAM_INT);

        if($stmt->execute()){
            if($stmt->rowCount() == 1){
                if($row = $stmt->fetch()){
                    $legacy_marketing = $row["u_can_contact"];
                }
            }
        }
        unset($stmt);
    }
}

// Define variables and initialize with empty values
$new_password = $confirm_password = "";
$new_password_err = $confirm_password_err = "";
$new_name = $name_status = "";
$renamed = FALSE;
$url = $url_err= "";
$update_msg = "";

$updated = FALSE;

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
     
    // Change name
    $new_name = trim($_POST["realname"]);
    if (($new_name != "") and ($new_name != $_SESSION["realname"])){
	$sql = "UPDATE users SET u_realname = :newname WHERE userid = :id";
        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":newname", $new_name, PDO::PARAM_STR);
            $stmt->bindParam(":id", $_SESSION["id"], PDO::PARAM_INT);

            if($stmt->execute()){
		        $renamed = TRUE;
		        $_SESSION["realname"] = $new_name;
                $name_status = _("Name updated");
                $updated = TRUE;
	        }
	    }
    }

    // Change Marketing
    $marketing = isset($_POST['marketing']);
    if($marketing != $legacy_marketing) {

        // Prepare an update statement
         $sql = "UPDATE users SET u_can_contact = :marketing WHERE userid = :id";
        
         if($stmt = $pdo->prepare($sql)){
            $stmt->bindParam(":marketing", $marketing, PDO::PARAM_BOOL);
 
            if($stmt->execute()){
                $_SESSION["marketing"] = $marketing;
                $updated = TRUE;
            }
        }
    }

    // Validate url
    $url_err="";

    if(! (empty(trim($_POST["homepage"])))){
    
        $url = trim($_POST["homepage"]);
                
        if (url != ""){
            if(filter_var($url, FILTER_VALIDATE_URL)) {
                $url_err="";
                $sql = "UPDATE users SET u_url = :url WHERE userid = :id";
        
                if($stmt = $pdo->prepare($sql)){
                   $stmt->bindParam(":url", $url, PDO::PARAM_BOOL);
        
                   if($stmt->execute()){
                       $updated = TRUE;
                    }
                }
            } else {
                $url_err = _("Please enter a valid url");
            }
        
        }   
    }
    


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
            $param_id = $_SESSION["id"];
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Password updated successfully. Destroy the session, and redirect to login page
                session_destroy();
                header("location: login.php");
                exit();
            } else{
                echo _("Oops! Something went wrong. Please try again later.");
            }

            // Close statement
            unset($stmt);
        }
    } elseif ($renamed && empty(trim($_POST["new_password"])) && empty(trim($_POST["new_password"]))){
	$new_password_err = $confirm_password_err = "";
    }
    
    // Close connection
    unset($pdo);
}

if ($updated){
    $update_msg = _("Updated successfully!");
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="infinity.css">
</head>
<body>
  <?php include 'nav-menu.php';?>


    <div class="wrapper">
        <h2>Update Profile</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post"> 
            <!-- Update message -->
            <div class="form-group <?php echo (!empty($update_msg)) ? 'has-error' : ''; ?>">
                <span class="help-block"><?php echo $update_msg; ?></span>
            </div>
            <!-- Name -->
            <div class="form-group">
                <label>Username</label>
                <input type="text" id="username" name="username" value="<?php echo $_SESSION["username"]; ?>" readonly><br>
            </div>
	        <div class="form-group <?php echo (!empty($name_status)) ? 'has-error' : ''; ?>">
                <label>Full Name</label>
                <input type="text" name="realname" class="form-control" value="<?php echo $_SESSION["realname"]; ?>">
                <span class="help-block"><?php echo $name_status; ?></span>
            </div>
            <!-- Homepage -->
            <div class="form-group <?php echo (!empty($url_err)) ? 'has-error' : ''; ?>">
                <label>Homepage</label>
                <input type="url" name="homepage" class="form-control" value="<?php echo $url; ?>">
                <span class="help-block"><?php echo $url_err; ?></span>
            </div>

            <!-- Marketing -->
            <div class="form-group">
                <label>May we contact you about news and events around this project?</label>
                <input name="marketing" type="checkbox" value="true" <?php echo $legacy_marketing ? 'checked' : ''; ?>>
                <span class="help-block">We will never give your name or email address to third parties.</span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Submit">
                <a class="btn btn-link" href="index.php">Go Back</a>
            </div>
  
        <h2>Change Password</h2>
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
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Submit">
                <a class="btn btn-link" href="index.php">Go Back</a>
            </div>
        </form>
    </div>    
</body>
</html>
