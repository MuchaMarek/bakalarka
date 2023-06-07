<?php
require_once("config.php");
session_start();
try {
    $db = new PDO("mysql:host=$hostname;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

$sql = "SELECT DISTINCT sensor_id FROM sensor_data";
$result = $db->query($sql);

while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $sensors[] = $row['sensor_id'];
}

$desiredParams = [];
$comparedSensors = [];
$addressList = [];

foreach ($sensors as $sensor) {
    $sql = "SELECT city, address, country FROM sensor_data WHERE sensor_id = :sensor_id LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute(['sensor_id' => $sensor]);
    $address = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($address) {
        $addressList[] = $address["city"] . " " . $address["address"] . ", " . $address["country"];
    }
}
?>

<html lang="sk">
<head>
    <title>MuchaData</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="stylesheet" type="text/css" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script>
        google.charts.load('current', {'packages': ['corechart']});
        let spanCheck = false;

        function drawCharts() {
            var sensorData = {};
            <?php foreach ($sensors as $sensor): ?>
            sensorData['<?php echo $sensor; ?>'] = {};
            <?php endforeach; ?>

            var period = document.querySelector('input[name="period"]:checked').value;
            var startDate = document.getElementById('start-date').value;
            var endDate = document.getElementById('end-date').value;


            if (period === "fromUntil" && ((startDate === "" || endDate === "") || (startDate > endDate))) {
                document.getElementById('day').checked = false;
                document.getElementById('week').checked = false;
                document.getElementById('month').checked = false;
                document.getElementById('fromUntil').checked = false;
                document.getElementById('start-date').value = "";
                document.getElementById('end-date').value = "";
                startDate = "";
                endDate = "";
                return;
            }

            fetch('getSensorData.php?period=' + period + '&start-date=' + startDate + '&end-date=' + endDate)
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


                    if (period === 'day') {
                        for (let i = 1; i < dataArray.length; i++) {
                            dataArray[i][0] = parseInt(dataArray[i][0]);    // hour
                        }
                    } else if (period === 'week' || period === 'month' || period === 'fromUntil') {
                        for (let i = 1; i < dataArray.length; i++) {
                            let dateParts = dataArray[i][0].split('-');
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
                .catch(function (error) {
                    console.log("Fetch error: " + error.message);
                });
        }


        function drawChart(dataArray, chartTitle, elementId) {
            let data = google.visualization.arrayToDataTable(dataArray);
            let period = document.querySelector('input[name="period"]:checked').value;
            let startDateInput = document.getElementById('start-date').value;
            let endDateInput = document.getElementById('end-date').value;
            let chart = new google.visualization.LineChart(document.getElementById(elementId));
            let todayDate = new Date();
            let options;
            let startDate;
            let endDate;
            let ticks = [];
            let date;

            if (period === "fromUntil") {
                startDate = new Date(startDateInput);
                endDate = new Date(endDateInput);
                let dateSpan = (endDate.getTime() - startDate.getTime()) / ((1000 * 3600 * 24));

                for (let i = 0; i < dateSpan; i += 4) {
                    date = new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate() + i);
                    ticks.push(date);
                }

                options = {
                    title: chartTitle,
                    curveType: 'function',
                    legend: {position: 'bottom'},
                    hAxis: {
                        title: 'Date',
                        format: 'dd.MM',
                        ticks: ticks
                    },
                    vAxis: {
                        title: chartTitle
                    }
                };
            } else if (period === "day") {
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
                startDate = new Date(todayDate.getFullYear(), todayDate.getMonth(), todayDate.getDate() - 7);

                for (let i = 0; i < 7; i++) {
                    date = new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate() + i);
                    ticks.push(date);
                }

                options = {
                    title: chartTitle,
                    curveType: 'function',
                    legend: {position: 'bottom'},
                    hAxis: {
                        title: 'Date',
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

                for (let i = 0; i < maxDaysInMonth; i += 4) {
                    date = new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate() + i);
                    ticks.push(date);
                }

                options = {
                    title: chartTitle,
                    curveType: 'function',
                    legend: {position: 'bottom'},
                    hAxis: {
                        title: 'Date',
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

        $(document).ready(function () {
            google.charts.load('current', {'packages': ['corechart']});
            google.charts.setOnLoadCallback(drawCharts);

            $('#sensor-data-form').submit(function (e) {
                e.preventDefault();
                document.getElementById('day').checked = false;
                document.getElementById('week').checked = false;
                document.getElementById('month').checked = false;
                document.getElementById('fromUntil').checked = true;
                drawCharts();
            });


            $('#clearBtn').click(function (e) {
                e.preventDefault();
                document.getElementById('start-date').value = "";
                document.getElementById('end-date').value = "";
                document.getElementById('day').checked = true;
                document.getElementById('week').checked = false;
                document.getElementById('month').checked = false;
                document.getElementById('fromUntil').checked = false;
                drawCharts();
            });


            $('#compareBtn').click(function (e) {
                e.preventDefault();
                $('.sensor-row').hide();
                document.getElementById("compareShow1").style.background = "green";
                document.getElementById("compareShow2").style.background = "green";
                drawCharts();
            });


            $('#clearCompareBtn').click(function (e) {
                e.preventDefault();
                $('.sensor-row').show();
                drawCharts();
            });

            $('#temp').click(function (e) {
                e.preventDefault();
                <?php
                $key = array_search("temperature", $desiredParams);
                if ($key !== false) {
                    unset($desiredParams[$key]);
                } else {
                    $desiredParams[] = "temperature";
                }
                ?>
                drawCharts();
            });

            $('#pres').click(function (e) {
                e.preventDefault();
                <?php
                $key = array_search("pm25", $desiredParams);
                if ($key !== false) {
                    unset($desiredParams[$key]);
                } else {
                    $desiredParams[] = "pm25";
                }
                ?>
                drawCharts();
            });

            $('#pm25s').click(function (e) {
                e.preventDefault();
                <?php
                $key = array_search("pm25", $desiredParams);
                if ($key !== false) {
                    unset($desiredParams[$key]);
                } else {
                    $desiredParams[] = "pm25";
                }
                ?>
                drawCharts();
            });

            $('#pm100s').click(function (e) {
                e.preventDefault();
                <?php
                $key = array_search("pm100", $desiredParams);
                if ($key !== false) {
                    unset($desiredParams[$key]);
                } else {
                    $desiredParams[] = "pm100";
                }
                ?>
                drawCharts();
            });

            $('#humi').click(function (e) {
                e.preventDefault();
                <?php
                $key = array_search("humidity", $desiredParams);
                if ($key !== false) {
                    unset($desiredParams[$key]);
                } else {
                    $desiredParams[] = "humidity";
                }
                ?>
                drawCharts();
            });

            $("#addBtn").click(function() {

            });
        });
    </script>
</head>

<body>
    <div id="menu" style="display: flex">
        <div id="menuMain" style="flex: 1">
            <input type="radio" id="day" name="period" value="day" checked onclick="drawCharts()">
            <label for="day">Day</label>

            <input type="radio" id="week" name="period" value="week" onclick="drawCharts()">
            <label for="week">Week</label>

            <input type="radio" id="month" name="period" value="month" onclick="drawCharts()">
            <label for="month">Month</label>

            <input type="radio" id="fromUntil" name="period" value="fromUntil" onclick="drawCharts()">
            <label for="month">From-Until</label>
        </div>

        <div style="flex: 1;">
            <canvas id="compareShow1" style="width: 15px; height: 15px; background: red"></canvas>
            <button id="compareBtn">Compare Mode</button>
            <canvas id="compareShow2" style="width: 15px; height: 15px; background: red"></canvas>
            <br>
            <button>Turn off compare mode</button>
            <div id="compareDiv">
                <input type="checkbox" id="temp" name="temp" value="Temperature">
                <label for="vehicle1">Temperature</label><br>
                <input type="checkbox" id="pres" name="pres" value="Pressure">
                <label for="vehicle2">Pressure</label><br>
                <input type="checkbox" id="pm25s" name="pm25s" value="PM2.5">
                <label for="vehicle3">PM2.5</label><br>
                <input type="checkbox" id="pm100s" name="pm100s" value="PM10">
                <label for="vehicle1">PM10</label><br>
                <input type="checkbox" id="humi" name="humi" value="Humidity">
                <label for="vehicle2">Humidity</label><br>
            </div>
        </div>

        <div id="rangeDiv">
            <form id="sensor-data-form" onsubmit="event.preventDefault()";/>
            <label for="start-date">From date:</label>
            <input type="date" id="start-date" name="end-date">
            <br>
            <label for="end-date">Until date:</label>
            <input type="date" id="end-date" name="end-date">
            <br>
            <input type="submit" value="Submit">
            <button id="clearBtn">Clear</button>
            </form>
        </div>

    </div>

<?php foreach ($sensors as $sensor):?>
    <div class="sensor-row" style="outline: 2px solid royalblue; padding-top: 30px;padding-bottom: 20px;">
        <h3 style="font-size: x-large; display: flex; justify-content: center"><?php $sql = "SELECT city, address, country FROM sensor_data WHERE sensor_id='" . $sensor . "' LIMIT 1";
            $address = $db->query($sql);
            $address = $address->fetch(PDO::FETCH_ASSOC);
            echo $address["city"] . " " . $address["address"] . ", " . $address["country"] ?></h3>
        <?php foreach (['temperature', 'humidity', 'pressure', 'pm25', 'pm100'] as $param): ?>
            <div class="chartDiv" id="chart_<?php echo $sensor . "_" . $param; ?>" style="z-index: 3; border-radius: 25px; height: 35%; padding: 1px; margin-bottom: 60px; background: #ffffff; box-shadow: 0 0 40px rgb(59,162,246);"></div>
        <?php endforeach;?>
    </div>
    <div style="clear: both;"></div>
<?php endforeach; ?>
</body>
</html>
