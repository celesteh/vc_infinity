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
        $username = strtolower($username);
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
        $sql = "SELECT userid, username, u_password, u_realname, u_rolecode, u_org FROM users WHERE username = :username";
        
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
                        $org_code = $row["u_org"];

                        if(password_verify($password, $hashed_password)){
                            // Password is correct, so start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username; 
                            $_SESSION["org_code"] = $org_code;

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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="infinity.css">

</head>
<body>
    <div class="page-header">
        <h1>Contributor Login</h1>
    </div>
    <nav class="navbar navbar-inverse" id="myTopnav">
        <div class="container-fluid">
            <ul class="nav navbar-nav">
                <li><a href="../">Public Site</a></li>
            </ul>
            <ul class="nav navbar-nav navbar-right">

                <li><a href="forgot-password.php">Reset Password</a></li>
            </ul>
        </div>
    </nav>
    <div class="wrapper">
    
        <p>It is not necessary to log in to listen to the project.</p>
        <p>If you are a contributor, please fill in your credentials to login.</p>
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
            <p>Don't have an account? Check your email for the sign-up link.</p>
            <p><a href="../">Back to main site</a></p>
        </form>

    </div>
</body>
</html>

