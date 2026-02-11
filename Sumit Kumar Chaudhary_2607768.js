let iconDisplay = document.getElementById("icon");
let button = document.getElementById("searchBtn");
let searchBar = document.getElementById("searchBar");

// Search button click
button.addEventListener("click", function () {
  let value = searchBar.value.trim();
  if (value === "") {
    alert("Please enter a city name!");
    return;
  } else {
    fetchWeatherData(value);
  }
});

// Enter key in search bar
searchBar.addEventListener("keyup", function (e) {
  if (e.key === "Enter") {
    let value = searchBar.value.trim();
    if (value === "") {
      alert("Please enter a city name!");
      return;
    } else {
      fetchWeatherData(value);
    }
  }
});

// Fetch weather data
async function fetchWeatherData(city) {
  let data;
  try {
    if (navigator.onLine) {
      let url =
        "http://localhost:90/Prototype2/db_connection.php?city=" +
        encodeURIComponent(city);
      let res = await fetch(url);
      if (!res.ok) {
        alert("City not found");
        return;
      }

      data = await res.json();
      if (!data.error && data.current) {
        localStorage.setItem(city, JSON.stringify(data));
      } else {
        alert(data.error || "City not found");
        return;
      }
    } else {
      const cached = localStorage.getItem(city);
      if (!cached) {
        alert("No cached data available for " + city);
        return;
      }
      data = JSON.parse(cached);
    }

    let current = data.current;

    let timeZone = current.timezone;

    document.getElementById("time").textContent = formatTime(null, timeZone);

    document.getElementById("sunrise").textContent = formatTime(
      current.sunrise,
      timeZone,
    );
    document.getElementById("sunset").textContent = formatTime(
      current.sunset,
      timeZone,
    );

    let date = new Date((Number(current.dt) + Number(timeZone)) * 1000);
    document.getElementById("date").textContent = date.toLocaleDateString(
      "en-US",
      { month: "short", day: "2-digit", weekday: "long", timeZone: "UTC" },
    );

    document.getElementById("city").textContent = current.city;
    document.getElementById("country").textContent = ", " + current.country;
    document.getElementById("temperature").textContent =
      Math.round(current.temperature) + "°C";
    document.getElementById("feelsLike").textContent =
      "Feels like: " + Math.round(current.feels_like) + "°C";
    document.getElementById("humidity").textContent = current.humidity + "%";
    document.getElementById("windSpeed").textContent =
      current.wind_speed + " m/s";
    document.getElementById("windDirection").textContent =
      current.wind_direction + "°";
    document.getElementById("pressure").textContent = current.pressure + " hPa";

    document.getElementById("weatherCondition").textContent =
      current.weather_main;
    document.getElementById("descriptionWeather").textContent =
      current.weather_description;

    if (current.weather_icon) {
      iconDisplay.src =
        "https://openweathermap.org/img/wn/" + current.weather_icon + "@2x.png";
      iconDisplay.alt = current.weather_description;
    }
  } catch (error) {
    alert("Error: " + error.message);
  }
}

// Format time
function formatTime(timestamp, offset) {
  let timeValue;
  if (timestamp) {
    timeValue = timestamp * 1000;
  } else {
    timeValue = Date.now();
  }

  let cityTimeInMs = timeValue + offset * 1000;
  let cityTime = new Date(cityTimeInMs);

  return cityTime.toLocaleTimeString("en-US", {
    timeZone: "UTC",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: true,
  });
}

// Default city
fetchWeatherData("Selma");
