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
    header("localtion: welcome.php");
}

if($_SERVER["REQUEST_METHOD"] == "POST"){

    // double check power level
    $my_powerlevel = get_power_level_for_user($_SESSION["id"], $pdo);
    $_SESSION["powerlevel"] = $my_powerlevel;
    if ($_SESSION["powerlevel"]< 80){
        header("localtion: welcome.php");
    }

    // step through users looking for changes
    $sql = "SELECT userid, u_realname, u_org, u_rolecode FROM `users` WHERE 1 ";
    if($stmt = $pdo->prepare($sql)){
        if($stmt->execute()){
             while($row = $stmt->fetch()){

                $uid = $row["userid"];
                $oldrole = $row["u_rolecode"];
                $newrole = trim($_POST[$uid]);

                // if we found a change
                if ($newrole != $oldrole){

                    // let's debug first, eh?
                    echo $newrole;

                }
            }
        }
    }


}



?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="infinity.css">

    <style type="text/css">
        body{ font: 14px sans-serif; }
        .wrapper{ width: 350px; padding: 20px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Manage Users</h2>
        <h3>Change user roles.</h3>
        <p>Users - can log in, but can't do anyhting else.</p>
        <p>Musicians - can submit audio.</p>
        <p>Engineers - can modify audio others have submitted.</p>
        <p>Editors - can modify audio and metadata that others have submitted.</p>
        <p>Administrators - can do everything above and can modify the roles of other users.</p>
        <div class="container">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

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

            $colours = array("white", "grey");
            $index =0;
            $size = sizeof($colours);

            $sql = "SELECT userid, u_realname, u_org, u_rolecode FROM `users` WHERE 1 ";
            if($stmt = $pdo->prepare($sql)){
                if($stmt->execute()){
                     while($row = $stmt->fetch()){
 

                        $userid = $row["userid"];
                        $realname = $row["u_realname"];
                        $orgcode = $row["u_org"];
                        $orgname = $orgs[$orgcode];
                        $role_code = $row["u_rolecode"];

 
                        // manage colours rotating
                        $index = ($index + 1) % $size;
                        $colour = $colours[$index];

                        $usrstr = <<< ENDUSR
                        <div class="row">
                            <div class="col-50l $colour">                    
                                <label>$realname, $orgname</label>
                            </div>
                            <div class="col-50r">
                                <select name="$userid" id="$userid">
ENDUSR;
                        foreach($role_arr as $rcode => $rname) {
                            if ($rcode == $role_code){
                                //match
                                $selected = "selected";
                            } else {
                                $selected = "";
                            }
                            $usrstr = $usrstr . '<option value="' . $rcode . '" ' . $selected . ">" . $rname . '</option>\n';  
                            }
                        
                        $usrstr = $usrstr ."</select>\n</div>\n</div>\n";
                        echo $usrstr;
                    }
                }
            }

                ?>
            <div class="row">
                <div class="col-50l">&nbsp;</div><div class="col-50r">
                <input type="submit" class="btn btn-primary" value="Submit">
                <input type="reset" class="btn btn-default" value="Reset">
            </div>
            </div>
            </form>
        </div>
        <p><a href="welcome.php">Go back</a></p>
    </div>    
</body>
</html>
<?php 
    // Close connection
    unset($pdo);
?>
