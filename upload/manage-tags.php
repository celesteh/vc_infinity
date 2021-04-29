<?php
// Initialize the session
session_start();
 
include_once "config.php";
include_once "functions.php";
 
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}


if (! lazy_power_check($_SESSION["id"], $pdo, 60)){
    header("location: index.php");
}

if(isset($_SESSION["page_called"])){
    $page_called = $_SESSION["page_called"];
} else {
    $page_called = get_page_called_for_user($_SESSION["id"], $pdo);
    $_SESSION["page_called"] = $page_called;
}
$upper = ucfirst($page_called);


$correct_nonce = verify_nonce();
$_SESSION['nonce'] = set_nonce();


// Define variables and initialize with empty values
$shortcode = $text = $parent = $hidden ="";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Validate shortcode
    if(empty(trim($_POST["shortcode"]))){
        $shortcode_err = _("Please enter a shortcode.");
    } else{
        
        $shortcode = trim($_POST["shortcode"]);
        $shortcode = strtolower($shortcode);

        // check if it's alphanumeric and lower case

        if (( ! ctype_alnum($shortcode) ) || preg_match('/[A-Z]/', $shortcode)) {

            $shortcode_err = _("Please use only numbers and lowercase letters for your shortcode.");
        
        } else {


            // Prepare a select statement
            $sql = "SELECT tag_id FROM available_tags WHERE tag_shortcode = :shortcode";
            
            if($stmt = $pdo->prepare($sql)){
                // Bind variables to the prepared statement as parameters
                $stmt->bindParam(":shortcode", $param_shortcode, PDO::PARAM_STR);
                
                // Set parameters
                $param_shortcode = $shortcode;
                
                // Attempt to execute the prepared statement
                if($stmt->execute()){
                    if($stmt->rowCount() == 1){
                        $shortcode_err = _("This shortcode is already taken.");
                    } else{
                        $shortcode = trim($_POST["shortcode"]);
                        $shortcode = strtolower($shortcode);
                    }
                } else{
                    echo _("Oops! Something went wrong. Please try again later.");
                }

                // Close statement
                unset($stmt);
            }
        }
    }

    // Validate tag text
    $text = trim($_POST["tag_text"]);
    if(empty($text)){
        $tag_text_err = "Please specify the visible version of this tag";
    } elseif ( preg_match('/[^\p{L}\p{N}\p{M}\'\p{Pd}\ ]/u', $text)) {

        //! ctype_alnum($username) ) {
        $tag_text_err = _("Please use only visible characters for your tag.");
    }
        
    $text = filter_var($text,FILTER_SANITIZE_SPECIAL_CHARS);

 
    
    // Optional Parent
    $parent = trim($_POST["parent"]);

    // Visible
    $idden = isset($_POST['hidden']);
    
    // Check input errors before inserting in database
    if(empty($username_err) && empty($realname_err) && empty($password_err) && empty($confirm_password_err) && empty($captcha_err) && empty($url_err)){
        
        /*
        // Prepare an insert statement
        $sql = "INSERT INTO users (username, u_email, u_org, u_realname, u_can_contact, u_url) VALUES (:username,  :email, :orgcode, :realname, :marketing, :url)";
         
        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
            $stmt->bindParam(":orgcode", $orgcode, PDO::PARAM_STR);
            $stmt->bindParam(":realname", $realname, PDO::PARAM_STR);
            $stmt->bindParam(":marketing", $param_marketing, PDO::PARAM_BOOL);
            $stmt->bindParam(":url", $url, PDO::PARAM_STR);
        
            // Set parameters
            $param_username = $username;
            $param_email = $email;
            $param_marketing = isset($_POST['marketing']);

            // Attempt to execute the prepared statement
            if($stmt->execute()){
            
                $uid = get_userid($username, $pdo);
                $url = password_reset($uid, $pdo);

                $body = "Thank you for registering. To confirm your account click here: " . $url;
                $headers = "From: infinity@vocalconstructivists.com";

                mail($email, "Welcome to Constructing Infinity", $body, $headers);
                // Redirect to email page
                header("location: check-email.html");
            } else{
                echo _("Something went wrong. Please try again later.");
            }

            // Close statement
            unset($stmt);
        }
        */
    }
    
} 


?>

 <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Available Tags</title>
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="infinity.css">

</head>
<body>
    <div class="page-header">
        <h1><b>Manage Available Tags</b<</h1>
    </div>

    <?php include 'nav-menu.php';?>
    <div>
    <h2>Current tags:<h2>
    </div>
  
    <div class="container">
        <div class = "overflow">
        <table>
            <tr><th>Tag Text</th><th>Short Code</th><th>Parent</th><th>Hidden</th></tr>
        <?php 
/*
Create table available_tags (
    tag_id int unsigned not null AUTO_INCREMENT primary key,
    tag_shortcode  VARCHAR(250) Not null unique,
    tag_text  VARCHAR(250) Not null,
    tag_parent  VARCHAR(250),
    tag_hidden tinyint(1)
) ENGINE = InnoDB;
*/
            $tags = array();

            $sql = "SELECT tag_shortcode, tag_text, tag_parent, tag_hidden FROM `available_tags` WHERE 1 ";
            if($stmt = $pdo->prepare($sql)){
                if($stmt->execute()){
                    while($row = $stmt->fetch()){


                        $shortcode = htmlspecialchars($row["tag_shortcode"]);
                        $text = htmlspecialchars($row["tag_text"]);
                        $parent  = htmlspecialchars($row["tag_text"]);
                        $hidden = $row["tag_hidden"];

                        array_push($atgs, $shortcode);

                        /*
                        // manage colours rotating
                        $index = ($index + 1) % $size;
                        $colour = $colours[$index];

                        $usrstr = <<< ENDUSR
                        <div class="row $colour">
                            <div class="col-50l">                    
                                <label>$realname, $orgname</label>
                            </div>
                            <div class="col-50r">
                                <select name="$userid" id="$userid">
ENDUSR;
                        */
                        $usrstr = <<< ENDUSR
                        <tr><td>$shortcode</td><td>$text</td><td>$parent</td><td>$hidden</td></tr>
ENDUSR;
                    }
                }
            }
        ?>
        </table>
    </div>
    </div>
    <div>
    <h2>Add tag:<h2>
    </div>
  
    <div class="wrapper">
       <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
       <div class="form-group <?php echo (!empty($shortcode_err)) ? 'has-error' : ''; ?>">
                <label>Shortcode</label>
                <input type="text" name="shortcode" class="form-control">
            </div>  
            <div class="form-group <?php echo (!empty($tag_text_err)) ? 'has-error' : ''; ?>">
                <label>Text</label>
                <input type="text" name="tag_text" class="form-control">
            </div>
            <div class="form-group ">
                <label>Parent</label>
                <select name="parent" id="parent">
                    <?php 

                        foreach ($tags as $poss_parent) {
                            echo "<option value='" . $poss_parent . "'>" . $poss_parent . "</option>";
                        } 
                ?>
                </select>
            </div> 
            <div class="form-group">
                <label>Hidden</label>
                <input name="hidden" type="checkbox" value="false">
                <span class="help-block">Hidden tags are parents to other tags, but can't be selected directly.</span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Submit">
                <input type="reset" class="btn btn-default" value="Reset">
            </div>
        </form>
        </div>
    </body>
</html>
