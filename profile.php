<?php
date_default_timezone_set("America/New_York");
$timestamp = date("m/d/Y h:i:sa");

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

//Check if user was selected (on this page, user should always be selected)
if (isset($_GET["selectedUser"])){
    $selectedUser = $_GET["selectedUser"];
        
    //Get userID of selectedUser
    $getSelectedUserIDQuery = "SELECT id FROM users WHERE username = '$selectedUser'";
    $getSelectedUserID = sqlsrv_query($conn, $getSelectedUserIDQuery, array());
    $selectedUserID = sqlsrv_fetch_array($getSelectedUserID);
    $selectedUserID = $selectedUserID[0];
}

//If user sent a friend invite
if (isset($_GET["addFriend"])){
    $addFriendQuery = "INSERT INTO friends VALUES ('$currentUserID', '$selectedUserID', 'False')";
    $addFriend = sqlsrv_query($conn, $addFriendQuery);
}

//If user accepted a friend invite
if (isset($_GET["acceptFriend"])){
    $acceptFriendQuery = "UPDATE friends SET accepted = 'True' WHERE userid = '$selectedUserID' AND friendid = '$currentUserID'";
    $acceptFriend = sqlsrv_query($conn, $acceptFriendQuery);
}

//Get friends of loggedInUser
$getCurrentUserFriendsQuery =  "SELECT userid, friendid FROM friends WHERE (accepted = 'True' AND userid = '$currentUserID') OR (accepted = 'True' AND friendid = '$currentUserID') ";
$currentUserFriends = sqlsrv_query($conn, $getCurrentUserFriendsQuery, array(), array( "Scrollable" => 'static' ));
$currentUserFriendsCount = sqlsrv_num_rows($currentUserFriends); //Count friends for loop

//Convert friends of loggedInUser to array of user IDs
$currentUserFriendsArray = array();
for ($x = 1; $x < $currentUserFriendsCount + 1; $x++){
    $currentUserFriendsRow = sqlsrv_fetch_array($currentUserFriends, SQLSRV_FETCH_NUMERIC); //Select next row
    array_push($currentUserFriendsArray, $currentUserFriendsRow[0]);
    array_push($currentUserFriendsArray, $currentUserFriendsRow[1]);}

$currentUserFriendsArray = array_unique($currentUserFriendsArray);
$currentUserFriendsArray = array_values($currentUserFriendsArray);
$currentUserFriendsCount = count($currentUserFriendsArray);





//Get sent pending friend invites of loggedInUser
$getCurrentUserPendingInvitesQuery = "SELECT friendid FROM friends WHERE userid = '$currentUserID' AND accepted = 'False'";
$currentUserPendingInvites = sqlsrv_query($conn, $getCurrentUserPendingInvitesQuery, array(), array( "Scrollable" => 'static' ));
$currentUserPendingInvitesCount = sqlsrv_num_rows($currentUserPendingInvites); //Count friends for loop

//Convert sent pending friend invites of loggedInUser to array of user IDs
$currentUserPendingInvitesArray = array();
for ($x = 1; $x < $currentUserPendingInvitesCount + 1; $x++){
    $currentUserPendingInvitesRow = sqlsrv_fetch_array($currentUserPendingInvites, SQLSRV_FETCH_NUMERIC); //Select next row
    array_push($currentUserPendingInvitesArray, $currentUserPendingInvitesRow[0]);}

//Get received pending friend invites of loggedInUser
$getCurrentUserReceivedPendingInvitesQuery = "SELECT userid FROM friends WHERE friendid = '$currentUserID' AND accepted = 'False'";
$currentUserReceivedPendingInvites = sqlsrv_query($conn, $getCurrentUserReceivedPendingInvitesQuery, array(), array( "Scrollable" => 'static' ));
$pendingRequestsCount = sqlsrv_num_rows($currentUserReceivedPendingInvites); //Count pending invites

//Convert pending friend invites of loggedInUser to array of user IDs
$currentUserReceivedPendingInvitesArray = array();
for ($x = 1; $x < $pendingRequestsCount + 1; $x++){
    $currentUserReceivedPendingInvitesRow = sqlsrv_fetch_array($currentUserReceivedPendingInvites, SQLSRV_FETCH_NUMERIC); //Select next row
    array_push($currentUserReceivedPendingInvitesArray, $currentUserReceivedPendingInvitesRow[0]);}

//
//For friends list of selectedUser in sidebar
//

//Get friends of selectedUser
$getSelectedUserFriendsQuery =  "SELECT userid, friendid FROM friends WHERE (accepted = 'True' AND userid = '$selectedUserID') OR (accepted = 'True' AND friendid = '$selectedUserID') ";
$selectedUserFriends = sqlsrv_query($conn, $getSelectedUserFriendsQuery, array(), array( "Scrollable" => 'static' ));
$selectedUserFriendsCount2 = sqlsrv_num_rows($selectedUserFriends); //Count friends for loop

//Convert friends of loggedInUser to array of user IDs
$selectedUserFriendsArray = array();
for ($x = 1; $x < $selectedUserFriendsCount2 + 1; $x++){
    $selectedUserFriendsRow = sqlsrv_fetch_array($selectedUserFriends, SQLSRV_FETCH_NUMERIC); //Select next row
    array_push($selectedUserFriendsArray, $selectedUserFriendsRow[0]);
    array_push($selectedUserFriendsArray, $selectedUserFriendsRow[1]);}

$selectedUserFriendsArray = array_unique($selectedUserFriendsArray);
$selectedUserFriendsArray = array_values($selectedUserFriendsArray);
$selectedUserFriendsCount = count($selectedUserFriendsArray);

//Convert selectedUserFriendsArray from user IDs to usernames	
foreach ($selectedUserFriendsArray as &$value){	
    $convertQuery = " SELECT username FROM users WHERE id = '$value' ";	
    $convert = sqlsrv_query($conn, $convertQuery);	
    $convert = sqlsrv_fetch_array($convert);	
    $value = $convert[0];}

//
//If $loggedInUser posts a status to their own Wall
//

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

//
//If $loggedInUser posts a status to a friend's Wall
//

if (isset($_POST["newWallPost"])){
    $newWallPost = $_POST["newWallPost"];

    //To calculate new post ID, count number of rows in database and add 1
    $countExistingPostsQuery = "SELECT * FROM posts";
    $countExistingPosts = sqlsrv_query($conn, $countExistingPostsQuery, array(), array( "Scrollable" => 'static' ));
    $posts_count = sqlsrv_num_rows( $countExistingPosts );
    $newPostID = $posts_count + 1;

    $newPostQuery = "INSERT INTO posts VALUES ('$newPostID', '$newWallPost', '$loggedInUser', '$timestamp', ' ', '$timestamp', '0', '$selectedUserID', '$currentUserID', '$selectedUser') ";
    $newPostSubmit = sqlsrv_query($conn, $newPostQuery);
    if (!$newPostSubmit){
        print_r(sqlsrv_errors());}
    }

//
//If user liked a post
//

if (isset($_GET["likePost"])){

    //Get previous number of likes
    $likedPostID = $_GET["likePost"];
    $getLikesCountQuery = "SELECT likes FROM posts WHERE post_id = '$likedPostID'";
    $getLikesCount = sqlsrv_query($conn, $getLikesCountQuery, array(), array( "Scrollable" => 'static' ));
    $likesCount = sqlsrv_fetch_array($getLikesCount);
    $likesCount = $likesCount[0];

    //Add like
    $newLikesCount = $likesCount + 1;
    $likePostQuery = "UPDATE posts SET likes = '$newLikesCount' WHERE post_id = '$likedPostID'";
    $likePost = sqlsrv_query($conn, $likePostQuery);
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
            <?php if (!isset($_SESSION["loggedInUser"])){echo '<a href="register.php">Register</a>&nbsp;';} ?>
            <?php if (!isset($_SESSION["loggedInUser"])){echo '<a href="login.php"44>Log in</a>&nbsp;';} ?>
            <?php if (isset($_SESSION["loggedInUser"])){echo '<a href="logout.php">Log out</a>';} ?>
            <?php if (isset($_SESSION["loggedInUser"])){echo 'Welcome, <a href="profile.php?selectedUser=' . $loggedInUser . '">' .$loggedInUser. '</a>';}
            ?>
        </span>
    </div>
    
    <div class="main">
        <span class="wall" id="wall">
            
            <!--Profile pic-->
            <?php
            //If profile pic exists, display it. Else, display default profile pic.
            if (file_exists("images\\" . $selectedUser . "_128.jpg")){
                echo"<a href='view_image.php?selectedImage=" . $selectedUser . "_128.jpg'><img src='images\\" .$selectedUser. "_128.jpg'></a><br>";                
            }
            else {
                echo "<img src='images\default_profile_picture_128.jpg'></a><br>";
            }

            if ($selectedUser == $loggedInUser){
                echo "<a href='?'>Change profile picture</a><br><br>";
            }
            else echo "<br>"
            ?>

            <!-- Wall -->
            <?php 
            if ($selectedUser == $loggedInUser){
                echo "Your Wall";
            }
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
            elseif (in_array($selectedUserID, $currentUserFriendsArray)){ //If users are friends
                //Post on $selectedUser's Wall
                echo nl2br('
                <form action="profile.php?selectedUser='.$selectedUser.'" method="post">'.
                '<textarea name="newWallPost" rows="1" cols="40" placeholder="Post on '.$selectedUser.'\'s Wall"></textarea><input type="submit" value="Submit" name="submitWallPost"><br>'.
                '<div class="error" id="status_error"><br></div>'.
                '</form>');
            }
            else { //If users are not friends, do not display status entry
                echo "<br><br>";
            }

            //If users are friends, display posts on selectedUser's Wall
            if ($selectedUserID == $currentUserID || in_array($selectedUserID, $currentUserFriendsArray)){
                //Count how many comments are in the the thread
                $query = "SELECT * FROM posts WHERE wall = '$selectedUserID' ORDER BY date_submitted DESC";
                $posts_array = sqlsrv_query($conn, $query, array(), array( "Scrollable" => 'static'));
                $posts_count = sqlsrv_num_rows($posts_array);

                for ($x = 1; $x < $posts_count + 1; $x++){
                    $posts_array_row = sqlsrv_fetch_array($posts_array, SQLSRV_FETCH_NUMERIC); //Select next row in $query
                    
                    //Display a post
                        echo
                        "<div class='status'>".
                            "<span class='profileThumb'>".
                                "<a href='profile.php?selectedUser=" . $posts_array_row[2] . "'><img src='";

                                //If profile pic exists, display it. Else, display default profile pic.
                                if (file_exists("images\\".$posts_array_row[2]."_32.jpg")){echo "images\\".$posts_array_row[2]."_32.jpg";}         
                                else {echo "images\default_profile_picture_32.jpg";}
                                
                        echo    "'></a>".
                            "</span>".
                            
                            "<span class='statusContent'>".
                                //Username of post author
                                "<font color='#0080ff'><b><a href='profile.php?selectedUser=" . $posts_array_row[2] . "'>" . $posts_array_row[2]. "</a>" . "</b></font><br> ";

                                //If post is not on author's wall, display username of that user
                                if ($posts_array_row[7] != $posts_array_row[8]){echo "<font color='#0080ff'><b><a href='profile.php?selectedUser=" . $posts_array_row[2] . "'>" . $posts_array_row[2]. "</a>" . " > " . "<a href='profile.php?selectedUser=" . $posts_array_row[9] . "'>" . $posts_array_row[9] . "</a>" . "</b></font> ";}

                                //Date posted
                        echo    "<font color='gray' size='2'>" . date_format($posts_array_row[3], "m/d/Y h:ia") . "</font><br>" .
                                //Post content
                                $posts_array_row[1] . "<br><font size='2'>";

                                //Number of likes
                                if ($posts_array_row[10] == 1) {echo "1 like&nbsp;";}
                                else if ($posts_array_row[10] > 1) {echo $posts_array_row[10] . "&nbsp;likes&nbsp;";}
                                
                                //Like and comment buttons
                        echo    "<a href='?selectedUser=" . $posts_array_row[2] . "&likePost=" . $posts_array_row[0] . "'>Like</a>&nbsp;<a href='?'>Comment</a></font>" .
                            "</span>".
                        "</div><br><br>";

                }
            }

            //If users are not friends, do not display any posts
            else {
                echo "You are not friends with ".$selectedUser;
            }
            ?>
        </span>
        <span class="sidebar">
            <?php
                if ($selectedUser == $loggedInUser){
                    echo nl2br("Viewing your profile\n\nYour friends(".($currentUserFriendsCount - 1)."): ");
                }
                else{
                    echo nl2br("Viewing ".$selectedUser."'s profile\n\n");
                }

                if ($selectedUserID == $currentUserID){

                }
                elseif (in_array($selectedUserID, $currentUserFriendsArray)){
                    echo nl2br("You are friends\n\n".$selectedUser."'s friends(".($selectedUserFriendsCount - 1)."): \n");
                }
                elseif (in_array($selectedUserID, $currentUserReceivedPendingInvitesArray)){
                    echo nl2br("<a href='profile.php?acceptFriend&selectedUser=".$selectedUser."'>Accept friend request</a>\n\n");
                }
                elseif (in_array($selectedUserID, $currentUserPendingInvitesArray)){
                    echo nl2br("Friend request sent\n\n");
                }
                elseif (!in_array($selectedUserID, $currentUserFriendsArray)){
                    echo nl2br("<a href='profile.php?addFriend&selectedUser=".$selectedUser."'>Send friend request</a>\n\n");
                }
                
                if ($selectedUserFriendsCount > 0){
                    for ($x = 0; $x < $selectedUserFriendsCount; $x++){
                        if ($selectedUserFriendsArray[$x] != $selectedUser){
                            //Display a user
                            echo nl2br("<font color='#0080ff'><b><a href='profile.php?selectedUser=".$selectedUserFriendsArray[$x]."'>".$selectedUserFriendsArray[$x]."</a></b></font>");
                            if ($x < $selectedUserFriendsCount - 1){
                                echo nl2br(", ");
                            }
                        }
                    }
                }

            ?>
        </span>
    </div>
    </center>
</body>
</html>