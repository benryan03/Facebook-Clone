<?php
session_start();

//If no user is logged in, setLoggedInUser to None
if (!isset($_SESSION["loggedInUser"])){
    $loggedInUser = "None";}
else {
    $loggedInUser = $_SESSION["loggedInUser"];}

$username = "";
$password = "";
$error = "";

$errorStatus = false;

if (!empty($_POST["submit"])){

    //Retreive submitted username and password
    $username = htmlspecialchars($_POST["username"]);
    $password = htmlspecialchars($_POST["password"]);
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    //Connect to database
    $serverName = "localhost\sqlexpress";
    $connectionInfo = array("Database"=>"social_network", "UID"=>"ben", "PWD"=>"password123");
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    //Query database
    $query = "SELECT * FROM Users WHERE username = '$username' ";
    $result = sqlsrv_query($conn, $query);

    //Convert result to array and extract stored hash
    $result = sqlsrv_fetch_array($result);
    $storedHash = $result[2];

    //Compare inputted password to stored hash
    $isPasswordCorrect = password_verify($password, $storedHash);

    if ($isPasswordCorrect == true){
        //Login success
        $_SESSION["loggedInUser"] = trim($username);
        header("Location:index.php");
    }
    else {
        //Login fail
        $error = "Incorrect username or password.";
    }
}

?>

<html>
<head>
<title>Log in</title>
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
<form class="register-form action="? echo $_SERVER["PHP_SELF"]" method="post">
<input type="text" name="username" placeholder="Username" value="<?php echo htmlentities($username) ?>"><br><br>

<input type="password" name="password" placeholder="Password" value="<?php echo htmlentities($password) ?>"><br><br>

<input type="submit" value="Submit" name="submit"><br>
<span class="error"><?php echo "$error" ?></span>
</form>
</div>

</center>
</body>
</html>