<?php
require_once("config.php");

try {
    $db = new PDO("mysql:host=$hostname;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}

$period = isset($_GET['period']) ? $_GET['period'] : 'day';
$start_date = isset($_GET['start-date']) ? $_GET['start-date'] : null;
$end_date = isset($_GET['end-date']) ? $_GET['end-date'] : null;

if ($start_date && $end_date && $period==='fromUntil') {
    $sqlPeriod = 'time BETWEEN "' . $start_date . '" AND "' . $end_date . '"';
    $groupBy = 'DATE(time)';
} else {
    switch ($period) {
        case 'day':
            $sqlPeriod = 'time >= DATE(NOW())';
            $groupBy = 'HOUR(time)';
            break;
        case 'week':
            $sqlPeriod = 'time >= DATE(NOW()) - INTERVAL 7 DAY';
            $groupBy = 'DATE(time)';
            break;
        case 'month':
            $sqlPeriod = 'time >= DATE(NOW()) - INTERVAL 1 MONTH';
            $groupBy = 'DATE(time)';
            break;
        default:
            $sqlPeriod = 'DATE(time) = CURDATE()';
            $groupBy = 'HOUR(time)';
            break;
    }
}

$sql = "SELECT DISTINCT sensor_id FROM sensor_data WHERE " . $sqlPeriod;
$result = $db->query($sql);

$sensors = [];
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $sensors[] = $row['sensor_id'];
}

$sensorData = [];
foreach ($sensors as $sensor) {
    $sensorData[$sensor] = [];
    foreach (['temperature', 'humidity', 'pressure', 'pm25', 'pm100'] as $param) {
        if ($period) {
            $sql1 = "SELECT AVG(" . $param . ") AS avg_value, " . $groupBy . " AS time_group FROM sensor_data WHERE sensor_id ='" . $sensor . "' AND " . $sqlPeriod . " GROUP BY " . $groupBy . " ORDER BY time_group";
            $results = $db->query($sql1);
            $data = [["Time", $param]];
            while ($row = $results->fetch(PDO::FETCH_ASSOC)) {
                $data[] = [$row["time_group"], floatval($row["avg_value"])];
            }
            $sensorData[$sensor][$param] = $data;
        }
    }
}

echo json_encode($sensorData);
