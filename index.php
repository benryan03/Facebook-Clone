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

//Get unseen Likes
$likesNotificationCountQuery = "SELECT * FROM likes WHERE post_author = '$loggedInUser' AND notified = 'False'";
$getLikesNotificationCount = sqlsrv_query($conn, $likesNotificationCountQuery, array(), array( "Scrollable" => 'static' ));
$likesNotificationCount = sqlsrv_num_rows($getLikesNotificationCount);

function calculateNewPostID(){
    //To calculate new post ID, count number of rows in database and add 1
    global $conn;
    $countExistingPosts = sqlsrv_query($conn, "SELECT * FROM posts", array(), array( "Scrollable" => 'static' ));
    $postsCount = sqlsrv_num_rows($countExistingPosts);
    $newPostID = $postsCount + 1;
    return $newPostID;
}

///////////////////////////////
//If new status has been posted
if (isset($_POST["new_status"])){
    $newStatus = $_POST["new_status"];
    $newPostID = calculateNewPostID();    
    $newPostSubmit = sqlsrv_query($conn, "INSERT INTO posts VALUES ('$newPostID', '$newStatus', '$loggedInUser', '$timestamp', ' ', '$timestamp', '0', '$currentUserID', '$currentUserID', '$loggedInUser', null) ");
    if (!$newPostSubmit){
        print_r(sqlsrv_errors());}
}

///////////////////////////
//If user uploaded an image
$postImageError = "";
if (isset($_POST["postImage"])){
    $newPostID = calculateNewPostID();    

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

    $newPostQuery = "INSERT INTO posts VALUES ('$newPostID', '', '$loggedInUser', '$timestamp', '$target_file', '$timestamp', '0', '$currentUserID', '$currentUserID', '$loggedInUser', null) ";
    $newPostSubmit = sqlsrv_query($conn, $newPostQuery);
    if (!$newPostSubmit){
        print_r(sqlsrv_errors());}

}

//////////////////////
//If user liked a post
//Still need to add permission check
if (isset($_GET["likePost"])){
    $likedPostID = $_GET["likePost"];
    $author = $_GET["author"];
    $likePostQuery = "INSERT INTO likes VALUES ('$likedPostID', '$author', '$loggedInUser', 'false')";
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

///////////////////////////////////////
//If user submitted a comment on a post
if (isset($_POST["submitComment"])){
    $commentOn = $_GET["commentOn"];
    $newPostID = calculateNewPostID();    
    $commentText = $_POST['comment'];
    $newPostSubmit = sqlsrv_query($conn, "INSERT INTO posts VALUES ('$newPostID', '$commentText', '$loggedInUser', '$timestamp', '', '$timestamp', '0', '0', '$currentUserID', 'NULL', $commentOn) ");
    if (!$newPostSubmit){
        print_r(sqlsrv_errors());}
}

///////////////////////////
//If not viewing first page
$page_number = 0;
if (isset($_GET["page"])){
    $page_number = $_GET["page"];

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
                //Search bar
                echo '<form action="search.php" method="post" style="display: inline;"><input type="text" name="search" placeholder="Search"><input type="submit" value="Submit" name="submitSearch"></form>';
                //Pending friend requests notification
                if ($pendingRequestsCount > 0){
                    echo '&nbsp;&nbsp;<a href="requests.php"><font color="red">'.$pendingRequestsCount.' new friend requests</font></a>';
                }
                //New likes notification
                if ($likesNotificationCount > 0){
                    echo '&nbsp;&nbsp;<a href="likes.php"><font color="red">'.$likesNotificationCount.' new likes</font></a>';
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
        //Count how many total posts are in the the feed
        $currentUserFriendsString = "'".implode("', '", $currentUserFriendsArray)."'";
        $query = "SELECT * FROM posts WHERE post_author_id IN ($currentUserFriendsString) AND comment_of IS NULL OR post_author = '$loggedInUser' AND comment_of IS NULL";
        $posts_array = sqlsrv_query($conn, $query, array(), array( "Scrollable" => 'static'));
        $posts_count = sqlsrv_num_rows($posts_array);

        //Query 10 posts for page
        $offset = $page_number * 10;
        $query = "SELECT * FROM posts WHERE post_author_id IN ($currentUserFriendsString) AND comment_of IS NULL OR post_author = '$loggedInUser' AND comment_of IS NULL ORDER BY date_submitted DESC OFFSET $offset ROWS FETCH NEXT 10 ROWS ONLY";
        $posts_array = sqlsrv_query($conn, $query, array(), array( "Scrollable" => 'static'));

        $anchor = 0;

        for ($x = 1; $x < 11; $x++){
            $posts_array_row = sqlsrv_fetch_array($posts_array, SQLSRV_FETCH_NUMERIC); //Select next row in $query
            $anchor++;
            echo "<a id='" . $anchor . "'></a>"; //Direct link to post
            //Display OP
            if (isset($posts_array_row[0])){
            echo "<div class='status'>";
            echo    "<span class='profileThumb'>";
            echo        "<a href='profile.php?selectedUser=" . $posts_array_row[2] . "'><img src='";
                        //Get profile pic or null
                        $getProfilePicQuery = "SELECT profile_pic FROM users WHERE username = '$posts_array_row[2]'";
                        $getProfilePic = sqlsrv_query($conn, $getProfilePicQuery, array());
                        $profilePic = sqlsrv_fetch_array($getProfilePic);

                        //If profile pic exists, display it. Else, display default profile pic.
                        if ($profilePic[0] != null){echo "images/" . $profilePic[0];}                           
                        else {echo "images\default_profile_picture_32.jpg";}
            echo        "'></a>";
            echo    "</span>";
                
            echo    "<span class='statusContent'>";
                        //Username of post author
            echo        "<font color='#0080ff'><b><a href='profile.php?selectedUser=" . $posts_array_row[2] . "'>" . $posts_array_row[2]. "</a>" . "</b></font> ";

                        //If post is not on author's wall, display username of that user
                        if ($posts_array_row[7] != $posts_array_row[8]){echo "<font color='#0080ff'><b></a>" . " > " . "<a href='profile.php?selectedUser=" . $posts_array_row[9] . "'>" . $posts_array_row[9] . "</a>" . "</b></font></br> ";}
                        else {echo "<br>";} 
                        
                        //Date posted
            echo        "<font color='gray' size='2'>" . date_format($posts_array_row[3], "m/d/Y h:ia") . "</font><br>";
                    
                        //Post content
                        if ($posts_array_row[4] != " "){echo "<a href='view_image.php?selectedImage=" . substr(strval($posts_array_row[4]), 7) . "'><img src='" . $posts_array_row[4] . "'></a><br><font size='2'>";}
                        else {echo $posts_array_row[1] . "<br><font size='2'>";}

                        //Get number of likes
                        $getLikesQuery = "SELECT * FROM likes WHERE post_id = '$posts_array_row[0]'";
                        $getLikes = sqlsrv_query($conn, $getLikesQuery, array(), array( "Scrollable" => 'static' ));
                        $likesCount = sqlsrv_num_rows($getLikes);

                        //Convert users who liked current post to array
                        $likesArray = array();
                        for ($y = 1; $y < $likesCount + 1; $y++){
                            $likesRow = sqlsrv_fetch_array($getLikes, SQLSRV_FETCH_NUMERIC); //Select next row
                            array_push($likesArray, $likesRow[2]);}

                        if ($likesCount == 1) {echo "<div class='tooltip'>1 like<span class='tooltiptext'>" . implode(" ,", $likesArray) . "</span></div>&nbsp;";}
                        else if ($likesCount > 1) {echo "<div class='tooltip'>" . $likesCount . "&nbsp;likes<span class='tooltiptext'>" . implode(", ", $likesArray) . "</span></div>&nbsp;";}

                        //Like/unlike button
                        if (!in_array($loggedInUser, $likesArray)){echo "<a href='?likePost=" . $posts_array_row[0] . "&author=" . $posts_array_row[2] . "&page=" . $page_number . "#" . $anchor . "'>Like</a>&nbsp";}
                        else {echo "<a href='?unLikePost=" . $posts_array_row[0] . "&page=" . $page_number . "#" . $anchor . "'>Unlike</a>&nbsp";}

                        //Comment button/box
                        if (isset($_GET["commentOn"]) && $_GET["commentOn"] == $posts_array_row[0]) { echo
                            "<form action='?commentOn=" . $posts_array_row[0] . "&page=" . $page_number . "#" . $anchor . "' method='post'>" .
                            "<input type='text' name='comment' placeholder='Add a comment'>" . 
                            "<input type='submit' value='Submit' name='submitComment'>&nbsp;<a href='?page=" . $page_number . "#" . $anchor . "'>Cancel</a><br>" . 
                            "</form>";
                        }
                        else {echo "<a href='?commentOn=" . $posts_array_row[0] . "&page=" . $page_number . "#" . $anchor . "'>Comment</a>";}
            echo        "</font>";
            echo    "</span><br><br>";

                    //Count how many comments the post has
                    $comments_array = sqlsrv_query($conn, "SELECT * FROM posts WHERE comment_of = '$posts_array_row[0]' ", array(), array( "Scrollable" => 'static'));
                    $comments_count = sqlsrv_num_rows($comments_array);
                
                                    
                    if ($comments_count > 0){
                        for ($z = 1; $z < $comments_count + 1; $z++){
                            $comments_array_row = sqlsrv_fetch_array($comments_array, SQLSRV_FETCH_NUMERIC); //Select next row in $query
                            $anchor++;
            echo    "<a id='" . $anchor . "'></a>"; //Direct link to comment
                    //Display comment
            echo    "<div class='statusComment'>";
            echo        "<span class='commentProfileThumb'>";
            echo            "<a href='profile.php?selectedUser=" . $comments_array_row[2] . "'><img src='";
                            //Get profile pic or null
                            $getProfilePicQuery2 = "SELECT profile_pic FROM users WHERE username = '$comments_array_row[2]'";
                            $getProfilePic2 = sqlsrv_query($conn, $getProfilePicQuery2, array());
                            $profilePic2 = sqlsrv_fetch_array($getProfilePic2);
    
                            //If profile pic exists, display it. Else, display default profile pic.
                            if ($profilePic2[0] != null){echo "images/" . $profilePic2[0];}                           
                            else {echo "images\default_profile_picture_32.jpg";}
            echo            "'></a>";
            echo        "</span>";            
            echo        "<span class='commentContent'>";
                            //Username of post author
            echo            "<font color='#0080ff'><b><a href='profile.php?selectedUser=" . $comments_array_row[2] . "'>" . $comments_array_row[2]. "</a>" . "</b></font><br> ";
            
                            //Date posted
            echo            "<font color='gray' size='2'>" . date_format($comments_array_row[3], "m/d/Y h:ia") . "</font><br>";
                                
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
                                array_push($likesArray2, $likesRow2[2]);}

                            echo "<font size='2'>";
                            if ($likesCount2 == 1) {echo "<div class='tooltip'>1 like<span class='tooltiptext'>" . implode(" ,", $likesArray2) . "</span></div>&nbsp;";}
                            else if ($likesCount2 > 1) {echo "<div class='tooltip'>" . $likesCount2 . "&nbsp;likes<span class='tooltiptext'>" . implode(", ", $likesArray2) . "</span></div>&nbsp;";}
    
                            //Like/unlike button
                            if (!in_array($loggedInUser, $likesArray2)){echo "<a href='?likePost=" . $comments_array_row[0] . "&page=" . $page_number ."#" . $anchor . "'>Like</a>&nbsp";}
                            else {echo "<a href='?unLikePost=" . $comments_array_row[0] . "&page=" . $page_number . "#" . $anchor . "'>Unlike</a>&nbsp";}
                            echo "</font>";

                            echo "<font size='1'><br><br></font>";
            echo        "</span>";
            echo    "</div>";          
                        }
                    }
            echo "</div><br><br>";
            }
        }
        //Page navigation links
        if ($posts_count > 10 && isset($posts_array_row[0]) && $page_number == 0) { //First of multiple pages
            echo "Showing posts " . (($page_number * 10) + 1) . "-" . (($page_number + 1) * 10) . " of " . $posts_count . "&nbsp;<a href=?page=" . ($page_number + 1) . ">Next page</a>&nbsp;";
        }
        else if ($posts_count > 10 && isset($posts_array_row[0]) && $page_number > 0 && ((($page_number + 1) * 10) < $posts_count)) { //Multiple pages, not first or last
            echo "Showing posts " . (($page_number * 10) + 1) . "-" . (($page_number + 1) * 10) . " of " . $posts_count . "&nbsp;<a href=?page=" . ($page_number + 1) . ">Next page</a>&nbsp;<a href=?page=" . ($page_number - 1) . ">Previous page</a>";
        }
        else if ($posts_count > 10 && isset($posts_array_row[0]) && $page_number > 0) { //Last of multiple pages, with no posts left over
            echo "Showing posts " . (($page_number * 10) + 1) . "-" . $posts_count . " of " . $posts_count . "&nbsp;<a href=?page=" . ($page_number - 1) . ">Previous page</a>";
        }
        else if ($posts_count <= 10){ //Only page
            echo "Showing posts 1-" . $posts_count . " of " . $posts_count;
        }
        else if (!isset($posts_array_row[0])){ //Last page, with <10 posts left
            echo "Showing posts " . (($page_number * 10) + 1) . "-" . $posts_count . " of " . $posts_count . "&nbsp;<a href=?page=" . ($page_number - 1) . ">Previous page</a>";
        }
        ?>
    </div>
    </center>
</body>
</html>