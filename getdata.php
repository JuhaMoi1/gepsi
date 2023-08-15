<?php
    include 'login_info.php';
?>

<?php


$mysqli = new mysqli($servername, $username, $password, $dbname);

if($mysqli->connect_error)
{
	exit('could not connect');
}

$sql = $sql = "SELECT Latitude, Longitude, Date, Time FROM logs ORDER BY id DESC";

$stmt = $mysqli->prepare($sql);

$stmt->execute();
$stmt->store_result();
$stmt->bind_result($latitude, $longitude, $date, $time);
$stmt->fetch();
$stmt->close();

echo "[";
echo json_encode($latitude);
echo ", ";
echo json_encode($longitude);
echo ", ";
echo json_encode($date);
echo ", ";
echo json_encode($time);
echo "]";

?>