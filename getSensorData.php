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

//switch ($period) {
//    case 'day':
//        $sqlPeriod = 'DATE(time) =DATE(NOW())';
//        break;
//    case 'week':
//        $sqlPeriod = 'time >= DATE(NOW()) - INTERVAL 7 DAY';
//        break;
//    case 'month':
//        $sqlPeriod = 'time >= DATE(NOW()) - INTERVAL 1 MONTH';
//        break;
//    default:
//        $sqlPeriod = 'DATE(time) = CURDATE()';
//        break;
//}
//
// Query the database to get unique sensor_id.
//$sql = "SELECT DISTINCT sensor_id FROM sensor_data WHERE " . $sqlPeriod;
//$result = $db->query($sql);
//
//$sensors = [];
//while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
//    $sensors[] = $row['sensor_id'];
//}
//
//$sensorData = [];
//foreach ($sensors as $sensor) {
//    $sensorData[$sensor] = [];
//    foreach (['temperature', 'humidity', 'pressure', 'pm25', 'pm100'] as $param) {
//        if(period)
//            $sql1 = "SELECT " . $param . ",time FROM sensor_data WHERE sensor_id ='" . $sensor . "' AND " . $sqlPeriod . " ORDER BY time";
//        $results = $db->query($sql1);
//        $data = [["Time", $param]];
//        while ($row = $results->fetch(PDO::FETCH_ASSOC)) {
//            $data[] = [$row["time"], floatval($row[$param])];
//        }
//        $sensorData[$sensor][$param] = $data;
//    }
//}
//
//echo json_encode($sensorData);
//
//switch ($period) {
//    case 'day':
//        $sqlStart = "SELECT DATE(time) AS date, AVG(";
//        $sqlMid = ") AS avg_param FROM sensor_data WHERE sensor_id ='5f936a41321dc8001b1b1dbf' AND time >= DATE(NOW()) - INTERVAL 7 DAY GROUP BY DATE(time) ORDER BY DATE(time)";
//        $sqlEnd = "SELECT DATE(time) AS date, AVG(temperature) AS avg_temperature FROM sensor_data WHERE sensor_id ='5f936a41321dc8001b1b1dbf' AND time >= DATE(NOW()) - INTERVAL 7 DAY GROUP BY DATE(time) ORDER BY DATE(time)";
//        break;
//    case 'week':
//        $sqlPeriod = 'time >= DATE(NOW()) - INTERVAL 7 DAY';
//        break;
//    case 'month':
//        $sqlPeriod = 'time >= DATE(NOW()) - INTERVAL 1 MONTH';
//        break;
//    default:
//        $sqlPeriod = 'DATE(time) = CURDATE()';
//        break;
//}
//// Query the database to get unique sensor_id.
//$sql = "SELECT DISTINCT sensor_id FROM sensor_data WHERE " . $sqlPeriod;
//$result = $db->query($sql);
//
//$sensors = [];
//while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
//    $sensors[] = $row['sensor_id'];
//}
//
//$sensorData = [];
//foreach ($sensors as $sensor) {
//    $sensorData[$sensor] = [];
//    foreach (['temperature', 'humidity', 'pressure', 'pm25', 'pm100'] as $param) {
//        if(period)
//        $sql1 = $sqlStart . $param . $sqlMid . $sensor . "' AND " . $sqlPeriod . " ORDER BY time";
//        $results = $db->query($sql1);
//        $data = [["Time", $param]];
//        while ($row = $results->fetch(PDO::FETCH_ASSOC)) {
//            $data[] = [$row["time"], floatval($row[$param])];
//        }
//        $sensorData[$sensor][$param] = $data;
//    }
//}
//
//echo json_encode($sensorData);
//
$period = isset($_GET['period']) ? $_GET['period'] : 'day';

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

// Query the database to get unique sensor_id.
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
?>