<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// DB credentials
$serverName = "localhost";
$userName = "root";
$password = "";
$dbName = "weather_db";

// Connect
$conn = mysqli_connect($serverName, $userName, $password);
if (!$conn) {
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

// Create DB
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $dbName");
mysqli_select_db($conn, $dbName);

// Create table
$createTable = "CREATE TABLE IF NOT EXISTS weather_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city VARCHAR(100),
    country VARCHAR(10),
    temperature FLOAT,
    feels_like FLOAT,
    humidity INT,
    pressure INT,
    wind_speed FLOAT,
    wind_direction INT,
    weather_main VARCHAR(50),
    weather_description VARCHAR(100),
    weather_icon VARCHAR(10),
    sunrise INT,
    sunset INT,
    timezone INT,
    dt INT,
    weather_date DATE,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_city_date (city, weather_date)
)";
mysqli_query($conn, $createTable);

// API key
$apiKey = "YOUR API KEY";

// Get city
if (isset($_GET['city']) && !empty($_GET['city'])) {
    $cityName = mysqli_real_escape_string($conn, $_GET['city']);
} else {
    $cityName = "Selma";
}

$todayDate = date('Y-m-d');

// Check today data
$select = "SELECT * FROM weather_data WHERE city like '%$cityName%' AND weather_date='$todayDate'";
$result = mysqli_query($conn, $select);

$needsUpdate = true;

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $lastUpdated = strtotime($row['last_updated']);

    if ((time() - $lastUpdated) < 7200) {
        $needsUpdate = false;
        $weatherData = $row;
    }
}

// Fetch API if needed
if ($needsUpdate) {
    $url = "https://api.openweathermap.org/data/2.5/weather?q=" . urlencode($cityName) .
        "&appid=" . $apiKey . "&units=metric";

    $response = @file_get_contents($url);
    if ($response === false) {
    echo json_encode(["error" => "City not found"]);
    exit;
}
    $data = json_decode($response, true);


    $insert = "INSERT INTO weather_data
    (city, country, temperature, feels_like, humidity, pressure, wind_speed,
     wind_direction, weather_main, weather_description, weather_icon,
     sunrise, sunset, timezone, dt, weather_date)
    VALUES (
     '{$data['name']}','{$data['sys']['country']}',{$data['main']['temp']},
     {$data['main']['feels_like']},{$data['main']['humidity']},
     {$data['main']['pressure']},{$data['wind']['speed']},
     {$data['wind']['deg']},'{$data['weather'][0]['main']}',
     '{$data['weather'][0]['description']}','{$data['weather'][0]['icon']}',
     {$data['sys']['sunrise']},{$data['sys']['sunset']},
     {$data['timezone']},{$data['dt']},'$todayDate'
    )
    ON DUPLICATE KEY UPDATE
     temperature=VALUES(temperature),
     feels_like=VALUES(feels_like),
     humidity=VALUES(humidity),
     pressure=VALUES(pressure),
     wind_speed=VALUES(wind_speed),
     wind_direction=VALUES(wind_direction),
     weather_main=VALUES(weather_main),
     weather_description=VALUES(weather_description),
     weather_icon=VALUES(weather_icon),
     sunrise=VALUES(sunrise),
     sunset=VALUES(sunset),
     timezone=VALUES(timezone),
     dt=VALUES(dt)";

    mysqli_query($conn, $insert);
}

// Final fetch
$result = mysqli_query($conn, $select);
$current = mysqli_fetch_assoc($result);

echo json_encode(["current" => $current]);
mysqli_close($conn);
?>