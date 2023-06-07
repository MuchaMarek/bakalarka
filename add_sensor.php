<?php
require_once("config.php");
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $db = new PDO("mysql:host=$hostname;dbname=$dbname", $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $address = $_POST["address"];
        $sql = "SELECT DISTINCT sensor_id FROM sensor_data WHERE CONCAT(city, ' ', address, ', ', country) = :address";
        $stmt = $db->prepare($sql);
        $stmt->execute(['address' => $address]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $_SESSION['comparedSensors'][] = $result['sensor_id'];
        }

        echo json_encode(["success" => true]);
    } catch(PDOException $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}
