<?php
$dbname='asiya_db';
$host='localhost:3307';

$user= 'root';
$password='';


$conn = mysqli_connect($host,$user,$password,$dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}


?>