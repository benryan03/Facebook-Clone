<?php
session_start();

date_default_timezone_set("America/New_York");
$timestamp = date("d/m/Y h:i:sa");

//If no user is logged in, setLoggedInUser to None
if (!isset($_SESSION["loggedInUser"])){
    $_SESSION["loggedInUser"] = "None";
}
$loggedInUser = $_SESSION["loggedInUser"];

if (isset($_POST["selectedUser"])){
    $selectedUser = $_POST["selectedUser"];
}
else {
    $selectedUser = "None";
}

$debug = "";

//Connect to database
$serverName = "localhost\sqlexpress";
$connectionInfo = array("Database"=>"social_network", "UID"=>"ben", "PWD"=>"password123");
$conn = sqlsrv_connect($serverName, $connectionInfo);

if (isset($_POST["new_status"])){
    
    $debug = "true e";

    $newStatus = $_POST["new_status"];

    //To calculate new comment ID, count number of rows in database and add 1
    $countExistingPostsQuery = "SELECT * FROM posts";
    $countExistingPosts = sqlsrv_query($conn, $countExistingPostsQuery, array(), array( "Scrollable" => 'static' ));
    $posts_count = sqlsrv_num_rows( $countExistingPosts );
    $newPostID = $posts_count + 1;

    $newPostQuery = "INSERT INTO posts VALUES ('$newPostID', '$newStatus', '$loggedInUser', '$timestamp', ' ', '$timestamp', '0') ";
    $newPostSubmit = sqlsrv_query($conn, $newPostQuery);
    if (!$newPostSubmit){
        print_r(sqlsrv_errors());
    }
}





?>

<html>
<head>
    <title>Welcome to Social Network</title>
    <link rel="stylesheet" type="text/css" href="default.css">
</head>
<body>
    <center>
    <div class="header" id="header">
        <h1><a href="index.php">Social Network</a></h1>
    </div>

    <div class="options" id="options">
        <?php if ($loggedInUser == "None"){echo '<a href="register.php">Register</a>&nbsp;';} ?>
        <?php if ($loggedInUser == "None"){echo '<a href="login.php"44>Log in</a>&nbsp;';} ?>
        <?php if ($loggedInUser != "None"){echo '<a href="logout.php">Log out</a>';} ?>
        <?php if ($loggedInUser != "None"){echo 'Welcome, <a href="profile.php?selectedUser=' . $loggedInUser . '">' .$loggedInUser. '</a>';} ?>

    </div>

    <div class="feed" id="feed">

        Your feed<br>

        <!--Post a status-->
        <form action="?" method="post">
        <textarea name="new_status" rows="1" cols="40" placeholder="Post a status"></textarea>
        <input type="submit" value="Submit" name="submit_status"><br>
        <div class="error" id="status_error"><br></div>
        </form>

        <?php

        //Count how many comments are in the the thread
        $query = "SELECT * FROM posts ORDER BY date_submitted DESC";
        $posts_array = sqlsrv_query($conn, $query, array(), array( "Scrollable" => 'static'));
        $posts_count = sqlsrv_num_rows($posts_array);

        for ($x = 1; $x < $posts_count + 1; $x++){
            $posts_array_row = sqlsrv_fetch_array($posts_array, SQLSRV_FETCH_NUMERIC); //Select next row         
            
            echo nl2br(
                "<font color='#0080ff'><b><a href='profile.php?selectedUser=" . $posts_array_row[2] . "'>" . $posts_array_row[2]. "</a></b></font> 
                <font color='gray' size='2'>" . date_format($posts_array_row[3], "m/d/Y h:ia") . "</font>\n" .
                $posts_array_row[1]."\n\n"
            );

        }

        ?>

    </div>
    <div class="debug"><?php echo $debug ?></div>
    </center>
</body>
</html>