<?php
// This tells PHP to look at the 'Variables' you set in Railway
$conn = new mysqli(
    getenv('MYSQLHOST'), 
    getenv('MYSQLUSER'), 
    getenv('MYSQLPASSWORD'), 
    getenv('MYSQLDATABASE'), 
    getenv('MYSQLPORT')
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>