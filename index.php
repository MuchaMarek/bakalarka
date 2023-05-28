<?php
require_once("config.php");

try {
    $db = new PDO("mysql:host=$hostname;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

// Query the database to get unique sensor_id.
$sql = "SELECT DISTINCT sensor_id FROM sensor_data";
$result = $db->query($sql);

$sensors = [];
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $sensors[] = $row['sensor_id'];
}

$sensorData = [];
foreach ($sensors as $sensor) {
    $sensorData[$sensor] = [];
    foreach (['temperature', 'humidity', 'pressure', 'pm25', 'pm100'] as $param) {
        $sql1 = "SELECT " . $param . ",time FROM sensor_data WHERE sensor_id ='" . $sensor . "' ORDER BY time";         // sem si pridal time, aby si mohol pridat do grafu aj cas kedy boli nahrane data
        $results = $db->query($sql1);
        $data = [["Sensor ID", $param]];
        while ($row = $results->fetch(PDO::FETCH_ASSOC)) {
            $data[] = [$sensor, $row[$param]];
        }
        $sensorData[$sensor][$param] = $data;
    }
}
?>

<html lang="sk">
<head>
    <!--Load the AJAX API-->
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load('current', {'packages': ['corechart']});
        google.charts.setOnLoadCallback(drawCharts);

        function drawCharts() {
            <?php foreach ($sensors as $sensor): ?>
            <?php foreach (['temperature', 'humidity', 'pressure', 'pm25', 'pm100'] as $param): ?>
            drawChart(<?php echo json_encode($sensorData[$sensor][$param]); ?>, '<?php echo $param; ?> Chart', 'chart_<?php echo $sensor . "_" . $param; ?>');
            <?php endforeach; ?>
            <?php endforeach; ?>
        }

        function drawChart(dataArray, chartTitle, elementId) {
            var data = google.visualization.arrayToDataTable(dataArray);

            var options = {
                title: chartTitle,
                curveType: 'function',
                legend: {position: 'bottom'}
            };

            var chart = new google.visualization.LineChart(document.getElementById(elementId));
            chart.draw(data, options);
        }
    </script>
</head>
<body>
<container id="graphContainer">
    <?php foreach ($sensors as $sensor): ?>
        <div class="sensor-row">
            <h3><?php $sql = "SELECT city, address FROM sensor_data WHERE sensor_id='" . $sensor . "' LIMIT 1";
                $address = $db->query($sql);
                $address = $address->fetch(PDO::FETCH_ASSOC);
                echo $address["city"] . " " . $address["address"] ?></h3>
            <?php foreach (['temperature', 'humidity', 'pressure', 'pm25', 'pm100'] as $param): ?>
                <div id="chart_<?php echo $sensor . "_" . $param; ?>"
                     style="width: 250px; height: 250px; float: left;"></div>
            <?php endforeach; ?>
        </div>
            <div style="clear: both;"></div>
    <?php endforeach; ?>
</container>
</body>
</html>
