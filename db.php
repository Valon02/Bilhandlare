<?php

$host = "127.0.0.1";
$user = "root";
$password = "";
$database = "bilhandlare";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Databasanslutningen misslyckades: " . $conn->connect_error);
}

?>