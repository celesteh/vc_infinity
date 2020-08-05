<?php
// Include config file
require_once "config.php";
 
// Define variables and initialize with empty values
$username = $password = $confirm_password = $email = "";
$username_err = $password_err = $confirm_password_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = _("Please enter a username.");
    } else{
        // Prepare a select statement
        $sql = "SELECT userid FROM users WHERE username = :username";
        
        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            
            // Set parameters
            $param_username = trim($_POST["username"]);
            
            // Attempt to execute the prepared statement
            if($stmt->execute()){
                if($stmt->rowCount() == 1){
                    $username_err = _("This username is already taken.");
                } else{
                    $username = trim($_POST["username"]);
                }
            } else{
                echo _("Oops! Something went wrong. Please try again later.");
            }

            // Close statement
            unset($stmt);
        }
    }

    // Validate real name
    $realname = trim($_POST["username"]);
    if(empty($realname)){
        // They don't need to give one if they don't want to
        $realname = "";
    }
 
    // Validate email
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
                       $email_err = _("There is already an account for this email.");
                    } else{
                        $email = trim($_POST["email"]);
                    }
                } else{
		    $email = "";
                    echo _("Oops! Something went wrong. Please try again later.");
                }

                // Close statement
                unset($stmt);
            }
        }
        else {
            // invalid address
	        $email = "";
	        $email_err = _("Please enter a valid email address.");
        }

    }

   
    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = _("Please enter a password.");     
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = _("Password must have atleast 6 characters.");
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = _("Please confirm password.");     
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = _("Password did not match.");
        }
    }

    // Check captcha
    $captchaResult = trim($_POST["captchaResult"]);
	$firstNumber = trim($_POST["firstNumber"]);
    $secondNumber = trim($_POST["secondNumber"]);
    $checkTotal = $firstNumber + $secondNumber;

    if ($captchaResult != $checkTotal) {
        $captcha_err = _("Incorrect. Please try again.");
    }

    // Organisation
    $orgcode = trim($_POST["orgcode"]);
    
    // Check input errors before inserting in database
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($captcha_err)){
        
        // Prepare an insert statement
        $sql = "INSERT INTO users (username, u_password, u_email, u_org, u_realname) VALUES (:username, :password, :email, :orgcode, :realname)";
         
        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            $stmt->bindParam(":password", $param_password, PDO::PARAM_STR);
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
            $stmt->bindParam(":orgcode", $orgcode, PDO::PARAM_STR);
            $stmt->bindParam(":realname", $realname, PDO::PARAM_STR);
            // Set parameters
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            $param_email = $email;

            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Redirect to login page
                header("location: login.php");
            } else{
                echo _("Something went wrong. Please try again later.");
            }

            // Close statement
            unset($stmt);
        }
    }
    
    // Close connection
    unset($pdo);
} else {

    // Create captcha
    $min_number = 1;
	$max_number = 15;

	$random_number1 = mt_rand($min_number, $max_number);
	$random_number2 = mt_rand($min_number, $max_number);
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
    <style type="text/css">
        body{ font: 14px sans-serif; }
        .wrapper{ width: 350px; padding: 20px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Sign Up</h2>
        <p>Please fill this form to create an account.</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group <?php echo (!empty($email_err)) ? 'has-error' : ''; ?>">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo $email; ?>">
                <span class="help-block"><?php echo $email_err; ?></span>
            </div>

            <div class="form-group <?php echo (!empty($username_err)) ? 'has-error' : ''; ?>">
                <label>Username</label>
                <input type="text" name="username" class="form-control" value="<?php echo $username; ?>">
                <span class="help-block"><?php echo $username_err; ?></span>
            </div>
            <div class="form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">

                <label>Password</label>
                <input type="password" name="password" class="form-control" value="<?php echo $password; ?>">
                <span class="help-block"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group <?php echo (!empty($confirm_password_err)) ? 'has-error' : ''; ?>">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" value="<?php echo $confirm_password; ?>">
                <span class="help-block"><?php echo $confirm_password_err; ?></span>
            </div> <!-- Organisation -->
            <div class="form-group ">
                <label>Group</label>
                <select name="orgcode" id="orgcode">
                    <?php        
                       $sql = "SELECT * FROM `organisations` WHERE 1 ";
                        if($stmt = $pdo->prepare($sql)){
                            if($stmt->execute()){
                                if($row = $stmt->fetch()){
                                    $orgcode = $row["orgcode"];
                                    $orgname = $row["orgname"];

                                    echo "<option value='" . $orgcode . "'>" . $orgname . "</option>";
                            }  
                        } 
                    }
                ?>
                </select>
            </div> <!-- Captcha -->
            <div class="form-group <?php echo (!empty($captcha_err)) ? 'has-error' : ''; ?>">
                <label>Prove you're a human: <?php echo $random_number1 . ' + ' . $random_number2 . ' = '; ?></label>
                <input name="captchaResult" type="text" />
                <span class="help-block"><?php echo $captcha_err; ?></span>
            </div>
            <input name="firstNumber" type="hidden" value="<?php echo $random_number1; ?>" />
            <input name="secondNumber" type="hidden" value="<?php echo $random_number2; ?>" />  
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Submit">
                <input type="reset" class="btn btn-default" value="Reset">
            </div>
            <p>Already have an account? <a href="login.php">Login here</a>.</p>
        </form>
    </div>    
</body>
</html>
