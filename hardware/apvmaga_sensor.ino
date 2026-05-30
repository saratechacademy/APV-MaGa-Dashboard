// ============================================================
//  APV-MaGa Sensor Client
//  Compatible : ESP32, ESP8266, Arduino + Ethernet Shield
//  Envoi des données vers l'API APV-MaGa
// ============================================================

#include <WiFi.h>           // ESP32
// #include <ESP8266WiFi.h> // Décommenter si ESP8266
#include <HTTPClient.h>
#include <ArduinoJson.h>    // Installer via Library Manager

// ------------------------------------------------------------
//  CONFIGURATION — À MODIFIER
// ------------------------------------------------------------

// WiFi
const char* WIFI_SSID     = "VotreSSID";
const char* WIFI_PASSWORD = "VotreMotDePasse";

// Site APV-MaGa — choisir UN site
// --- Site Fass ---
const char* SITE_SLUG = "fass-6a15122aee52e";
const char* API_KEY   = "apv-6AJgzjBQKLij27vznGOCPaf2ASoEp9mp";

// --- Site Muje Gona (décommenter pour utiliser) ---
// const char* SITE_SLUG = "muje-gona-6a1510d78bfc1";
// const char* API_KEY   = "apv-1CIzj7Ei7GrkbEbuIyQxNaDExjikZmAL";

// Serveur API
const char* API_BASE = "https://apvmaga.saratechniger.com/api/sensors";

// Intervalle d'envoi (millisecondes) — 10 secondes par défaut
const unsigned long SEND_INTERVAL = 10000;

// Catégories à envoyer — mettre true/false selon le site
const bool HAS_SOLAR      = true;
const bool HAS_WEATHER    = true;
const bool HAS_WATER      = false; // true pour Muje Gona
const bool HAS_IRRIGATION = false; // true pour Muje Gona

// ------------------------------------------------------------
//  FONCTIONS UTILITAIRES
// ------------------------------------------------------------

// Génère un float aléatoire entre min et max
float randFloat(float minVal, float maxVal) {
  return minVal + (float)random(0, 1000) / 1000.0 * (maxVal - minVal);
}

// Génère un int aléatoire entre min et max
int randInt(int minVal, int maxVal) {
  return random(minVal, maxVal + 1);
}

// Envoie une requête POST à l'API
bool sendData(const char* category, String jsonBody) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("  [ERREUR] WiFi non connecté");
    return false;
  }

  HTTPClient http;
  String url = String(API_BASE) + "/" + SITE_SLUG + "/" + category;

  http.begin(url);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-API-Key", API_KEY);

  int httpCode = http.POST(jsonBody);

  if (httpCode == 200 || httpCode == 201) {
    Serial.printf("  [OK] %s → %d\n", category, httpCode);
    http.end();
    return true;
  } else {
    Serial.printf("  [ERREUR] %s → %d\n", category, httpCode);
    Serial.println("  " + http.getString());
    http.end();
    return false;
  }
}

// ------------------------------------------------------------
//  ENVOI DES DONNÉES PAR CATÉGORIE
// ------------------------------------------------------------

void sendSolar() {
  StaticJsonDocument<256> doc;
  doc["solar_output"]      = roundf(randFloat(0.5, 6.0) * 10) / 10;
  doc["solar_irradiance"]  = randInt(200, 1100);
  doc["panel_temperature"] = roundf(randFloat(25.0, 65.0) * 10) / 10;
  doc["system_efficiency"] = roundf(randFloat(70.0, 95.0) * 10) / 10;

  String body;
  serializeJson(doc, body);
  sendData("solar", body);
}

void sendWeather() {
  StaticJsonDocument<256> doc;
  doc["temperature"]    = roundf(randFloat(18.0, 45.0) * 10) / 10;
  doc["humidity"]       = randInt(20, 95);
  doc["air_pressure"]   = randInt(990, 1025);
  doc["wind_speed"]     = roundf(randFloat(0.0, 30.0) * 10) / 10;
  doc["wind_direction"] = randInt(0, 359);
  doc["wx_irradiance"]  = randInt(100, 1100);

  String body;
  serializeJson(doc, body);
  sendData("weather", body);
}

void sendWater() {
  StaticJsonDocument<128> doc;
  doc["borehole_level"]  = roundf(randFloat(1.0, 15.0) * 10) / 10;
  doc["tank_fill_level"] = randInt(10, 100);

  String body;
  serializeJson(doc, body);
  sendData("water", body);
}

void sendIrrigation() {
  StaticJsonDocument<256> doc;
  doc["flow_rate"]       = roundf(randFloat(0.0, 5.0) * 10) / 10;
  doc["zone_a_moisture"] = randInt(20, 95);
  doc["zone_b_moisture"] = randInt(20, 95);
  doc["zone_c_moisture"] = randInt(20, 95);
  doc["valve_1"]         = randInt(0, 1);
  doc["valve_2"]         = randInt(0, 1);
  doc["valve_3"]         = randInt(0, 1);

  String body;
  serializeJson(doc, body);
  sendData("irrigation", body);
}

// ------------------------------------------------------------
//  CONNEXION WIFI
// ------------------------------------------------------------

void connectWiFi() {
  Serial.print("Connexion WiFi");
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500);
    Serial.print(".");
    attempts++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWiFi connecté !");
    Serial.print("IP : ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("\n[ERREUR] Connexion WiFi échouée. Redémarrage...");
    delay(3000);
    ESP.restart();
  }
}

// ------------------------------------------------------------
//  SETUP & LOOP
// ------------------------------------------------------------

unsigned long lastSend = 0;
int iteration = 0;

void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.println("\n========================================");
  Serial.println("  APV-MaGa Sensor Client");
  Serial.println("========================================");
  Serial.printf("  Site : %s\n", SITE_SLUG);
  Serial.printf("  Intervalle : %lu ms\n", SEND_INTERVAL);
  Serial.println("========================================\n");

  randomSeed(analogRead(0)); // Seed aléatoire depuis pin analogique
  connectWiFi();
}

void loop() {
  // Reconnexion automatique si WiFi perdu
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("[WiFi] Déconnecté, reconnexion...");
    connectWiFi();
  }

  unsigned long now = millis();

  if (now - lastSend >= SEND_INTERVAL) {
    lastSend = now;
    iteration++;

    Serial.printf("\n[%lu ms] Iteration #%d\n", now, iteration);
    Serial.println("  ----------------------------------------");

    if (HAS_SOLAR)      { sendSolar();      delay(200); }
    if (HAS_WEATHER)    { sendWeather();    delay(200); }
    if (HAS_WATER)      { sendWater();      delay(200); }
    if (HAS_IRRIGATION) { sendIrrigation(); delay(200); }

    Serial.printf("  Prochain envoi dans %lu s...\n", SEND_INTERVAL / 1000);
  }
}
