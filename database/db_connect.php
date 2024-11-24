<?php 
$servername = "localhost";
$username = "admin";
$password = "200605";
$dbname = "kilburnazon";

// Create connection
$connection = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($connection -> connect_error){
    die("Connection failed: " . $connection->connect_error);
}

?>