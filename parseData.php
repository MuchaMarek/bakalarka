<?php
require_once("config.php");

$sensorIds = ["5f936a41321dc8001b1b1dbf", "60468798e94be2001c0e6a09", "5c8e6ac3922ca9001933bc84", "62015d432d6155001b0313c6",
    "5f9916b3321dc8001b711fe1","5e5028f718423d001c1b4a02", "621a190d4f7ef7001bdb6ed2", "578207d56fea661300861f3b",
    "63fc7839e331c50008a0d875", "62f00e78320cc4001bd6a967", "5e58a833100432001bf78af1", "5dfe7b0e5b8d95001aeb77ac",
    "6181a5508b4e03001bd81971", "5acd2e81223bd80019198632","5abffcd4850005001b7f02df","5c86bed4922ca900190215df",
    "5cab2dcf3680f2001b0550bd", "5ec17920dbe1cf001c076eca", "5f8c095157e598001b4ef479", "603bbc5eaf6fbf001b07a8e9"];
$apiKey = "AIzaSyArgBywlIsUtMoLJmia5jZckXkkv362HZU";

try {
    $db = new PDO("mysql:host=$hostname;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit();
}

foreach ($sensorIds as $sensorId) {
    $url = "https://api.opensensemap.org/boxes/" . $sensorId;
    $sensorDataJson = @file_get_contents($url);

    if ($sensorDataJson === false) {
        echo "Unable to fetch data for sensor $sensorId";
        continue;
    }

    $sensorData = json_decode($sensorDataJson);

    if (!isset($sensorData->_id, $sensorData->lastMeasurementAt, $sensorData->currentLocation->coordinates)) {
        echo "Incomplete data for sensor $sensorId";
        continue;
    }

    $sensor_id = $sensorData->_id;
    $time = new DateTime();
    $time = $time->format('Y-m-d H:i:s');

    $longitude = $sensorData->currentLocation->coordinates[0];
    $latitude = $sensorData->currentLocation->coordinates[1];

    try {
        $googleMapUrl = "https://maps.googleapis.com/maps/api/geocode/json?latlng=" . $latitude . "," . $longitude . "&key={$apiKey}";
        $googleMapContent = @file_get_contents($googleMapUrl);

        if ($googleMapContent === false) {
            echo "Unable to fetch location data for sensor $sensorId";
            continue;
        }

        $locationData = json_decode($googleMapContent);
        if (!isset($locationData->results[0]->formatted_address)) {
            echo "Unable to parse location data for sensor $sensorId";
            continue;
        }
        $returned = parseFormattedAddress($locationData->results[0]->formatted_address);
        $address = isset($returned["address"]) ? $returned["address"] : '';
        $city = isset($returned["city"]) ? $returned["city"] : '';
        $country = isset($returned["country"]) ? $returned["country"] : '';
    } catch (Exception $e) {
        echo "Fetching location data failed: " . $e->getMessage();
        continue;
    }

    $pressure = 0.0;
    $temperature = 0.0;
    $humidity = 0.0;
    $pm25 = 0.0;
    $pm100 = 0.0;

    foreach ($sensorData->sensors as $sensor) {
        switch ($sensor->title) {
            case 'PM10':
                if ($sensor->lastMeasurement->createdAt > date('Y-m-d H:i:s', strtotime('-1 hour'))) {
                    $pm100 = $sensor->lastMeasurement->value;
                }
                break;
            case 'PM2.5':
                if ($sensor->lastMeasurement->createdAt > date('Y-m-d H:i:s', strtotime('-1 hour'))) {
                    $pm25 = $sensor->lastMeasurement->value;
                }
                break;
            case 'Temperatur':
            case 'Temperature':
                if ($sensor->lastMeasurement->createdAt > date('Y-m-d H:i:s', strtotime('-1 hour'))) {
                    $temperature = $sensor->lastMeasurement->value;
                }
                break;
            case 'rel. Luftfeuchte':
            case 'Humidity':
                if ($sensor->lastMeasurement->createdAt > date('Y-m-d H:i:s', strtotime('-1 hour'))) {
                    $humidity = $sensor->lastMeasurement->value;
                }
                break;
            case 'Pressure':
            case 'Luftdruck':
                if ($sensor->lastMeasurement->createdAt > date('Y-m-d H:i:s', strtotime('-1 hour'))) {
                    $pressure = $sensor->lastMeasurement->value;
                }
                break;
        }
    }

    try {
        $stmt = $db->prepare("INSERT INTO sensor_data (sensor_id, time, longitude, latitude, temperature, humidity, pressure, pm25, pm100, address, city, country) VALUES (:sensor_id, :time, :longitude, :latitude, :temperature, :humidity, :pressure, :pm25, :pm100, :address, :city, :country)");
        $stmt->bindParam(':sensor_id', $sensor_id);
        $stmt->bindParam(':time', $time);
        $stmt->bindParam(':longitude', $longitude);
        $stmt->bindParam(':latitude', $latitude);
        $stmt->bindParam(':pressure', $pressure);
        $stmt->bindParam(':temperature', $temperature);
        $stmt->bindParam(':humidity', $humidity);
        $stmt->bindParam(':pm25', $pm25);
        $stmt->bindParam(':pm100', $pm100);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':city', $city);
        $stmt->bindParam(':country', $country);
        $stmt->execute();
    } catch (PDOException $e) {
        echo "Data insertion failed: " . $e->getMessage();
        continue;
    }
}


function parseFormattedAddress($formattedAddress)
{
    $addressWithoutNumbers = preg_replace('/\d/', '', $formattedAddress);
    $cleanString = preg_replace('/[^a-zA-Z0-9, áäčďéíĺľňóôŕšťúýžÁÄČĎÉÍĹĽŇÓÔŔŠŤÚÝŽ-]/u', '', $addressWithoutNumbers);
    $addressParts = explode(',', $cleanString);

    return [
        'address' => isset($addressParts[0]) ? trim($addressParts[0]) : '',
        'city' => isset($addressParts[1]) ? trim($addressParts[1]) : '',
        'country' => isset($addressParts[2]) ? trim($addressParts[2]) : ''
    ];
}


?>