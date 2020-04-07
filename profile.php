<?php

//If no user is logged in, setLoggedInUser to None
session_start();
if (!isset($_SESSION["loggedInUser"])){
    $loggedInUser = "None";}
else{
    $loggedInUser = $_SESSION["loggedInUser"];}

//If no user is logged in, redirect to login page
if ($loggedInUser == "None"){
    header("Location:login.php");}

//Connect to database
$serverName = "localhost\sqlexpress";
$connectionInfo = array("Database"=>"social_network", "UID"=>"ben", "PWD"=>"password123");
$conn = sqlsrv_connect($serverName, $connectionInfo);

//Check if user was selected
if (isset($_GET["selectedUser"])){
    $selectedUser = $_GET["selectedUser"];
        
    //Get userID of selectedUser
    $getSelectedUserIDQuery = "SELECT id FROM users WHERE username = '$selectedUser'";
    $getSelectedUserID = sqlsrv_query($conn, $getSelectedUserIDQuery, array());
    $selectedUserID = sqlsrv_fetch_array($getSelectedUserID);
    $selectedUserID = $selectedUserID[0];
}
else {
    $selectedUser = "None";}

date_default_timezone_set("America/New_York");
$timestamp = date("m/d/Y h:i:sa");

//Get userID of loggedinUser
$getCurrentUserIDQuery = "SELECT id FROM users WHERE username = '$loggedInUser'";
$getCurrentUserID = sqlsrv_query($conn, $getCurrentUserIDQuery, array());
$currentUserID = sqlsrv_fetch_array($getCurrentUserID);
$currentUserID = $currentUserID[0];

//Count friends of loggedInUser
$getCurrentUserFriendsQuery = "SELECT friendid FROM friends WHERE userid = '$currentUserID'";
$currentUserFriends = sqlsrv_query($conn, $getCurrentUserFriendsQuery, array(), array( "Scrollable" => 'static' ));
$currentUserFriendsCount = sqlsrv_num_rows($currentUserFriends);

//Get friends of loggedInUser as array of user IDs
$currentUserFriendsArray = array();
for ($x = 1; $x < $currentUserFriendsCount + 1; $x++){
    $currentUserFriendsRow = sqlsrv_fetch_array($currentUserFriends, SQLSRV_FETCH_NUMERIC); //Select next row
    array_push($currentUserFriendsArray, $currentUserFriendsRow[0]);}

//Convert currentUserFriendsArray from user IDs to usernames
foreach ($currentUserFriendsArray as &$value){
    $convertQuery = " SELECT username FROM users WHERE id = '$value' ";
    $convert = sqlsrv_query($conn, $convertQuery);
    $convert = sqlsrv_fetch_array($convert);
    $value = $convert[0];}

//If $loggedInUser posts a status to their own Wall
if (isset($_POST["new_status"])){
    $newStatus = $_POST["new_status"];

    //To calculate new post ID, count number of rows in database and add 1
    $countExistingPostsQuery = "SELECT * FROM posts";
    $countExistingPosts = sqlsrv_query($conn, $countExistingPostsQuery, array(), array( "Scrollable" => 'static' ));
    $posts_count = sqlsrv_num_rows( $countExistingPosts );
    $newPostID = $posts_count + 1;

    $newPostQuery = "INSERT INTO posts VALUES ('$newPostID', '$newStatus', '$loggedInUser', '$timestamp', ' ', '$timestamp', '0', '$currentUserID') ";
    $newPostSubmit = sqlsrv_query($conn, $newPostQuery);
    if (!$newPostSubmit){
        print_r(sqlsrv_errors());}
}

//If $loggedInUser posts a status to a friend's Wall
if (isset($_POST["newWallPost"])){
    $newWallPost = $_POST["newWallPost"];

    //To calculate new post ID, count number of rows in database and add 1
    $countExistingPostsQuery = "SELECT * FROM posts";
    $countExistingPosts = sqlsrv_query($conn, $countExistingPostsQuery, array(), array( "Scrollable" => 'static' ));
    $posts_count = sqlsrv_num_rows( $countExistingPosts );
    $newPostID = $posts_count + 1;

    $newPostQuery = "INSERT INTO posts VALUES ('$newPostID', '$newWallPost', '$loggedInUser', '$timestamp', ' ', '$timestamp', '0', '$selectedUserID') ";
    $newPostSubmit = sqlsrv_query($conn, $newPostQuery);
    if (!$newPostSubmit){
        print_r(sqlsrv_errors());}
}
?>

<html>
<head>
    <title>Welcome to Social Network</title>
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
            <?php echo '<form action="search.php" method="post"style="display: inline;"><input type="text" name="search" placeholder="Search"><input type="submit" value="Submit" name="submitSearch"></form>'; ?>
        </span>
        <span id="userOptions">
            <?php if ($loggedInUser == "None"){echo '<a href="register.php">Register</a>&nbsp;';} ?>
            <?php if ($loggedInUser == "None"){echo '<a href="login.php"44>Log in</a>&nbsp;';} ?>
            <?php if ($loggedInUser != "None"){echo '<a href="logout.php">Log out</a>';} ?>
            <?php if ($loggedInUser != "None"){echo 'Welcome, <a href="profile.php?selectedUser=' . $loggedInUser . '">' .$loggedInUser. '</a>';}
            ?>
        </span>
    </div>
    
    <div class="main">
        <span class="wall" id="wall">

            <?php 
            if ($selectedUser == $loggedInUser){
                echo "Your Wall";}
            else {
                echo nl2br($selectedUser."'s Wall");
            }

            if ($selectedUser == $loggedInUser){
                //Post a status on your Wall
                echo nl2br('
                <form action="profile.php?selectedUser='.$selectedUser.'" method="post">'.
                '<textarea name="new_status" rows="1" cols="40" placeholder="Post a status"></textarea><input type="submit" value="Submit" name="submit_status"><br>'.
                '<div class="error" id="status_error"><br></div>'.
                '</form>');
            }
            else {
                //Post on $selectedUser's Wall
                echo nl2br('
                <form action="profile.php?selectedUser='.$selectedUser.'" method="post">'.
                '<textarea name="newWallPost" rows="1" cols="40" placeholder="Post on '.$selectedUser.'\'s Wall"></textarea><input type="submit" value="Submit" name="submitWallPost"><br>'.
                '<div class="error" id="status_error"><br></div>'.
                '</form>');
            }

            //Count how many comments are in the the thread
            $query = "SELECT * FROM posts WHERE wall = '$selectedUserID' ORDER BY date_submitted DESC";
            $posts_array = sqlsrv_query($conn, $query, array(), array( "Scrollable" => 'static'));
            $posts_count = sqlsrv_num_rows($posts_array);

            for ($x = 1; $x < $posts_count + 1; $x++){
                $posts_array_row = sqlsrv_fetch_array($posts_array, SQLSRV_FETCH_NUMERIC); //Select next row in $query
                
                //Display a post
                echo nl2br(
                    "<font color='#0080ff'><b><a href='profile.php?selectedUser=" . $posts_array_row[2] . "'>" . $posts_array_row[2]. "</a></b></font>" .
                    "<font color='gray' size='2'> " . date_format($posts_array_row[3], "m/d/Y h:ia") . "</font>\n" .
                    $posts_array_row[1]."\n\n"
                );
            }
            ?>
        </span>
        <span class="sidebar">
            <?php
                if ($selectedUser == $loggedInUser){
                    echo nl2br("Viewing your profile\n");}
                else{
                echo nl2br("Viewing ".$selectedUser."'s profile\n");}

                if ($selectedUser == $loggedInUser){}
                elseif (in_array($selectedUser, $currentUserFriendsArray)){
                    echo nl2br("You are friends\n");}
                else {
                    echo nl2br("Add friend\n");}
            ?>
        </span
    </div>
    </center>
</body>
</html>