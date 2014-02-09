<?php

	$host="localhost"; // Host name 
	$username="root"; // Mysql username 
	$password=""; // Mysql password 
	$db_name="koken"; // Database name 
	$tbl_name="koken_albums"; // Table name 

	// Connect to server and select databse.
	mysql_connect("$host", "$username", "$password")or die("cannot connect"); 
	mysql_select_db("$db_name")or die("cannot select DB");

	$sql = "SELECT * FROM $tbl_name";
	$result = mysql_query($sql);

	if ( !$result ) {
		die('Invalid query: ' . mysql_error());
	} else {
		while($row = mysql_fetch_assoc($result)){
		    foreach($row as $cname => $cvalue){
		        print "$cvalue\t";
		    }
		    print "<br />";
		}
	}

?>