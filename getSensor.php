<?php
require_once("config.php");
session_start();
try {
    $db = new PDO("mysql:host=$hostname;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT DISTINCT sensor_id FROM sensor_data";
    $result = $db->query($sql);

    $sensors = array();
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $sensors[] = $row['sensor_id'];
    }

    if (isset($_POST['sensorIndex'])) {
        echo $sensors[$_POST['sensorIndex']];
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

