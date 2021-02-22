<?php
	
//basepath = This is the top folder of your installation. 
//If your installation is at 'www.example.com/farts/' set this to 'farts'. Leave the string empty for installs at the web server root.
//You don't need trailing or ending slashes for this.

$basepath = 'admin/stocks';

//SQL vars for database storage.
//Create a MySQL database and import the 'stocks.sql' file
$sql_username = 'your-sql-username';
$sql_password = 'your-sql-password';
$sql_dbname = 'stocksdb';
$sql_server = 'localhost'; //Default localhost
$sql_port = '3306'; //Default 3306

//Select your theme from /lib/templates/{theme_name}/
$selected_theme = 'default';

//Globals for some API providers
//Some endpoints are only licensed for personal use. Set this to true to use these restricted APIs
$isPersonalInstallation = true;

?>