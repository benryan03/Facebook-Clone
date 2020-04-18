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

//
//If new status has been posted
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
//If user uploaded an image
//
$postImageError = "";
if (isset($_POST["postImage"])){

    //To calculate new post ID, count number of rows in database and add 1
    $countExistingPostsQuery = "SELECT * FROM posts";
    $countExistingPosts = sqlsrv_query($conn, $countExistingPostsQuery, array(), array( "Scrollable" => 'static' ));
    $posts_count = sqlsrv_num_rows( $countExistingPosts );
    $newPostID = $posts_count + 1;

    //Set filename of image to new post ID
    $path = $_FILES['file']['name'];
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    $target_file = "images/" . $newPostID . "." . $ext;

    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    
    // Check if image file is a actual image or fake image
    if(isset($_POST["submit"])) {
        $check = getimagesize($_FILES["file"]["tmp_name"]);
        if($check !== false) {
            echo "File is an image - " . $check["mime"] . ".";
            $uploadOk = 1;
        } else {
            $postImageError = "File is not an image.";
            $uploadOk = 0;
        }
    }
    // Check file size
    if ($_FILES["file"]["size"] > 500000) {
        $postImageError = "Sorry, your file is too large.";
        $uploadOk = 0;}
    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        $postImageError = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;}
    // if everything is ok, try to upload file
    if ($uploadOk == 1) {   
        if (!move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
            $postImageError = "Sorry, there was an error uploading your file.";}
    }

    $newPostQuery = "INSERT INTO posts VALUES ('$newPostID', '', '$loggedInUser', '$timestamp', '$target_file', '$timestamp', '0', '$currentUserID', '$currentUserID', '$loggedInUser') ";
    $newPostSubmit = sqlsrv_query($conn, $newPostQuery);
    if (!$newPostSubmit){
        print_r(sqlsrv_errors());}

}

//
//If user liked a post
//

//Still need to add permission check
if (isset($_GET["likePost"])){
    $likedPostID = $_GET["likePost"];
    $likePostQuery = "INSERT INTO likes VALUES ('$likedPostID', '$loggedInUser')";
    $likePost = sqlsrv_query($conn, $likePostQuery);
}

//
//If user unliked a post
//

//Still need to add permission check
if (isset($_GET["unLikePost"])){
    $unLikedPostID = $_GET["unLikePost"];
    $likePostQuery = "DELETE FROM likes WHERE post_id = '$unLikedPostID' AND liked_by = '$loggedInUser'";
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


    <!-- Content feed-->
    <div class="feed">
        Your News Feed<br>

        <!--Post a status-->
        <form action="?" method="post">
        <textarea name="new_status" rows="1" cols="40" placeholder="Post a status"></textarea>
        <input type="submit" value="Submit" name="submit_status"><br>
        <!--<div class="error" id="status_error"><br></div>-->
        </form>

        <!-- Post an image-->
        <?php
            if (!isset($_GET["postImage"])){
                echo "<a href='?postImage'>Post an image</a><br><br>";}
            else {
                echo "<form action='?' method='post' enctype='multipart/form-data'>".
                    "Select image to post:<br>".
                    "<input type='file' name='file' id='file'><br>".
                    "<input type='submit' value='Post' name='postImage'>".
                    "</form>";
            }
        ?>
        <div class="error"><?php echo $postImageError; ?></div>
            
        <?php
        //Count how many comments are in the the thread
        $currentUserFriendsString = "'".implode("', '", $currentUserFriendsArray)."'";
        $query = "SELECT * FROM posts WHERE post_author_id IN ($currentUserFriendsString) OR post_author = '$loggedInUser' ORDER BY date_submitted DESC";
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
                        if ($posts_array_row[4] != " "){echo "<a href='view_image.php?selectedImage=" . substr(strval($posts_array_row[4]), 7) . "'><img src='" . $posts_array_row[4] . "'></a><br>";}
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
                        if (!in_array($loggedInUser, $likesArray)){echo "<a href='?likePost=" . $posts_array_row[0] . "'>Like</a>&nbsp";}
                        else {echo "<a href='?unLikePost=" . $posts_array_row[0] . "'>Unlike</a>&nbsp";}

                        //Comment button
                echo    "<a href='?'>Comment</a></font>" .
                    "</span>".
                "</div><br><br>";
        }

        ?>
    </div>
    </center>
</body>
</html>