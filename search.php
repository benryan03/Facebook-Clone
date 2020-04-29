<?php

//Get logged in user. If no user is logged in, redirect to login page.
session_start();
if (!isset($_SESSION["loggedInUser"])){
    header("Location:login.php");}
else{
    $loggedInUser = $_SESSION["loggedInUser"];}

//Get search term
if (isset($_POST["search"])){
    $search = $_POST["search"];}

//Connect to database
$serverName = "localhost\sqlexpress";
$connectionInfo = array("Database"=>"social_network", "UID"=>"ben", "PWD"=>"password123");
$conn = sqlsrv_connect($serverName, $connectionInfo);

//Get userID of loggedInUser
$getCurrentUserIDQuery = "SELECT id FROM users WHERE username = '$loggedInUser'";
$getCurrentUserID = sqlsrv_query($conn, $getCurrentUserIDQuery, array());
$currentUserID = sqlsrv_fetch_array($getCurrentUserID);
$currentUserID = $currentUserID[0];

//Get pending friend requests of loggedInUser
$getPendingRequestsQuery = "SELECT * FROM friends WHERE friendid = '$currentUserID' AND accepted = 'False'";
$getPendingRequests = sqlsrv_query($conn, $getPendingRequestsQuery, array(), array( "Scrollable" => 'static' ));
$pendingRequestsCount = sqlsrv_num_rows($getPendingRequests);

//Get current friends of loggedInUser
$getCurrentUserFriendsQuery =  "SELECT userid, friendid FROM friends WHERE (accepted = 'True' AND userid = '$currentUserID') OR (accepted = 'True' AND friendid = '$currentUserID') ";
$currentUserFriends = sqlsrv_query($conn, $getCurrentUserFriendsQuery, array(), array( "Scrollable" => 'static' ));
$currentUserFriendsCount = sqlsrv_num_rows($currentUserFriends); //Count friends for loop

//Convert current friends of loggedInUser to array of user IDs
$currentUserFriendsArray = array();
for ($x = 1; $x < $currentUserFriendsCount + 1; $x++){
    $currentUserFriendsRow = sqlsrv_fetch_array($currentUserFriends, SQLSRV_FETCH_NUMERIC); //Select next row
    array_push($currentUserFriendsArray, $currentUserFriendsRow[0]);
    array_push($currentUserFriendsArray, $currentUserFriendsRow[1]);}

$currentUserFriendsArray = array_unique($currentUserFriendsArray);
$currentUserFriendsArray = array_values($currentUserFriendsArray);
$currentUserFriendsCount = count($currentUserFriendsArray);


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

    <!--Search results-->
    <div class="contents">
        <div id="searchResults">
            <?php

                //////////////
                //User results
                echo nl2br("User results for " . $search . ":\n\n");

                //Count how many users were returned
                $query = "SELECT username FROM users WHERE username LIKE '%$search%'";
                $userResultsArray = sqlsrv_query($conn, $query, array(), array( "Scrollable" => 'static'));
                $userResultsCount = sqlsrv_num_rows($userResultsArray);
                
                if ($userResultsCount != 0){
                    for ($x = 1; $x < $userResultsCount + 1; $x++){
                        $userResultsArrayRow = sqlsrv_fetch_array($userResultsArray, SQLSRV_FETCH_NUMERIC); //Select next row in $query

                        //Display profile pic for user result
                        echo
                        "<div class='status'>".
                            "<span class='profileThumb'>".
                                "<a href='profile.php?selectedUser=" . $userResultsArrayRow[0] . "'><img src='";
                        
                                //If profile pic exists, display it. Else, display default profile pic.
                                if (file_exists("images\\".$userResultsArrayRow[0]."_32.jpg")){echo "images\\".$userResultsArrayRow[0]."_32.jpg";}         
                                else {echo "images\default_profile_picture_32.jpg";}
                                
                                echo "'></a>".
                            "</span>".
                        
                        //Display user result
                            "<span class='statusContent'>".
                                "<font color='#0080ff'><b><a href='profile.php?selectedUser=" . $userResultsArrayRow[0] . "'>" . $userResultsArrayRow[0]. "</a></b></font><br> " .
                            "</span>".
                        "</div><br><br>";
                    }
                }
                else {
                    echo "No results found.<br><br>";
                }
                
                //////////////
                //Post results
                echo nl2br("Post results for " . $search . ":\n\n");

                //Count how many posts were returned
                $postQuery = "SELECT * FROM posts WHERE post_text LIKE '%$search%'";
                $posts_array = sqlsrv_query($conn, $postQuery, array(), array( "Scrollable" => 'static'));
                $posts_count = sqlsrv_num_rows($posts_array);
                print_r( sqlsrv_errors() );

                if ($posts_count != 0){
                    for ($x = 1; $x < $posts_count + 1; $x++){
                        $posts_array_row = sqlsrv_fetch_array($posts_array, SQLSRV_FETCH_NUMERIC); //Select next row in $query
                            //If logged in user is friends with post author, display post
                            if ($posts_array_row[2] == $loggedInUser || in_array($posts_array_row[8], $currentUserFriendsArray)){
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
                                        //if (!in_array($loggedInUser, $likesArray)){echo "<a href='?selectedUser=" . $selectedUser . "&likePost=" . $posts_array_row[0] . "'>Like</a>&nbsp";}
                                        ///else {echo "<a href='?selectedUser=" . $selectedUser . "&unLikePost=" . $posts_array_row[0] . "'>Unlike</a>&nbsp";}
                
                                        //Comment button
                                echo    /*"<a href='?'>Comment</a>*/"</font>" .
                                    "</span>".
                                "</div><br><br>";
                            }
                        }
                }
                else {
                    echo "No results found.";
                }

            ?>
        </div>
    </div>
    <div class="debug">

    </div>
    </center>
</body>
</html>