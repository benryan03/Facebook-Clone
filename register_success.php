<html>
<head>
<title>Register account</title>
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
        <?php if (!isset($_SESSION["loggedInUser"])){echo '<a href="register.php">Register</a>&nbsp;';} ?>
        <?php if (!isset($_SESSION["loggedInUser"])){echo '<a href="login.php"44>Log in</a>&nbsp;';} ?>
        <?php if (isset($_SESSION["loggedInUser"])){echo '<a href="logout.php">Log out</a>';} ?>
        <?php if (isset($_SESSION["loggedInUser"])){echo 'Welcome, <a href="profile.php?selectedUser=' . $loggedInUser . '">' .$loggedInUser. '</a>';}
        ?>
    </span>
</div>

<div class="content">
    Account successfully created.<br><br>
    <a href="login.php">Log in</a>
</div>

</center>
</body>
</html>