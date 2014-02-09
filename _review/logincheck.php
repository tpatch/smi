<?php 
    include 'header.php'; 

    if ( !isset($_SESSION['count']) ) {
        $_SESSION['count'] = 0;
    } else {
        $_SESSION['count']++;
    };

    // Connect to server and select databse.
    mysql_connect("$host", "$username", "$password")or die("cannot connect"); 
    mysql_select_db("$db_name")or die("cannot select DB");

    // To protect MySQL injection (more detail about MySQL injection)
    $myusername = stripslashes($_POST["username"]);
    $mypassword = stripslashes($_POST["password"]);
    $keyword = stripslashes($_POST["keyword"]);
    $myusername = mysql_real_escape_string($myusername);
    $mypassword = mysql_real_escape_string($mypassword);
    $keyword = str_replace(' ', '', mysql_real_escape_string($keyword));
    $sql = "SELECT * FROM members WHERE username = '$myusername' AND password = '$mypassword'";
    $result = mysql_query($sql);

    // Mysql_num_row is counting table row
    $count = mysql_num_rows($result);

    // If result matched $myusername and $mypassword, table row must be 1 row
    if( $count == 1 ){
        // Register $myusername, $mypassword and redirect to file "login_success.php"
        $_SESSION['auth'] = 1;
        $_SESSION['gallery'] = $keyword;
        header("location: /k/koken/_review/loginsuccess.php");
    }
    else {
        echo "Sorry, we couldn't find that username and password. <a href='index.php'>Try again</a>.";
    }
?>

<?php include 'footer.php'; ?>