<?php
date_default_timezone_set("America/New_York");
$timestamp = date("m/d/Y h:i:sa");

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

//Get userID of loggedInUser
$getCurrentUserIDQuery = "SELECT id FROM users WHERE username = '$loggedInUser'";
$getCurrentUserID = sqlsrv_query($conn, $getCurrentUserIDQuery, array());
$currentUserID = sqlsrv_fetch_array($getCurrentUserID);
$currentUserID = $currentUserID[0];

//Get friends of loggedInUser
$getCurrentUserFriendsQuery =  "SELECT userid, friendid FROM friends WHERE (accepted = 'True' AND userid = '$currentUserID') OR (accepted = 'True' AND friendid = '$currentUserID') ";
$currentUserFriends = sqlsrv_query($conn, $getCurrentUserFriendsQuery, array(), array( "Scrollable" => 'static' ));
$currentUserFriendsCount = sqlsrv_num_rows($currentUserFriends); //Count friends of loggedInUser

//Convert friends of loggedInUser to array of user IDs
$currentUserFriendsArray = array();
for ($x = 1; $x < $currentUserFriendsCount + 1; $x++){
    $currentUserFriendsRow = sqlsrv_fetch_array($currentUserFriends, SQLSRV_FETCH_NUMERIC); //Select next row
    array_push($currentUserFriendsArray, $currentUserFriendsRow[0]);
    array_push($currentUserFriendsArray, $currentUserFriendsRow[1]);}

//Get pending friend requests of loggedInUser
$getPendingRequestsQuery = "SELECT * FROM friends WHERE friendid = '$currentUserID' AND accepted = 'False'";
$getPendingRequests = sqlsrv_query($conn, $getPendingRequestsQuery, array(), array( "Scrollable" => 'static' ));
$pendingRequestsCount = sqlsrv_num_rows($getPendingRequests);


//If new status has been posted
if (isset($_POST["new_status"])){
    $newStatus = $_POST["new_status"];

    //To calculate new post ID, count number of rows in database and add 1
    $countExistingPostsQuery = "SELECT * FROM posts";
    $countExistingPosts = sqlsrv_query($conn, $countExistingPostsQuery, array(), array( "Scrollable" => 'static' ));
    $posts_count = sqlsrv_num_rows( $countExistingPosts );
    $newPostID = $posts_count + 1;

    $newPostQuery = "INSERT INTO posts VALUES ('$newPostID', '$newStatus', '$loggedInUser', '$timestamp', ' ', '$timestamp', '0', '$currentUserID', '$currentUserID', '$loggedInUser') ";
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
            <?php
                echo '<form action="search.php" method="post" style="display: inline;"><input type="text" name="search" placeholder="Search"><input type="submit" value="Submit" name="submitSearch"></form>';
                if ($pendingRequestsCount > 0){
                    echo '&nbsp;&nbsp;<a href="requests.php" id="requests">'.$pendingRequestsCount.' new friend requests</a>';
                }
            ?>

        </span>
        <span id="userOptions">
            <?php if ($loggedInUser == "None"){echo '<a href="register.php">Register</a>&nbsp;';} ?>
            <?php if ($loggedInUser == "None"){echo '<a href="login.php"44>Log in</a>&nbsp;';} ?>
            <?php if ($loggedInUser != "None"){echo '<a href="logout.php">Log out</a>';} ?>
            <?php if ($loggedInUser != "None"){echo 'Welcome, <a href="profile.php?selectedUser=' . $loggedInUser . '">' .$loggedInUser. '</a>';}
            ?>
        </span>
    </div>


    <!-- Content feed-->
    <div class="feed">
        Your feed<br>

        <!--Post a status-->
        <form action="?" method="post">
        <textarea name="new_status" rows="1" cols="40" placeholder="Post a status"></textarea>
        <input type="submit" value="Submit" name="submit_status"><br>
        <div class="error" id="status_error"><br></div>
        </form>

        <?php   
        //Count how many comments are in the the thread
        $currentUserFriendsString = "'".implode("', '", $currentUserFriendsArray)."'";
        $query = "SELECT * FROM posts WHERE post_author_id IN ($currentUserFriendsString) OR post_author = '$loggedInUser' ORDER BY date_submitted DESC";
        $posts_array = sqlsrv_query($conn, $query, array(), array( "Scrollable" => 'static'));
        $posts_count = sqlsrv_num_rows($posts_array);

        for ($x = 1; $x < $posts_count + 1; $x++){
            $posts_array_row = sqlsrv_fetch_array($posts_array, SQLSRV_FETCH_NUMERIC); //Select next row in $query
            
            //Display a post
            if ($posts_array_row[7] == $posts_array_row[8]){
                echo nl2br(
                    "<font color='#0080ff'><b><a href='profile.php?selectedUser=" . $posts_array_row[2] . "'>" . $posts_array_row[2]. "</a></b></font> " .
                    "<font color='gray' size='2'>" . date_format($posts_array_row[3], "m/d/Y h:ia") . "</font>\n" .
                    $posts_array_row[1]."\n\n"
                );
            }
            else {
                echo nl2br(
                    "<font color='#0080ff'><b><a href='profile.php?selectedUser=" . $posts_array_row[2] . "'>" . $posts_array_row[2]. "</a>" . 
                    " > " . "<a href='profile.php?selectedUser=" . $posts_array_row[9] . "'>" . $posts_array_row[9] . "</a>" .
                    "</b></font> " .
                    "<font color='gray' size='2'>" . date_format($posts_array_row[3], "m/d/Y h:ia") . "</font>\n" .
                    $posts_array_row[1]."\n\n"
                );                
            }
        }

        ?>
    </div>
    </center>
</body>
</html>