<?php

$config = require __DIR__ . "/db_config.php";

$conn = new mysqli(
    $config["host"],
    $config["user"],
    $config["password"],
    $config["database"],
    $config["port"]
);

if ($conn->connect_error) {
    die(
        "Databasanslutningen misslyckades: "
        . $conn->connect_error
    );
}

?>