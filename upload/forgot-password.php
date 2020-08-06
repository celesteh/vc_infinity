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

$email = $email_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){

    if(empty(trim($_POST["email"]))){
        $email_err = _("Please enter an email address.");
    } else{
	
	$email = trim($_POST["email"]);

    if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
	    // valid address
        // Prepare a select statement
        $sql = "SELECT userid FROM users WHERE u_email = :email";

        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);

            // Set parameters
            $param_email = $email;

            // Attempt to execute the prepared statement
            if($stmt->execute()){
                if($stmt->rowCount() == 1){
                    if($row = $stmt->fetch()){
                        $uid = $row["userid"];
    

                        $url = password_reset($uid, $pdo);

                        $body = "You have requested a password change on your account. To reset it, click here:  " . $url;
                        $headers = "From: infinity@vocalconstructivists.com";
    
                        mail($email, "Construncting Infinity password reset", $body, $headers);
                    
                    }
                }
            }

            // Close statement
            unset($stmt);
        }

        // Redirect to email page
        header("location: check-email.html");
    }
    else {
        // invalid address
	    $email = "";
	    $email_err = _("Please enter a valid email address.");
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
    <style type="text/css">
        body{ font: 14px sans-serif; }
        .wrapper{ width: 350px; padding: 20px; }

    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Forgot Password</h2>
        <p>Please enter your email address to get a reset link.</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group <?php echo (!empty($email_err)) ? 'has-error' : ''; ?>">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo $email; ?>">
                <span class="help-block"><?php echo $email_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Submit">
                <input type="reset" class="btn btn-default" value="Reset">
            </div>
            <p>Or <a href="login.php">Login here</a>.</p>
        </form>
    </div>    
</body>
</html>
