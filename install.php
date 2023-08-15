//this file is to make new table to database if there isn't any. Login credentials will 
//be fetched from login_info.php file.

<?php
    include 'login_info.php';
?>

<?php

	//Connect to database and create table

	//create connection
	$conn = new mysqli($servername, $username, $password, $dbname);
	//check connection
	if($conn->connect_error)
	{
		die("Connection failed: ". $conn->connect_error);
	}
	
	//SR No, GPS number, Latitude, Longitude, Date, Time
	//  1        5        64.1243   27.12412  2022-03-22  18.54
	//sql to create table
	$sql = "CREATE TABLE logs (
	id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	gpsnumber VARCHAR(30),
	Latitude VARCHAR(30),
	Longitude VARCHAR(30),
	`Date` DATE NULL,
	`Time` TIME NULL, 
	`TimeStamp` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
	)";
        //up there not sure if timestamp is neccessary or no.	
	if($conn->query($sql) === TRUE)
	{
		echo "Table logs created succesfully";
	}
	else
	{
		echo "Error creating table: " . $conn->error;
	}
	
	$conn->close()
?>