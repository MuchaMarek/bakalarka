<?php
require_once("config.php");
session_start();
try {
    $db = new PDO("mysql:host=$hostname;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (isset($_POST['sensor_id'])) {
        $sensorId = $_POST['sensor_id'];
        $sql = "SELECT DISTINCT address FROM sensor_data WHERE sensor_id = '" . $sensorId . "'  LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result["address"]) {
            echo $result["address"];
        } else {
            echo "No address found for this sensor!";
        }
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

