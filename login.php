<?php
session_start();

//If no user is logged in, setLoggedInUser to None
if (!isset($_SESSION["loggedInUser"])){
    $_SESSION["loggedInUser"] = "None";}
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

<div class="header">
    <h1><a href="index.php">Social Network</a></h1>
</div>

<div class="options">
    <?php if ($loggedInUser == "None"){echo '<a href="register.php">Register</a>&nbsp;';} ?>
    <?php if ($loggedInUser == "None"){echo '<a href="login.php">Log in</a>&nbsp;';} ?>
    <?php if ($loggedInUser != "None"){echo '<a href="new_thread.php">New thread</a>&nbsp;';} ?>
    <?php if ($loggedInUser != "None"){echo '<a href="logout.php">Log out</a>';} ?>
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