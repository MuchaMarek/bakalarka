<?php
$sensorIds = [54376];
//54565
//google maps api key
$apiKey = "AIzaSyArgBywlIsUtMoLJmia5jZckXkkv362HZU";


// ruzova dolina
//$url = "https://data.sensor.community/airrohr/v1/sensor/12308/";
// cerveny most
//$url = "https://data.sensor.community/airrohr/v1/sensor/14876/";
// narcisova
//$url = "https://data.sensor.community/airrohr/v1/sensor/62610/";
// bardonovo
//$url = "https://data.sensor.community/airrohr/v1/sensor/24259/";
// partizan
//$url = "https://data.sensor.community/airrohr/v1/sensor/42196/";



foreach ($sensorIds as $sensorId) {
    $url = "https://data.sensor.community/airrohr/v1/sensor/" . $sensorId . "/";
    echo $sensorId . "<br>";
    $sensorData = file_get_contents($url);
    $sensorData = json_decode($sensorData);

    $googleMapUrl = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$sensorData[0]->location->latitude},{$sensorData[0]->location->longitude}&key={$apiKey}";
    $googleMapContent = file_get_contents($googleMapUrl);
    $locationData = json_decode($googleMapContent);

    $timestamp = null;
    $longitude = null;
    $latitude = null;
    $pressure  = null;
    $temperature = null;
    $humidity = null;
    $pm25 = null;
    $pm100 = null;
    $address = null;
    $city = null;
    $country = null;

    $sensorsTypes = getSensorTypes($sensorId, $sensorData[0]->location->latitude, $sensorData[0]->location->longitude);
    foreach ($sensorsTypes as $sensorType) {
        echo "<br>" . $sensorType["type"]. "<br>";
        $url = "https://data.sensor.community/airrohr/v1/sensor/" . $sensorType["id"] . "/";
        $sensorData = file_get_contents($url);
        $sensorData = json_decode($sensorData);

        if ($sensorType["type"] === "SDS011") {
            $location = parseFormattedAddress($locationData->results[0]->formatted_address);
            $address=$location["address"];
            $city=$location["city"];
            $country=$location["country"];
            $timestamp = $sensorData[0]->timestamp;
            $latitude = $sensorData[0]->location->latitude;
            $longitude = $sensorData[0]->location->longitude;
            $pm25 = $sensorData[0]->sensordatavalues[1]->value;
            $pm100 = $sensorData[0]->sensordatavalues[0]->value;
        } else if ($sensorType["type"] === "DHT22") {
            $temperature = $sensorData[0]->sensordatavalues[0]->value;
            $humidity = $sensorData[0]->sensordatavalues[1]->value;
        } else if ($sensorType["type"] === "BMP280"){
            var_dump($sensorData);
            $temperature = $sensorData[0]->sensordatavalues[1]->value;
            $pressure = number_format($sensorData[0]->sensordatavalues[2]->value / 100, 1);
            $longitude = $sensorData[0]->location->longitude;
            $latitude = $sensorData[0]->location->latitude;
        }else if($sensorType["type"] === "BME280"){
            $temperature = $sensorData[0]->sensordatavalues[0]->value;
            $humidity = $sensorData[0]->sensordatavalues[2]->value;
            $pressure = number_format($sensorData[0]->sensordatavalues[3]->value/ 100, 1);
        }
    }


    echo "<br><br>";
    echo "id: ". $sensorId . "<br>";
    echo "time: ". $timestamp . "<br>";
    echo "lon: ". $longitude . "<br>";
    echo "lat: ". $latitude . "<br>";
    echo "pres: ". $pressure . "<br>";
    echo "temp: ". $temperature . "<br>";
    echo "humi: " . $humidity .  "<br>";
    echo "pm2.5: ". $pm25 . "<br>";
    echo "pm10: ". $pm100 . "<br>";
    echo "addr: ". $address . "<br>";
    echo "city: ". $city . "<br>";
    echo "cntry: ". $country . "<br><br><br><br><br>";
}




function getSensorTypes($sensorId, $latitude, $longitude)
{
    $baseUrl = "https://data.sensor.community/airrohr/v1/sensor/";
    $types = [];
    for ($i = -2; $i <= 2; $i++) {
        $url = $baseUrl . ($sensorId + $i) . "/";
        $response = file_get_contents($url);

        if (!empty($response) && strlen($response) !== 0) {
            $decodedResponse = json_decode($response);
            if (isset($decodedResponse[0]) && $decodedResponse[0]->location->longitude === $longitude && $decodedResponse[0]->location->latitude === $latitude) {
                $types[] = ['type' => getSensorType($decodedResponse), 'id' => $sensorId + $i];
            }
        }
    }
    return $types;
}


function getSensorType($jsonData)
{
    $sensorTypes = ['DHT22', 'SDS011', 'BMP280', 'BME280'];
    foreach ($jsonData as $item) {
        $sensorName = $item->sensor->sensor_type->name;

        if (in_array($sensorName, $sensorTypes)) {
            return $sensorName;
        }
    }
    return null;
}

function parseFormattedAddress($formattedAddress)
{
    $addressWithoutNumbers = preg_replace('/\d/', '', $formattedAddress);
    $cleanString = preg_replace('/[^a-zA-Z0-9, áäčďéíĺľňóôŕšťúýžÁÄČĎÉÍĹĽŇÓÔŔŠŤÚÝŽ-]/u', '', $addressWithoutNumbers);
    $addressParts = explode(',', $cleanString);

    return [
        'address' => trim($addressParts[0]),
        'city' => trim($addressParts[1]),
        'country' => trim($addressParts[2])
    ];
}

?>











































<!--<!DOCTYPE html>-->
<!--<html lang="en">-->
<!---->
<!--<head>-->
<!--    <meta charset="UTF-8">-->
<!--    <meta name="viewport" content="width=device-width, initial-scale=1.0">-->
<!--    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">-->
<!--    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">-->
<!---->
<!--    <title>Pain</title>-->
<!--</head>-->
<!---->
<!--<body>-->
<!--<title>Graphs</title>-->
<!--<style>-->
<!--    .container {-->
<!--        width: 100%;-->
<!--        padding: 20px;-->
<!--        box-sizing: border-box;-->
<!--        margin-bottom: 20px;-->
<!--        border: 1px solid #ccc;-->
<!--    }-->
<!--</style>-->
<!---->
<!--<div id="container1" class="container">-->

<!--</div>-->
<!--<div id="container2" class="container">-->

<!--</div>-->
<!--<div id="container3" class="container">-->


<!--</div>-->
<!--<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>-->
<!--</body>-->
<!---->
<!--</html>-->
