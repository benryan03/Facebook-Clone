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
        
    //Get userID and profile pic of selectedUser
    $getSelectedUserIDQuery = "SELECT id, profile_pic FROM users WHERE username = '$selectedUser'";
    $getSelectedUserID = sqlsrv_query($conn, $getSelectedUserIDQuery, array());
    $selectedUserInfo = sqlsrv_fetch_array($getSelectedUserID);
    $selectedUserID = $selectedUserInfo[0];
    $selectedUserProfilePic = $selectedUserInfo[1];
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

function calculateNewPostID(){
    //To calculate new post ID, count number of rows in database and add 1
    global $conn;
    $countExistingPosts = sqlsrv_query($conn, "SELECT * FROM posts", array(), array( "Scrollable" => 'static' ));
    $postsCount = sqlsrv_num_rows($countExistingPosts);
    $newPostID = $postsCount + 1;
    return $newPostID;
}

function setFilename(){
    global $newPostID;
    //Set filename of image to new post ID
    $path = $_FILES['file']['name'];
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    $target_file = "images/" . $newPostID . "." . $ext;
    return $target_file;
}

function verifyImage(){
    global $conn;
    global $newPostID;
    global $target_file;
    $uploadError = "";
    // Check if image file is a actual image or fake image
    if(isset($_POST["submit"])) {
        $check = getimagesize($_FILES["file"]["tmp_name"]);
        if($check !== false) {
            echo "File is an image - " . $check["mime"] . ".";
        } 
        else {
            $uploadError = "File is not an image.";
            return $uploadError;
        }
    }
    // Check file size
    if ($_FILES["file"]["size"] > 500000) {
        $uploadError = "Sorry, your file is too large.";
        return $uploadError;}
    // Allow certain file formats
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        $uploadError = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        return $uploadError;}
    // if everything is ok, try to upload file
    if ($uploadError == "") {   
        if (!move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
            $uploadError = "Sorry, there was an error uploading your file.";
            return $uploadError;}
    }
}

///////////////////////////////////////////////////
//If $loggedInUser posts a status to their own Wall
if (isset($_POST["new_status"])){
    $newStatus = $_POST["new_status"];
    $newPostID = calculateNewPostID();
    $newPostSubmit = sqlsrv_query($conn, "INSERT INTO posts VALUES ('$newPostID', '$newStatus', '$loggedInUser', '$timestamp', ' ', '$timestamp', '0', '$currentUserID', '$currentUserID', '$loggedInUser', null)");
    if (!$newPostSubmit){
        print_r(sqlsrv_errors());}
}

////////////////////////////////////////////////////
//If $loggedInUser posts a status to a friend's Wall
//Still need to add permission check
if (isset($_POST["newWallPost"])){
    $newWallPost = $_POST["newWallPost"];
    $newPostID = calculateNewPostID();
    $newPostSubmit = sqlsrv_query($conn, "INSERT INTO posts VALUES ('$newPostID', '$newWallPost', '$loggedInUser', '$timestamp', ' ', '$timestamp', '0', '$selectedUserID', '$currentUserID', '$selectedUser', null)");
    if (!$newPostSubmit){
        print_r(sqlsrv_errors());}
}

//////////////////////
//If user liked a post
//Still need to add permission check
if (isset($_GET["likePost"])){
    $likedPostID = $_GET["likePost"];
    $likePostQuery = "INSERT INTO likes VALUES ('$likedPostID', '$loggedInUser')";
    $likePost = sqlsrv_query($conn, $likePostQuery);
}

////////////////////////
//If user unliked a post
//Still need to add permission check
if (isset($_GET["unLikePost"])){
    $unLikedPostID = $_GET["unLikePost"];
    $likePostQuery = "DELETE FROM likes WHERE post_id = '$unLikedPostID' AND liked_by = '$loggedInUser'";
    $likePost = sqlsrv_query($conn, $likePostQuery);
}

/////////////////////////////////////////////
//If user uploaded an image to their own wall
$uploadError = "";
if (isset($_POST["postImage"])){
    $newPostID = calculateNewPostID();
    $target_file = setFileName();
    $uploadError = verifyImage();
    if ($uploadError == "")
        $newPostSubmit = sqlsrv_query($conn, "INSERT INTO posts VALUES ('$newPostID', '', '$loggedInUser', '$timestamp', '$target_file', '$timestamp', '0', '$currentUserID', '$currentUserID', '$loggedInUser', null) ");
        if (!$newPostSubmit){
            print_r(sqlsrv_errors());
    }
}

//////////////////////////////////////////////
//If user uploaded an image to a friend's Wall
if (isset($_POST["postImageFriend"])){
    $newPostID = calculateNewPostID();
    $target_file = setFileName();
    $uploadError = verifyImage();
    if ($uploadError == "")
        $newPostSubmit = sqlsrv_query($conn, "INSERT INTO posts VALUES ('$newPostID', '', '$loggedInUser', '$timestamp', '$target_file', '$timestamp', '0', '$selectedUserID', '$currentUserID', '$selectedUser', null) ");
        if (!$newPostSubmit){
            print_r(sqlsrv_errors());
    }
}

///////////////////////////////////////
//If user changed their profile picture
if (isset($_POST["submitProfilePic"])){
    $newPostID = calculateNewPostID();
    $target_file = setFileName();
    $uploadError = verifyImage();
    if ($uploadError == "")
        $newPostSubmit = sqlsrv_query($conn, "INSERT INTO posts VALUES ('$newPostID', '', '$loggedInUser', '$timestamp', '$target_file', '$timestamp', '0', '$selectedUserID', '$currentUserID', '$selectedUser') ");
        $target_file = substr($target_file, 7);
        $newProfilePicSubmit = sqlsrv_query($conn, "UPDATE users SET profile_pic = '$target_file' WHERE username = '$loggedInUser'");
        if (!$newPostSubmit){
            print_r(sqlsrv_errors());
    }
}

///////////////////////////////////////
//If user submitted a comment on a post
if (isset($_POST["submitComment"])){

    $commentOn = $_GET["commentOn"];

    //To calculate new post ID, count number of rows in database and add 1
    $countExistingPostsQuery = "SELECT * FROM posts";
    $countExistingPosts = sqlsrv_query($conn, $countExistingPostsQuery, array(), array( "Scrollable" => 'static' ));
    $posts_count = sqlsrv_num_rows( $countExistingPosts );
    $newPostID = $posts_count + 1;
    
    $commentText = $_POST['comment'];
    $newPostQuery = "INSERT INTO posts VALUES ('$newPostID', '$commentText', '$loggedInUser', '$timestamp', '', '$timestamp', '0', '0', '$currentUserID', 'NULL', $commentOn) ";
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
            //Get userID and profile pic of selectedUser
            $getProfilePicQuery = "SELECT profile_pic FROM users WHERE username = '$selectedUser'";
            $getProfilePic = sqlsrv_query($conn, $getProfilePicQuery, array());
            $selectedUserProfilePic = sqlsrv_fetch_array($getProfilePic);
            $selectedUserProfilePic = $selectedUserProfilePic[0];

            if ($selectedUserProfilePic != null){
                //Need to add code to create thumbnails on profile pic upload
                echo"<a href='view_image.php?selectedImage=" . $selectedUserProfilePic . "&imageType=profile'><img src='images/" .$selectedUserProfilePic. "' height='128' width='128'></a><br>";                           
            }
            else {
                echo "<img src='images\default_profile_picture_128.jpg'></a><br>";
            }

            if ($selectedUser == $loggedInUser){

                //Post an image on your Wall
                if (!isset($_GET["changeProfilePic"])){
                    echo "<a href='?selectedUser=" . $selectedUser . "&changeProfilePic'>Change profile picture</a><br><br>";}
                else {
                    echo "<form action='?selectedUser=" . $selectedUser . "' method='post' enctype='multipart/form-data'>".
                        "Select image to upload:<br>".
                        "<input type='file' name='file' id='file'><br>".
                        "<input type='submit' value='Submit' name='submitProfilePic'>".
                        "</form>";
                }
                echo '<div class="error">' . $uploadError . '</div><br>';
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
                '<div class="error" id="status_error"></div>'.
                '</form>');

                //Post an image on your Wall
                if (!isset($_GET["postImage"])){
                    echo "<a href='?selectedUser=" . $selectedUser . "&postImage'>Post an image</a><br><br>";}
                else {
                    echo "<form action='?selectedUser=" . $selectedUser . "' method='post' enctype='multipart/form-data'>".
                        "Select image to post:<br>".
                        "<input type='file' name='file' id='file'><br>".
                        "<input type='submit' value='Post' name='postImage'>".
                        "</form>";
                }
                echo '<div class="error">' . $uploadError . '</div><br>';

            }
            elseif (in_array($selectedUserID, $currentUserFriendsArray)){ //If users are friends
                //Post a status on $selectedUser's Wall
                echo nl2br('
                <form action="profile.php?selectedUser='.$selectedUser.'" method="post">'.
                '<textarea name="newWallPost" rows="1" cols="40" placeholder="Post on '.$selectedUser.'\'s Wall"></textarea><input type="submit" value="Submit" name="submitWallPost"><br>'.
                '<div class="error" id="status_error"></div>'.
                '</form>');
            
                //Post an image on $selectedUser's Wall
                if (!isset($_GET["postImage"])){
                    echo "<a href='?selectedUser=" . $selectedUser . "&postImage'>Post an image</a><br><br>";}
                else {
                    echo "<form action='?selectedUser=" . $selectedUser . "' method='post' enctype='multipart/form-data'>".
                        "Select image to post:<br>".
                        "<input type='file' name='file' id='file'><br>".
                        "<input type='submit' value='Post' name='postImageFriend'>".
                        "</form>";
                }
                echo '<div class="error">' . $uploadError . '</div><br>';
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
        
                                //Get profile pic or null
                                $getProfilePicQuery = "SELECT profile_pic FROM users WHERE username = '$posts_array_row[2]'";
                                $getProfilePic = sqlsrv_query($conn, $getProfilePicQuery, array());
                                $profilePic = sqlsrv_fetch_array($getProfilePic);

                                //If profile pic exists, display it. Else, display default profile pic.
                                if ($profilePic[0] != null){echo "images/" . $profilePic[0];}                           
                                else {echo "images\default_profile_picture_32.jpg";}
                                
                        echo    "'></a>".
                            "</span>".
                            
                            "<span class='statusContent'>".
                                //Username of post author
                                "<font color='#0080ff'><b><a href='profile.php?selectedUser=" . $posts_array_row[2] . "'>" . $posts_array_row[2]. "</a>" . "</b></font> ";
        
                                //If post is not on author's wall, display username of that user
                                if ($posts_array_row[7] != $posts_array_row[8]){echo "<font color='#0080ff'><b></a>" . " > " . "<a href='profile.php?selectedUser=" . $posts_array_row[9] . "'>" . $posts_array_row[9] . "</a>" . "</b></font></br> ";}
                                else {echo "<br>";}

                                //Date posted
                        echo    "<font color='gray' size='2'>" . date_format($posts_array_row[3], "m/d/Y h:ia") . "</font><br>";
                                
                                //Post content
                                if ($posts_array_row[4] != " "){echo "<a href='view_image.php?selectedImage=" . substr(strval($posts_array_row[4]), 7) . "'><img src='" . $posts_array_row[4] . "'></a><br><font size='2'>";}
                                else {echo $posts_array_row[1] . "<br><font size='2'>";}
        
                                //Get number of likes
                                $getLikesQuery = "SELECT * FROM likes WHERE post_id = '$posts_array_row[0]' ";
                                $getLikes = sqlsrv_query($conn, $getLikesQuery, array(), array( "Scrollable" => 'static' ));
                                $likesCount = sqlsrv_num_rows($getLikes);
        
                                //Convert users who liked current post to array
                                $likesArray = array();
                                for ($y = 1; $y < $likesCount + 1; $y++){
                                    $likesRow = sqlsrv_fetch_array($getLikes, SQLSRV_FETCH_NUMERIC); //Select next row
                                    array_push($likesArray, $likesRow[1]);}
                                    
                                if ($likesCount == 1) {echo "<div class='tooltip'>1 like<span class='tooltiptext'>" . implode(" ,", $likesArray) . "</span></div>&nbsp;";}
                                else if ($likesCount > 1) {echo "<div class='tooltip'>" . $likesCount . "&nbsp;likes<span class='tooltiptext'>" . implode(", ", $likesArray) . "</span></div>&nbsp;";}
            
        
                                //Like/unlike button
                                if (!in_array($loggedInUser, $likesArray)){echo "<a href='?selectedUser=" . $selectedUser . "&likePost=" . $posts_array_row[0] . "'>Like</a>&nbsp";}
                                else {echo "<a href='?selectedUser=" . $selectedUser . "&unLikePost=" . $posts_array_row[0] . "'>Unlike</a>&nbsp";}
        
                                //Comment button/box
                                if (isset($_GET["commentOn"]) && $_GET["commentOn"] == $posts_array_row[0]) { echo
                                    "<form action='?commentOn=" . $posts_array_row[0] . "' method='post'>" .
                                    "<input type='text' name='comment' placeholder='Add a comment'>" . 
                                    "<input type='submit' value='Submit' name='submitComment'><br>" . 
                                    "</form>";
                                }
                                else {echo "<a href='?selectedUser=" . $selectedUser . "&commentOn=" . $posts_array_row[0] . "'>Comment</a></font>";}
                        echo"</span><br><br>";
                            
                    
                                //Count how many comments the post has
                                $comments_array = sqlsrv_query($conn, "SELECT * FROM posts WHERE comment_of = '$posts_array_row[0]' ", array(), array( "Scrollable" => 'static'));
                                $comments_count = sqlsrv_num_rows($comments_array);
                                
                                                
                                if ($comments_count > 0){
                                    for ($z = 1; $z < $comments_count + 1; $z++){
                                        $comments_array_row = sqlsrv_fetch_array($comments_array, SQLSRV_FETCH_NUMERIC); //Select next row in $query
                                        echo
                                            "<div class='statusComment'>".
                                                "<span class='commentProfileThumb'>".
                                                    "<a href='profile.php?selectedUser=" . $comments_array_row[2] . "'><img src='";
                                                    //Get profile pic or null
                                                    $getProfilePicQuery2 = "SELECT profile_pic FROM users WHERE username = '$comments_array_row[2]'";
                                                    $getProfilePic2 = sqlsrv_query($conn, $getProfilePicQuery2, array());
                                                    $profilePic2 = sqlsrv_fetch_array($getProfilePic2);
                            
                                                    //If profile pic exists, display it. Else, display default profile pic.
                                                    if ($profilePic2[0] != null){echo "images/" . $profilePic2[0];}                           
                                                    else {echo "images\default_profile_picture_32.jpg";}
                                            echo    "'></a>".
                                                "</span>" . 
                                                
                                                "<span class='commentContent'>".
                                                    //Username of post author
                                                    "<font color='#0080ff'><b><a href='profile.php?selectedUser=" . $comments_array_row[2] . "'>" . $comments_array_row[2]. "</a>" . "</b></font><br> ";
                                
                                                    //Date posted
                                            echo    "<font color='gray' size='2'>" . date_format($comments_array_row[3], "m/d/Y h:ia") . "</font><br>";
                                                    
                                                    //Post content
                                                    if ($comments_array_row[4] != ""){echo "<a href='view_image.php?selectedImage=" . substr(strval($comments_array_row[4]), 7) . "'><img src='" . $comments_array_row[4] . "'></a><br>";}
                                                    else {echo $comments_array_row[1] . "<br>";}
                                            
                                                    //Get number of likes
                                                    $getLikesQuery2 = "SELECT * FROM likes WHERE post_id = '$comments_array_row[0]'";
                                                    $getLikes2 = sqlsrv_query($conn, $getLikesQuery2, array(), array( "Scrollable" => 'static' ));
                                                    $likesCount2 = sqlsrv_num_rows($getLikes2);
                            
                                                    //Convert users who liked current post to array
                                                    $likesArray2 = array();
                                                    for ($a = 1; $a < $likesCount2 + 1; $a++){
                                                        $likesRow2 = sqlsrv_fetch_array($getLikes2, SQLSRV_FETCH_NUMERIC); //Select next row
                                                        array_push($likesArray2, $likesRow2[1]);}

                                                    echo "<font size='2'>";
                                                    if ($likesCount2 == 1) {echo "<div class='tooltip'>1 like<span class='tooltiptext'>" . implode(" ,", $likesArray2) . "</span></div>&nbsp;";}
                                                    else if ($likesCount2 > 1) {echo "<div class='tooltip'>" . $likesCount2 . "&nbsp;likes<span class='tooltiptext'>" . implode(", ", $likesArray2) . "</span></div>&nbsp;";}
                            
                                                    //Like/unlike button
                                                    if (!in_array($loggedInUser, $likesArray2)){echo "<a href='?likePost=" . $comments_array_row[0] . "'>Like</a>&nbsp";}
                                                    else {echo "<a href='?unLikePost=" . $comments_array_row[0] . "'>Unlike</a>&nbsp";}
                                                    echo "</font>";

                                                    echo "<font size='1'><br><br></font>";
                                            echo "</span></div>";                  
                                    }
                                }

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