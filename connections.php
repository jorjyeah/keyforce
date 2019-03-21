<?php
$dbhost = "localhost:3306";
$dbuser = "root";
$dbpass = "";
$dbname = 'invicikey';
$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
//echo "Connected successfully";
?>