<?php 
# Used to store settings for connecting to database
$db_hostname = "****";
$db_database = "****";
$db_username = "****";
$db_password = "****";
$db_charset = "utf8mb4";
$dsn = "mysql:host=$db_hostname;port=3306;dbname=$db_database;charset=$db_charset";
$opt = array(
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
PDO::ATTR_EMULATE_PREPARES => false
);

?>