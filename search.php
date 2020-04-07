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

/*
//Check if user was selected
if (isset($_GET["selectedUser"])){
    $selectedUser = $_GET["selectedUser"];}
else {
    $selectedUser = "None";}

date_default_timezone_set("America/New_York");
$timestamp = date("m/d/Y h:i:sa");
*/

if (isset($_POST["search"])){
    $search = $_POST["search"];}

//Connect to database
$serverName = "localhost\sqlexpress";
$connectionInfo = array("Database"=>"social_network", "UID"=>"ben", "PWD"=>"password123");
$conn = sqlsrv_connect($serverName, $connectionInfo);

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