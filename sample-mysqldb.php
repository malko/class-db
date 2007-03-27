<?php
/**
* sample script for class-mysqldb
*/

require('class-mysqldb.php');

$dbname = 'test';
$dbhost = 'localhost';
$dbuser = 'root';
$dbpass = '';

# instantiate to database with autoconnection
$db = mysqldb($dbname,$dbhost,$dbuser,$dbpass);

?>