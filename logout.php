<?php
session_start();
$_SESSION["loggedInUser"] = "None";

//If no user is logged in, setLoggedInUser to None
if (!isset($_SESSION["loggedInUser"])){
    $_SESSION["loggedInUser"] = "None";
}

$loggedInUser = $_SESSION["loggedInUser"];
?>

<html>
<head>
    <title>Social Network</title>
    <link rel="stylesheet" type="text/css" href="default.css">
</head>
<body>
    <center>
    <div class="header">
    <h1><a href="index.php">Social Network</a></h1>
    </div>

    <div class="options">
        <?php if ($loggedInUser == "None"){echo '<a href="register.php">Register</a>&nbsp;';} ?>
        <?php if ($loggedInUser == "None"){echo '<a href="login.php">Log in</a>&nbsp;';} ?>
    </div>

    <div class="content">
        You have been sucessfully logged out.
    </div>


    </center>
</body>
</html>