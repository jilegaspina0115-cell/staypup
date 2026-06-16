<?php
// db_connect.php
// Detect live cloud variables on Railway, otherwise execute local XAMPP profile settings
$db_host = isset($_ENV['MYSQLHOST'])     ? $_ENV['MYSQLHOST']     : "localhost";
$db_user = isset($_ENV['MYSQLUSER'])     ? $_ENV['MYSQLUSER']     : "root";
$db_pass = isset($_ENV['MYSQLPASSWORD']) ? $_ENV['MYSQLPASSWORD'] : "";
$db_name = isset($_ENV['MYSQLDATABASE']) ? $_ENV['MYSQLDATABASE'] : "staypup_db";
$db_port = isset($_ENV['MYSQLPORT'])     ? $_ENV['MYSQLPORT']     : "3306";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn->connect_error) {
    die("<div style='color:red;'>Database Connection Broken: " . $conn->connect_error . "</div>");
}
?>