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

//Get userID of loggedinUser
$getCurrentUserIDQuery = "SELECT id FROM users WHERE username = '$loggedInUser'";
$getCurrentUserID = sqlsrv_query($conn, $getCurrentUserIDQuery, array());
$currentUserID = sqlsrv_fetch_array($getCurrentUserID);
$currentUserID = $currentUserID[0];

//Get received pending friend invites of loggedInUser
$getCurrentUserReceivedPendingInvitesQuery = "SELECT userid FROM friends WHERE friendid = '$currentUserID' AND accepted = 'False'";
$currentUserReceivedPendingInvites = sqlsrv_query($conn, $getCurrentUserReceivedPendingInvitesQuery, array(), array( "Scrollable" => 'static' ));
$pendingRequestsCount = sqlsrv_num_rows($currentUserReceivedPendingInvites); //Count pending invites

//Convert pending friend invites of loggedInUser to array of user IDs
$currentUserReceivedPendingInvitesArray = array();
for ($x = 1; $x < $pendingRequestsCount + 1; $x++){
    $currentUserReceivedPendingInvitesRow = sqlsrv_fetch_array($currentUserReceivedPendingInvites, SQLSRV_FETCH_NUMERIC); //Select next row
    array_push($currentUserReceivedPendingInvitesArray, $currentUserReceivedPendingInvitesRow[0]);}

//Convert currentUserReceivedPendingInvitesArray from user IDs to usernames	
foreach ($currentUserReceivedPendingInvitesArray as &$value){	
    $convertQuery = " SELECT username FROM users WHERE id = '$value' ";	
    $convert = sqlsrv_query($conn, $convertQuery);	
    $convert = sqlsrv_fetch_array($convert);	
    $value = $convert[0];}	

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
        <?php if ($loggedInUser == "None"){echo '<a href="register.php">Register</a>&nbsp;';} ?>
        <?php if ($loggedInUser == "None"){echo '<a href="login.php"44>Log in</a>&nbsp;';} ?>
        <?php if ($loggedInUser != "None"){echo '<a href="logout.php">Log out</a>';} ?>
        <?php if ($loggedInUser != "None"){echo 'Welcome, <a href="profile.php?selectedUser=' . $loggedInUser . '">' .$loggedInUser. '</a>';}
        ?>
    </span>
</div>

<div class="contents">
    <div id="searchResults">
        <?php

            echo nl2br("Friend requests\n\n");
            
            if ($pendingRequestsCount > 0){
                for ($x = 1; $x < $pendingRequestsCount + 1; $x++){                        
                    //Display a user
                    echo nl2br("<font color='#0080ff'><b><a href='profile.php?selectedUser=".$currentUserReceivedPendingInvitesArray[$x-1]."'>".$currentUserReceivedPendingInvitesArray[$x-1]."</a></b></font>\n\n");
                }
            }
            else {
                echo "No friend reqests";
            }
            
        ?>
    </div>
</div>


</center>
</body>
</html>