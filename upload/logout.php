<?php
// Initialize the session
session_start();
 
$_SESSION = array();
session_destroy();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Logged Out</title>
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="infinity.css">

 </head>
<body>
<h2>You are now logged out.</h2>
<p><a href="login.php">Login here</a>.</p>
<p><a href="../">View Constructing Infinity</a></p>
</body>
</html>
