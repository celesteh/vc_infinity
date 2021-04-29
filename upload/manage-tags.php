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
//if (! $correct_nonce){
$_SESSION['nonce'] = set_nonce();
    //}



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
       <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
       <div class="form-group <?php echo (!empty($username_err)) ? 'has-error' : ''; ?>">
                <label>Shortcode</label>
                <input type="text" name="shortcode" class="form-control">
            </div>  
            <div class="form-group <?php echo (!empty($username_err)) ? 'has-error' : ''; ?>">
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
