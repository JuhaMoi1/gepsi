<?php
	include 'login_info.php';
?>

<!DOCTYPE HTML>
<html lang="en">
  <head>
    <link rel=“shortcut icon” type=“image/x-icon” href="/favicon.ico">
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" 
	integrity="sha512-xodZBNTC5n17Xt2atTPuE1HxjVMSvLVW9ocqUKLsCC5CXdbqCmblAshOMAS6/keqq/sMZMZ19scR4PsZChSR7A==" 
	crossorigin="" />
	<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"
	integrity="sha512-XQoYMqMTK8LvdxXYG3nZ448hOEQiglfqkJs1NOQV44cWnUrBc8PkAOcXy20w0vlaXaVUearIOBhiXZ5V3ynxwA=="
	crossorigin=""></script>
    <style>
      html, body {
        height: 100%;
        padding: 0;
        margin: 0;
      }
      #map {
        /* configure the size of the map */
        width: 100%;
        height: 95%;
      }
    </style>
  </head>
 <body> 
<?php
	//connect to database and create table

	
	//Create connection
	$conn = new mysqli ($servername, $username, $password, $dbname);
	//check connection
	if($conn->connect_error)
	{
		die("Database connection failed: " . $conn->connect_error);
		echo "<a href='install.php'>If first time running click here to install database</a>";
	}


	$sql = "SELECT * FROM logs ORDER BY id DESC";

	if($result=mysqli_query($conn,$sql))
	{	
	    $db_size = 0;
		while ($row=mysqli_fetch_row($result))
		{
		    //if connected satelite amount higher than 3, get info from db.
		    if($row[1] > 3)
		    {
		        $longitude[$db_size] = $row[3];
		        $latitude[$db_size] = $row[2];
		        $date[$db_size] = $row[4];
		        $time[$db_size] = $row[5];
		        $db_size++;
		    }
		}

		//free result set
		mysqli_free_result($result);
	}
	mysqli_close($conn);
	
?>
    <div id="offline"></div>
    <div id="map"> </div>
    <script>
	<?php
		echo "var lat1 = '$latitude[0]';";
		echo "var long1 = '$longitude[0]';";
		echo "var lats = [];";
		echo "var longs = [];";
		echo "var times = [];";
		echo "var dates = [];";
		echo "var db_size = '$db_size';";
		for($i = 0; $i < $db_size; $i++)
		{
			echo "lats[$i] = '$latitude[$i]';";
			echo "longs[$i] = '$longitude[$i]';";
			echo "times[$i] = '$time[$i]';";
			echo "dates[$i] = '$date[$i]';";
		}
	?>
		var jsonResponse;
		var time;
		var date;
		var curlatlngs = [];
function showCoord()
{
	const xhttp = new XMLHttpRequest();
	
	xhttp.onload = function()
	{
		jsonResponse = JSON.parse(this.responseText);
		
	}
	xhttp.open("GET", "getdata.php");
	xhttp.send();
	
	setTimeout(showCoord, 5000);
	if(jsonResponse)
	{
	    if(jsonResponse[0] && jsonResponse[1])
	    {
	        if(jsonResponse[0] != lat1 || jsonResponse[1] != long1)
	        {
		        //curlat.push(jsonResponse[0]);
		        lat1 = jsonResponse[0];
		        //curlong.push(jsonResponse[1]);
		        long1 = jsonResponse[1];
		        curlatlngs.push([lat1, long1]);
		        console.log("current latlngs: ",curlatlngs);
		        //console.log(lat1);
		        //console.log(long1);
	        }
	    }
        else
        {
            //console.log("bad gps signal");
            document.getElementById('offline').innerHTML = "<-----gps signal is bad----->";
        }
		date = jsonResponse[2];
		time = jsonResponse[3];
		
	}
	var time1 = new Date();
	var time2 = new Date(date + ' ' + time);
	//console.log(date, time);
	//console.log(time2);
	var differenceInTime = (time1.getTime() - time2.getTime()) / (1000 * 60);
	console.log("time difference, if this reaches 15 then offline: ", differenceInTime);
	//this hardcoded number here is minutes offline to give error message
	if(differenceInTime > 15)
	{
	    document.getElementById('offline').innerHTML = "<-----GPS device is offlineee. Last time seen online: " + date + " " + time + "----->";
	}
	/*
	 * This makes marker on the map to disappear and to move new position due to above jsonresponse.
	 * This always refreshes the marker, even though it doesen't move. But the clicked information
	 * disappears, witch is quite annoying. But can't figure out how to keep the clicked info allways
	 * displayed.
	 */
	fg.clearLayers();
	
	marker = new L.marker({lon: long1, lat: lat1}).bindPopup(lat1 + ", "+ long1 + ", " + time + ", " + date).addTo(fg);
	//map.flyTo({lon: long1, lat: lat1});
    //flyTo, setView ja panTo kaikki samannäköisiä
	//console.log("Lat: ", lat1, "lon: ", long1);
	var ppolyline = L.polyline(curlatlngs, {color: 'green'});
	ppolyline.addTo(fg);
}
	var zoom = 13;
	
      // initialize Leaflet
      var map = L.map('map').setView({lon: long1, lat: lat1}, zoom);

      // add the OpenStreetMap tiles
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap contributors</a>'
      }).addTo(map);
	   var fg = L.featureGroup().addTo(map);
	var time = new Date();

		 var latlngs = [];
		for(var i = 0; i < db_size; i++)
		{

		    var timme = new Date(dates[i] + ' ' + times[i]);

		    var timeDifference = (time.getTime() - timme.getTime()) / (1000 * 60 * 24);
            //time difference in hours.
		    if(timeDifference < 24)
		    {
		        console.log("time difference: ", timeDifference);
		        latlngs[i] = [lats[i], longs[i]];
                console.log("latlngs: ", lats[i]);
		    }
		}
		
	  var polyline = L.polyline(latlngs, {color: 'red'});
	  polyline.addTo(map);

	  
      // show the scale bar on the lower left corner
      L.control.scale({imperial: true, metric: true}).addTo(map);

      // show a marker on the map
	  
	  showCoord();
    </script>
	
  </body>
</html>