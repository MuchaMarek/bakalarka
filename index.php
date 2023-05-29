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

while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $sensors[] = $row['sensor_id'];
}
?>

<html lang="sk">
<head>
    <title>Pain</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script type="text/javascript">
        google.charts.load('current', {'packages': ['corechart']});

        function drawCharts() {
            var sensorData = {};
            <?php foreach ($sensors as $sensor): ?>
            sensorData['<?php echo $sensor; ?>'] = {};
            <?php endforeach; ?>

            var period = document.querySelector('input[name="period"]:checked').value;
            fetch('getSensorData.php?period=' + period)
                .then(response => {
                    if (!response.ok) {
                        throw new Error("HTTP error " + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    sensorData = data;
                <?php foreach ($sensors as $sensor): ?>
                    <?php foreach (['temperature', 'humidity', 'pressure', 'pm25', 'pm100'] as $param): ?>
                    var dataArray = sensorData['<?php echo $sensor; ?>']['<?php echo $param; ?>'];

                    // Adjust the parsing based on period
                    if (period === 'day') {
                        for (var i = 1; i < dataArray.length; i++) {
                            dataArray[i][0] = parseInt(dataArray[i][0]);    // hour
                        }
                    } else if (period === 'week' || period === 'month') {
                        for (var i = 1; i < dataArray.length; i++) {
                            var dateParts = dataArray[i][0].split('-');
                            dataArray[i][0] = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);
                        }
                    }

                    var unit = "";
                    switch ('<?php echo $param; ?>') {
                        case 'temperature':
                            unit = "C°";
                            break;
                        case 'humidity':
                            unit = "%";
                            break;
                        case 'pressure':
                            unit = "hPa";
                            break;
                        case 'pm25':
                        case 'pm100':
                            unit = "µg/m3";
                            break;
                    }

                    drawChart(dataArray, '<?php echo $param; ?> (' + unit + ')', 'chart_<?php echo $sensor . "_" . $param; ?>');
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                })
                .catch(function(error) {
                    console.log("Fetch error: " + error.message);
                });

        }

        function drawChart(dataArray, chartTitle, elementId) {
            var data = google.visualization.arrayToDataTable(dataArray);
            var period = document.querySelector('input[name="period"]:checked').value;
            var chart = new google.visualization.LineChart(document.getElementById(elementId));
            var todayDate = new Date();
            var options;
            var startDate;
            var ticks = [];
            var date;
            if (period === "day") {
                options = {
                    title: chartTitle,
                    curveType: 'function',
                    legend: {position: 'bottom'},
                    hAxis: {
                        title: 'Time',
                        ticks: [0, 4, 8, 12, 16, 20, 23]
                    },
                    vAxis: {
                        title: chartTitle
                    }
                };
            } else if (period === "week") {
                startDate = new Date(todayDate.getFullYear(), todayDate.getMonth(), todayDate.getDate() - 7); // Get the date 7 days before today
                for (var i = 0; i < 7; i++) {
                    date = new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate() + i);
                    ticks.push(date);
                }

                options = {
                    title: chartTitle,
                    curveType: 'function',
                    legend: {position: 'bottom'},
                    hAxis: {
                        title: 'Time',
                        format: 'dd.MM',
                        ticks: ticks
                    },
                    vAxis: {
                        title: chartTitle
                    }
                };
            } else if (period === "month") {
                startDate = new Date(todayDate.getFullYear(), todayDate.getMonth(), todayDate.getDate() - 30);
                var maxDaysInMonth = new Date(startDate.getFullYear(), startDate.getMonth() + 1, 0).getDate();
                for (var j = 0; j < maxDaysInMonth; j+=4) {
                    date = new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate() + j);
                    ticks.push(date);
                }

                options = {
                    title: chartTitle,
                    curveType: 'function',
                    legend: {position: 'bottom'},
                    hAxis: {
                        title: 'Time',
                        format: 'dd.MM',
                        ticks: ticks
                    },
                    vAxis: {
                        title: chartTitle
                    }
                };
            }
            chart.draw(data, options);
        }

        $(document).ready(function () {
            google.charts.setOnLoadCallback(drawCharts);
        });
    </script>
</head>

<body>
    <form id="sensor-data-form" onsubmit="return drawCharts()">
        <label for="start-date">Start Date:</label>
        <input type="date" id="start-date" name="start-date">

        <input type="radio" id="day" name="period" value="day" checked onclick="drawCharts()">
        <label for="day">Day</label>

        <input type="radio" id="week" name="period" value="week" onclick="drawCharts()">
        <label for="week">Week</label>

        <input type="radio" id="month" name="period" value="month" onclick="drawCharts()">
        <label for="month">Month</label>

        <input type="submit" value="Update Charts">
    </form>

    <?php foreach ($sensors as $sensor): ?>
        <div class="sensor-row">
            <h3><?php $sql = "SELECT city, address, country FROM sensor_data WHERE sensor_id='" . $sensor . "' LIMIT 1";
                $address = $db->query($sql);
                $address = $address->fetch(PDO::FETCH_ASSOC);
                echo $address["city"] . " " . $address["address"] . ", " . $address["country"] ?></h3>
            <?php foreach (['temperature', 'humidity', 'pressure', 'pm25', 'pm100'] as $param): ?>
                <div id="chart_<?php echo $sensor . "_" . $param; ?>"
                     style="width: 500px; height: 250px; float: left;"></div>
            <?php endforeach; ?>
        </div>
        <div style="clear: both;"></div>
    <?php endforeach; ?>
</body>
</html>
