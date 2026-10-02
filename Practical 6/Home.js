
const API_KEY = "7fb64c96c91f0a1f98cf80297399f990";

const citySelect = document.getElementById("citySelect");
const weatherData = document.getElementById("weatherData");

async function getWeather(city) {

    weatherData.innerHTML = "🌤️ Loading weather...";

    const url =
        "https://api.openweathermap.org/data/2.5/weather" +
        "?q=" + encodeURIComponent(city) +
        "&units=metric" +
        "&appid=" + API_KEY;

    try {

        const response = await fetch(url);
        const data = await response.json();

        console.log("Weather API response:", data);

        if (!response.ok) {
            throw new Error(data.message || "API request failed");
        }

        weatherData.innerHTML =
            "🌤️ " + data.name +
            " | " + data.main.temp.toFixed(1) + "°C" +
            " | " + data.weather[0].description +
            " | 💧 " + data.main.humidity + "%" +
            " | 💨 " + data.wind.speed.toFixed(2) + " m/s";

    } catch (error) {

        console.error("Weather error:", error);

        weatherData.innerHTML =
            "❌ " + error.message;
    }
}

citySelect.addEventListener("change", function() {
    getWeather(this.value);
});

getWeather(citySelect.value);
