<?php
// Initialize the session
session_start();
include_once "config.php";
include_once "functions.php";


if(!isset($_SESSION["powerlevel"])){
    //echo "unset";
    $_SESSION["powerlevel"] = get_power_level_for_user($_SESSION["id"], $pdo);
} 

?>

<nav class="navbar navbar-inverse" id="myTopnav">
  <div class="container-fluid">
   <ul class="nav navbar-nav">
 
    <li><a href="../">Future Site</a></li>
    <li><a href="./">Dashboard</a></li>
<?php
 $powerlevel = $_SESSION["powerlevel"];

if ($powerlevel >= 80){
    echo '<li><a href="manage-users.php">Manage users</a></li>';
}
if ($powerlevel >= 60){
    echo '<li><a href="pages.php">Manage score</a></li>';
}
if ($powerlevel >= 40) {
    echo '<li><a href="edit-audio.php">Edit Audio</a></li>';
 }
if ($powerlevel >= 20) {
    echo '<li><a href="view-score.php">View Full Score</a></li>';
    echo '<li><a href="submit.php">Submit audio</a></li>';
    echo '<li><a href="user-audio.php">Audio Repository</a></li>';

}
?>
<li><a href="prepare.html">How To</a></li>
<li><a href="edit-profile.php">Edit Profile</a></li>
</ul>
<ul class="nav navbar-nav navbar-right">

<li><a href="logout.php">Logout</a></li>
<li id="hamburger" class="icon"><a href="javascript:void(0);" class="icon" onclick="menuFunc()">&equiv; Menu</a></li>
</ul>
  </div>
</nav> 
<script>
    /* Toggle between adding and removing the "responsive" class to topnav when the user clicks on the icon */
function menuFunc() {
  var x = document.getElementById("myTopnav");
  if (x.className === "") {
    x.className += " responsive";
  } else {
    x.className = "";
  }
} 

var hamburger = document.getElementById('hamburger');
if (screen.width <= 600){
    hamburger.style.display = "block";
} else {
    hamburger.style.display = "none";
}

</script>