
<?php  //gets login info
    include 'login_info.php';
?>

<?php
// gps device uses this to post data to database.
//creates new record as per request
	//connect to database
	
	//create connection
	$conn = new mysqli($servername, $username, $password, $dbname);
	//check connection
	if($conn->connect_error)
	{
		die("Database connection failed: " . $conn->connect_error);
	}
	
	//Get current date and time
	date_default_timezone_set('Europe/Helsinki');
	$d = date("y-m-d");
	//echo " Date:".$d."<BR>";
	$t = date("H:i:s");
	
	if(!empty($_POST['status']) &&!empty($_POST['gpsnumber']) && !empty($_POST['latitude']) && 
	!empty($_POST['longitude']))
	{
		
		$status = $_POST['status'];

		if($status == "online")
		{
		    $gpsnumber = $_POST['gpsnumber'];
		    $latitude = $_POST['latitude'];
		    $longitude = $_POST['longitude'];
		
		    $sql = "INSERT INTO logs (gpsnumber, Latitude, Longitude, Date, Time)
		
		    VALUES ('".$gpsnumber."', '".$latitude."', '".$longitude."', '".$d."', '".$t."')";

		    if ($conn->query($sql) === TRUE)
		    {
			    echo "OK";
		    }
		    else
		    {
			    echo "Error: " . $sql . "<br>" . $conn->error;
		    }
		    }
		else
		{
		    $sql = "INSERT INTO logs(Date, Time)
		    VALUES ('".$d."', '".$t."')";
		    if($conn->query($sql) === TRUE)
		    {
		        echo "posted date and time only";
		    }
		    else
		    {
		        echo "Error: " . $sql . "<br". $conn->error;
		    }
		}
	}
	
	$conn->close();
	
?>
	
	
	
