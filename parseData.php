<?php
require_once("config.php");

$sensorIds = ["5dfe7b0e5b8d95001aeb77ac", "5abffcd4850005001b7f02df", "5c86bed4922ca900190215df", "5cab2dcf3680f2001b0550bd", "5c4a283b35acab001902ef61", "5ec17920dbe1cf001c076eca", "5f8c095157e598001b4ef479"];
$apiKey = "AIzaSyArgBywlIsUtMoLJmia5jZckXkkv362HZU";
try {
    $db = new PDO("mysql:host=$hostname;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    throw new PDOException($e);
}

foreach ($sensorIds as $sensorId) {
    $url = "https://api.opensensemap.org/boxes/" . $sensorId;
    $sensorData = file_get_contents($url);
    $sensorData = json_decode($sensorData);

    $sensor_id = null;
    $time = null;
    $longitude = null;
    $latitude = null;
    $pressure = 0.0;
    $temperature = null;
    $humidity = null;
    $pm25 = null;
    $pm100 = null;
    $address = null;
    $city = null;
    $country = null;

    $sensor_id = $sensorData->_id;
    $time = $sensorData->lastMeasurementAt;
    $time = new DateTime($time);
    $time = $time->format('Y-m-d H:i:s');

    $longitude = $sensorData->currentLocation->coordinates[0];
    $latitude = $sensorData->currentLocation->coordinates[1];

    try {
        $googleMapUrl = "https://maps.googleapis.com/maps/api/geocode/json?latlng=" . $latitude . "," . $longitude . "&key={$apiKey}";
        $googleMapContent = file_get_contents($googleMapUrl);
        $locationData = json_decode($googleMapContent);
    } catch (Exception $e) {
        echo "Fetching location data failed: " . $e->getMessage();
        continue;
    }

    $returned = parseFormattedAddress($locationData->results[0]->formatted_address);
    $address = $returned["address"];
    $city = $returned["city"];
    $country = $returned["country"];

    foreach ($sensorData->sensors as $sensor) {
        switch ($sensor->title) {
            case 'PM10':
                $pm100 = $sensor->lastMeasurement->value;
                break;
            case 'PM2.5':
                $pm25 = $sensor->lastMeasurement->value;
                break;
            case 'Temperatur':
            case 'Temperature':
                $temperature = $sensor->lastMeasurement->value;
                break;
            case 'rel. Luftfeuchte':
            case 'Humidity':
                $humidity = $sensor->lastMeasurement->value;
                break;
            case 'Pressure':
            case 'Luftdruck':
                $pressure =$sensor->lastMeasurement->value;
                break;
        }
    }

    try {
        $stmt = $db->prepare("INSERT INTO sensor_data (sensor_id, time, longitude, latitude, temperature, humidity, pressure, pm25, pm100, address, city, country) VALUES (:sensor_id, :time, :longitude, :latitude, :temperature, :humidity, :pressure, :pm25, :pm100, :address, :city, :country)");
        $stmt->bindParam(':sensor_id', $sensor_id);
        $stmt->bindParam(':time', $time); //assuming $time equals $timestamp in your code
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
        'address' => trim($addressParts[0]),
        'city' => trim($addressParts[1]),
        'country' => trim($addressParts[2])
    ];
}
?>