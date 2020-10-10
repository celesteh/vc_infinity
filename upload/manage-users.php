<?php

require_once "config.php";
require_once "functions.php";
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

if(!isset($_SESSION["powerlevel"]) || $_SESSION["powerlevel"]< 80){
    header("location: index.php");
}

$update_msg = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $success = TRUE;
    $count = 0;

    // double check power level
    $my_powerlevel = get_power_level_for_user($_SESSION["id"], $pdo);
    $_SESSION["powerlevel"] = $my_powerlevel;
    if ($_SESSION["powerlevel"]< 80){
        header("location: index.php");
    }

    // step through users looking for changes
    $sql = "SELECT userid, u_rolecode, u_email FROM `users` WHERE 1 ";
    if($stmt = $pdo->prepare($sql)){
        if($stmt->execute()){
             while($row = $stmt->fetch()){

                $uid = $row["userid"];
                $oldrole = $row["u_rolecode"];
                $uemail = $row["u_email"];
                $newrole = trim($_POST[$uid]);

                // if we found a change
                if ($newrole != $oldrole){

                    // let's debug first, eh?
                    //echo $newrole;

                    // update it
                    $usql = "UPDATE users SET u_rolecode = :newrole WHERE userid = :userid";
                    if($ustmt = $pdo->prepare($usql)){
                        $ustmt->bindParam(":userid", $param_userid, PDO::PARAM_INT);
                        $ustmt->bindParam(":newrole", $newrole, PDO::PARAM_STR);
                        $param_userid = (int) $uid;
                        if($ustmt->execute()){
                            // Send an email to the user

                            $body = _("Your account at " .$_SITE["title"] . " has been modified. You are now a {$newrole}. Please log in to see what you are now allowed to do.");
                            $headers = "From: infinity@vocalconstructivists.com";

                            mail($uemail, $_SITE["title"] . _(" account modified"), $body, $headers);


                            // ok, add this one to the counter

                            $count = $count +1;
                        }else{
                            $success = false;
                        }
                        unset($ustmt);
                    }
                }
            }
        } else { $success = false; }
        unset($stmt);
    }

    if ($success){
        if ($count > 0){    
            $update_msg = _("Updated successfully!");
        }
    } else {
        $update_msg = _("Update failed. Try again later.");
    }

}



?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Users</title>
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="infinity.css">

</head>
<body>

<div class="page-header">
    <h1>Manage Users</h1>
</div>

<?php include 'nav-menu.php';?>
    <div class="notes">
        <h3>Change user roles.</h3>

        <div class="form-group <?php echo (!empty($update_msg)) ? 'has-error' : ''; ?>">
                <span class="help-block"><?php echo $update_msg; ?></span>
            </div>




        <p>Users - can log in, but can't do anyhting else.</p>
        <p>Musicians - can submit audio.</p>
        <p>Engineers - can modify audio others have submitted.</p>
        <p>Editors - can upload new score pages and can modify audio and metadata that others have submitted.</p>
        <p>Administrators - can do everything above and can modify the roles of other users.</p>
        <div class="container">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class = "overflow">
        <table>
            <tr><th>Name</th><th>Organisation</th><th>URL</th><th>Role</th></tr>
        <?php        
            // get all roles
            $arsql = "SELECT role_rolecode, role_rolename FROM `roles` WHERE 1";
            if($arstmt = $pdo->prepare($arsql)){
                if($arstmt->execute()){
                    while($row = $arstmt->fetch()){
                        $role_arr[$row["role_rolecode"]] = $row["role_rolename"];
                    }
                }
            }

            // get all orgs
            $osql = "SELECT orgname, orgcode from `organisations` where 1";
                        if($ostmt = $pdo->prepare($osql)){
                            if($ostmt->execute()){
                                while($orow = $ostmt->fetch()) {
                                    $orgs[$orow["orgcode"]] = $orow["orgname"];
                                }
                            }
                        }

            //$colours = array("white", "grey");
            //$index =0;
            //$size = sizeof($colours);

            $sql = "SELECT userid, u_realname, u_org, u_rolecode, u_url FROM `users` WHERE 1 ";
            if($stmt = $pdo->prepare($sql)){
                if($stmt->execute()){
                     while($row = $stmt->fetch()){
 

                        $userid = $row["userid"];
                        $realname = $row["u_realname"];
                        $orgcode = $row["u_org"];
                        $orgname = $orgs[$orgcode];
                        $role_code = $row["u_rolecode"];
                        $url = $row["u_url"];

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
                        <tr><td>$realname</td><td>$orgname</td><td><a href="$url" target="_blank">$url</a></td><td><select name="$userid" id="$userid">
ENDUSR;


                        foreach($role_arr as $rcode => $rname) {
                            if ($rcode == $role_code){
                                //match
                                $selected = "selected";
                            } else {
                                $selected = "";
                            }
                            $usrstr = $usrstr . '<option value="' . $rcode . '" ' . $selected . ">" . $rname . '</option><\n';  
                            }
                        
                        //$usrstr = $usrstr ."</select>\n</div>\n</div>\n";
                        $usrstr = $usrstr ."</select></td></tr>\n";
                        echo $usrstr;
                    }
                }
            }

                ?>
                </table>
        </div>
            <div class="row">
                <div class="col-50l">&nbsp;</div><div class="col-50r">
                <input type="submit" class="btn btn-primary" value="Submit">
                <input type="reset" class="btn btn-default" value="Reset">
            </div>
            </div>
            </form>
        </div>
        <p><a href="index.php">Go back</a></p>
    </div>    
</body>
</html>
<?php 
    // Close connection
    unset($pdo);
?>
