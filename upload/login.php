<?php
// Initialize the session
session_start();
 
// Check if the user is already logged in, if yes then redirect him to welcome page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php");
    exit;
}
 
// Include config file
require_once "config.php";
require_once "functions.php";
 
// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Check if username is empty
    if(empty(trim($_POST["username"]))){
        $username_err = _("Please enter username.");
    } else{
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = _("Please enter your password.");
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($username_err) && empty($password_err)){
        // Prepare a select statement
        $sql = "SELECT userid, username, u_password, u_realname, u_rolecode FROM users WHERE username = :username";
        
        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            
            // Set parameters
            $param_username = trim($_POST["username"]);
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Check if username exists, if yes then verify password
                if($stmt->rowCount() == 1){
                    if($row = $stmt->fetch()){
                        $id = $row["userid"];
                        $username = $row["username"];
                        $hashed_password = $row["u_password"];
                        $realname = $row["u_realname"];
                        $role_code = $row["u_rolecode"];

                        if(password_verify($password, $hashed_password)){
                            // Password is correct, so start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username; 

			                if ( is_null($realname) or ($realname == "")) {
				                $realname = $username;
			                }

                             $_SESSION["realname"] = $realname;  
                                           
                             // if there was a temporary password, get rid of it
                             clear_temp_password($id, $pdo);

                            // Redirect user to welcome page
                            header("location: index.php");
                        } else{
                            // Display an error message if password is not valid
                            $password_err = _("The password you entered was not valid.");
                        }
                    }
                } else{
                    // Display an error message if username doesn't exist
                    $username_err = _("No account found with that username.");
                }
            } else{
                echo _("Oops! Something went wrong. Please try again later.");
            }

            // Close statement
            unset($stmt);
        }
        if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] == true){
            $powerlevel = get_power_level($role_code, $pdo);
            $_SESSION["powerlevel"] = $powerlevel;

    
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
    <title>Login</title>
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="infinity.css">

</head>
<body>
    <div class="wrapper">
        <h2>Login</h2>
        <p>Please fill in your credentials to login.</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group <?php echo (!empty($username_err)) ? 'has-error' : ''; ?>">
                <label>Username</label>
                <input type="text" name="username" class="form-control" value="<?php echo $username; ?>">
                <span class="help-block"><?php echo $username_err; ?></span>
            </div>    
            <div class="form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
                <label>Password</label>
                <input type="password" name="password" class="form-control">
                <span class="help-block"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Login">
            </div>
            <p>Forgot your password? <a href="forgot-password.php">Password reset</a>.</p>
            <p>Don't have an account? <a href="register.php">Sign up now</a>.</p>
        </form>
    </div>
</body>
</html>

