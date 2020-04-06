<?php
session_start();
$_SESSION["loggedInUser"] = "None";
$loggedInUser = "None";
?>

<html>
<head>
    <title>Social Network</title>
    <link rel="stylesheet" type="text/css" href="default.css">
</head>
<body>
<center>
<!--Logo-->
<div class="header" id="header">
    <h1><a href="index.php">Social Network</a></h1>
</div>

<!--Options bar-->
<div id="options">
    <span id="search">   
        <?php echo '<form action="?" method="post"style="display: inline;"><input type="text" name="search" placeholder="Search"><input type="submit" value="Submit" name="submitSearch"></form>'; ?>
    </span>
    <span id="userOptions">
        <?php if ($loggedInUser == "None"){echo '<a href="register.php">Register</a>&nbsp;';} ?>
        <?php if ($loggedInUser == "None"){echo '<a href="login.php"44>Log in</a>&nbsp;';} ?>
        <?php if ($loggedInUser != "None"){echo '<a href="logout.php">Log out</a>';} ?>
        <?php if ($loggedInUser != "None"){echo 'Welcome, <a href="profile.php?selectedUser=' . $loggedInUser . '">' .$loggedInUser. '</a>';}
        ?>
    </span>
</div>

<div class="content">
    You have been sucessfully logged out.
</div>


</center>
</body>
</html>