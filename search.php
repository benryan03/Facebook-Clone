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
                echo nl2br("User results for " . $search . ":\n\n");

                //Count how many users were returned
                $query = "SELECT username FROM users WHERE username LIKE '%$search%'";
                $userResultsArray = sqlsrv_query($conn, $query, array(), array( "Scrollable" => 'static'));
                $userResultsCount = sqlsrv_num_rows($userResultsArray);
                
                if ($userResultsCount != 0){
                    for ($x = 1; $x < $userResultsCount + 1; $x++){
                        $userResultsArrayRow = sqlsrv_fetch_array($userResultsArray, SQLSRV_FETCH_NUMERIC); //Select next row in $query

                        //Display a user
                        echo nl2br("<font color='#0080ff'><b><a href='profile.php?selectedUser=".$userResultsArrayRow[0]."'>".$userResultsArrayRow[0]."</a></b></font>\n\n");
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