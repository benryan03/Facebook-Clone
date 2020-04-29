<?php
//Get logged in user. If no user is logged in, redirect to login page.
session_start();
if (!isset($_SESSION["loggedInUser"])){
    header("Location:login.php");}
else{
    $loggedInUser = $_SESSION["loggedInUser"];}

//Connect to database
$serverName = "localhost\sqlexpress";
$connectionInfo = array("Database"=>"social_network", "UID"=>"ben", "PWD"=>"password123");
$conn = sqlsrv_connect($serverName, $connectionInfo);

//Get userID of loggedinUser
$getCurrentUserIDQuery = "SELECT id FROM users WHERE username = '$loggedInUser'";
$getCurrentUserID = sqlsrv_query($conn, $getCurrentUserIDQuery, array());
$currentUserID = sqlsrv_fetch_array($getCurrentUserID);
$currentUserID = $currentUserID[0];

//Get new likes
$getNewLikesQuery = "SELECT * FROM likes WHERE post_author = '$loggedInUser' AND notified = 'False'";
$newLikes = sqlsrv_query($conn, $getNewLikesQuery, array(), array( "Scrollable" => 'static' ));
$newLikesCount = sqlsrv_num_rows($newLikes); //Count new likes

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
        <?php if (!isset($_SESSION["loggedInUser"])){echo '<a href="register.php">Register</a>&nbsp;';} ?>
        <?php if (!isset($_SESSION["loggedInUser"])){echo '<a href="login.php"44>Log in</a>&nbsp;';} ?>
        <?php if (isset($_SESSION["loggedInUser"])){echo '<a href="logout.php">Log out</a>';} ?>
        <?php if (isset($_SESSION["loggedInUser"])){echo 'Welcome, <a href="profile.php?selectedUser=' . $loggedInUser . '">' .$loggedInUser. '</a>';}
        ?>
    </span>
</div>

<div class="contents">
    <div id="searchResults">
        <?php

            echo "New Likes:<br><br>";
            
            if ($newLikesCount > 0){
                for ($x = 1; $x < $newLikesCount + 1; $x++){             
                    $likes_array_row = sqlsrv_fetch_array($newLikes, SQLSRV_FETCH_NUMERIC); //Select next row in $query    
                    $postID = $likes_array_row[0];       

                    //Query post
                    $getPostQuery = "SELECT * FROM posts WHERE post_id = '$postID'";
                    $getPost = sqlsrv_query($conn, $getPostQuery, array(), array( "Scrollable" => 'static' ));
                    $post = sqlsrv_fetch_array($getPost);
                    
                    //Query who liked the post
                    $getPostQuery = "SELECT * FROM likes WHERE post_id = '$postID'";
                    $getPost = sqlsrv_query($conn, $getPostQuery, array(), array( "Scrollable" => 'static' ));
                    $like = sqlsrv_fetch_array($getPost);
                    
                    //X liked your post
                    echo "<font color='#0080ff'><b><a href='profile.php?selectedUser=" . $like[2] . "'>" . $like[2]. "</a>" . "</b></font> liked your post:<br>";
                    //Display post
                    echo $post[1] . "<br><br>";

                }
            }
            else {
                echo "No new likes";
            }
            
        ?>
    </div>
</div>


</center>
</body>
</html>