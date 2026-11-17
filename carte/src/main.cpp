// Firmware L.E.R.N.A. v2 : carte MKR WiFi 1010 sur MKR Connector Carrier.
// La carte ne décide de rien : elle envoie les appuis de boutons au site (via MQTT) et affiche ce que le site lui dit.
//   Reçoit :  lerna/alarme (ON/OFF)   lerna/etat (attente, jeu, alarme, victoire, defaite)   lerna/jauge (0 à 100)
//             lerna/msg (texte de la 1re ligne de l'écran pendant le jeu, vide = texte par défaut)
//   Envoie :  lerna/bouton/<nom> = 1  lerna/carte/etat = en-ligne / hors-ligne
#include <Arduino.h>
#include <Wire.h>
#include <WiFiNINA.h>
#include <PubSubClient.h>
#include <Servo.h>
#include <rgb_lcd.h>
#include <Grove_LED_Bar.h>

// Paramètres réseau : include/secrets.h (fichier local, jamais envoyé sur GitHub), voir secrets.h.example
#include "secrets.h"
const char* ssid        = WIFI_SSID;         // Wi-Fi 2.4 GHz obligatoire
const char* password    = WIFI_PASSWORD;
const char* mqtt_server = MQTT_SERVER_IP;    // IP du PC (ou du Raspberry Pi) qui fait tourner Mosquitto
#ifdef MQTT_SERVER_PORT
const uint16_t mqtt_port = MQTT_SERVER_PORT;
#else
const uint16_t mqtt_port = 1883;
#endif

// --- Branchements (prises de la carte d'extension) ---
const uint8_t PIN_LED_ROUGE = 2;    // prise D2
const uint8_t PIN_LED_VERTE = 3;    // prise D3
const uint8_t PIN_LED_BLEUE = 4;   // prise D4
const uint8_t PIN_BUZZER    = 5;    // prise D5 (buzzer passif : on joue des notes à la main, voir BUZZER_VOLUME)
const uint8_t PIN_SERVO     = A4;    // prise A4
#ifdef BARRE_FILS_INVERSES          // à ajouter dans build_flags si la barre reste éteinte
const uint8_t PIN_BARRE_CLK = A5, PIN_BARRE_DATA = A6;
#else
const uint8_t PIN_BARRE_CLK = A6, PIN_BARRE_DATA = A5;
#endif

const uint8_t NB_BOUTONS = 6;
const char*   NOM_BOUTON[NB_BOUTONS] = {"demarrage", "arret", "serveur1", "serveur2", "serveur3", "serveur4"};
const uint8_t PIN_BOUTON[NB_BOUTONS] = {0, 1, A0, A1, A2, A3};   // D0, D1, A0, A1, A2, A3
bool          reposBouton[NB_BOUTONS];
bool          etatBouton[NB_BOUTONS];
unsigned long tBouton[NB_BOUTONS];

/* Volume du buzzer (passif) : part de chaque période où la broche est à l'état haut, en %.
   50 = plein volume (comme tone()), 8 = discret. Pour changer sans toucher au code : build_flags = -DBUZZER_VOLUME=20 */
#ifndef BUZZER_VOLUME
#define BUZZER_VOLUME 8
#endif

const int SERVO_FERME = 0;     // degrés : coffre fermé
const int SERVO_OUVERT = 90;   // coffre ouvert (victoire)

enum Phase { ATTENTE, JEU, ALARME, VICTOIRE, DEFAITE };

WiFiClient   wifiClient;
PubSubClient mqtt(wifiClient);
rgb_lcd      lcd;
Servo        servo;
Grove_LED_Bar* barre = nullptr;   // créée dans setup() : son constructeur configure des broches

// --- État reçu du site ---
Phase phase = ATTENTE;
bool  alarme = false;
int   jauge = 0;
char  message[17] = "";   // lerna/msg

// --- Affichage ---
char          dernierL1[17] = "", dernierL2[17] = "";
uint8_t       dernierR = 0, dernierG = 0, dernierB = 0;
int           dernierNiveauBarre = -1;
bool          servoAttache = false;
unsigned long tServo = 0;
int           servoCible = SERVO_FERME, servoActuel = -1;
unsigned long tSirene = 0;
bool          sireneHaute = true;
unsigned long tWifi = 0, tMqtt = 0;

void ecran(const char* l1, const char* l2, uint8_t r, uint8_t g, uint8_t b) {
  char a[17], c[17];
  snprintf(a, sizeof(a), "%-16.16s", l1);
  snprintf(c, sizeof(c), "%-16.16s", l2);
  if (r != dernierR || g != dernierG || b != dernierB) { lcd.setRGB(r, g, b); dernierR = r; dernierG = g; dernierB = b; }
  if (strcmp(a, dernierL1)) { lcd.setCursor(0, 0); lcd.print(a); strcpy(dernierL1, a); }
  if (strcmp(c, dernierL2)) { lcd.setCursor(0, 1); lcd.print(c); strcpy(dernierL2, c); }
}

void afficherPhase() {
  char l2[17];
  switch (phase) {
    case ATTENTE:  ecran("L.E.R.N.A.", "En attente", 0, 90, 255); break;
    case JEU:
      snprintf(l2, sizeof(l2), "Fuite %d %%", jauge);
      {
        const char* l1 = message[0] ? message : "Mission en cours";
        if (jauge < 60)      ecran(l1, l2, 0, 200, 0);
        else if (jauge < 85) ecran(l1, l2, 255, 140, 0);
        else                 ecran(l1, l2, 255, 0, 0);
      }
      break;
    case ALARME:
      snprintf(l2, sizeof(l2), "Fuite %d %%", jauge);
      ecran("!! ALARME !!", l2, 255, 0, 0);
      break;
    case VICTOIRE: ecran("LERNA NEUTRALISE", "Victoire !", 0, 255, 60); break;
    case DEFAITE:  ecran("FUITE TOTALE", "Echec", 255, 0, 0); break;
  }
}

void afficherLeds() {
  digitalWrite(PIN_LED_BLEUE, phase == ATTENTE ? HIGH : LOW);
  digitalWrite(PIN_LED_VERTE, (phase == JEU || phase == VICTOIRE) ? HIGH : LOW);
  if (!alarme) digitalWrite(PIN_LED_ROUGE, LOW);   // en alarme, la LED rouge clignote avec la sirène
  int niveau = (jauge + 5) / 10;                   // 0 à 100 % -> 0 à 10 LED
  if (niveau != dernierNiveauBarre && barre) { barre->setLevel(niveau); dernierNiveauBarre = niveau; }
}

// --- Son : un signal carré à impulsions étroites, produit à la main (pas de tone(), donc pas de conflit avec le servo) ---
unsigned long sonPeriode = 0;     // microsecondes, 0 = silence
unsigned long sonImpulsion = 0;
unsigned long sonDebut = 0;

void sonJouer(unsigned int frequence) {
  if (frequence == 0) { sonPeriode = 0; digitalWrite(PIN_BUZZER, LOW); return; }
  sonPeriode = 1000000UL / frequence;
  sonImpulsion = sonPeriode * BUZZER_VOLUME / 100;
  sonDebut = micros();
}

void sonArreter() { sonJouer(0); }

/** À appeler le plus souvent possible : produit une impulsion à chaque période. */
void sonMaj() {
  if (sonPeriode == 0) return;
  unsigned long t = micros();
  if (t - sonDebut >= sonPeriode) {
    sonDebut = t;
    digitalWrite(PIN_BUZZER, HIGH);
    delayMicroseconds(sonImpulsion);
    digitalWrite(PIN_BUZZER, LOW);
  }
}

/** Une note de durée donnée (bloque pendant la note). */
void bip(unsigned int frequence, unsigned int ms) {
  sonJouer(frequence);
  unsigned long t0 = millis();
  while (millis() - t0 < ms) sonMaj();
  sonArreter();
}

// --- Servo : branché seulement le temps de bouger (évite qu'il tremble au repos) ---
void deplacerServo(int angle) { servoCible = angle; }

void gererServo() {
  if (servoCible != servoActuel) {
    servo.attach(PIN_SERVO);
    servo.write(servoCible);
    servoActuel = servoCible;
    servoAttache = true;
    tServo = millis();
  } else if (servoAttache && millis() - tServo > 1500) {
    servo.detach();
    servoAttache = false;
  }
}

bool sireneEnCours = false;

void sirene() {
  if (!alarme) {
    if (sireneEnCours) { sonArreter(); sireneEnCours = false; }
    return;
  }
  sireneEnCours = true;
  unsigned long t = millis();
  if (t - tSirene >= 200) {
    tSirene = t;
    sireneHaute = !sireneHaute;
    digitalWrite(PIN_LED_ROUGE, sireneHaute ? HIGH : LOW);
    sonJouer(sireneHaute ? 1200 : 800);
  }
}

void jingleVictoire() {
  const int notes[] = {659, 784, 988, 1319};
  for (int n : notes) { bip(n, 130); delay(30); }
}

void changerPhase(Phase nouvelle) {
  if (nouvelle == phase) return;
  Phase ancienne = phase;
  phase = nouvelle;
  Serial.print("Phase -> "); Serial.println((int) phase);
  if (phase == VICTOIRE) { sonArreter(); jingleVictoire(); deplacerServo(SERVO_OUVERT); }
  else if (ancienne == VICTOIRE) deplacerServo(SERVO_FERME);
}

void onMessage(char* topic, byte* payload, unsigned int len) {
  char msg[16];
  if (len > sizeof(msg) - 1) len = sizeof(msg) - 1;
  memcpy(msg, payload, len);
  msg[len] = 0;
  Serial.print("MQTT "); Serial.print(topic); Serial.print(" = "); Serial.println(msg);

  if (!strcmp(topic, "lerna/alarme")) {
    alarme = !strcmp(msg, "ON");
  } else if (!strcmp(topic, "lerna/jauge")) {
    jauge = constrain(atoi(msg), 0, 100);
  } else if (!strcmp(topic, "lerna/msg")) {
    strncpy(message, msg, sizeof(message) - 1);
    message[sizeof(message) - 1] = 0;
  } else if (!strcmp(topic, "lerna/coffre")) {
    if (!strcmp(msg, "OUVERT")) deplacerServo(SERVO_OUVERT);
    else if (!strcmp(msg, "FERME")) deplacerServo(SERVO_FERME);
  } else if (!strcmp(topic, "lerna/etat")) {
    if      (!strcmp(msg, "attente"))  changerPhase(ATTENTE);
    else if (!strcmp(msg, "jeu"))      changerPhase(JEU);
    else if (!strcmp(msg, "alarme"))   changerPhase(ALARME);
    else if (!strcmp(msg, "victoire")) changerPhase(VICTOIRE);
    else if (!strcmp(msg, "defaite"))  changerPhase(DEFAITE);
  }
  afficherPhase();
  afficherLeds();
}

void maintenirReseau() {
  if (WiFi.status() != WL_CONNECTED) {
    if (tWifi == 0 || millis() - tWifi > 8000) {
      tWifi = millis();
      ecran("Connexion Wi-Fi", ssid, 255, 160, 0);
      Serial.print("Wi-Fi : "); Serial.println(ssid);
      WiFi.begin(ssid, password);   // bloque jusqu'à 10 s environ
      if (WiFi.status() == WL_CONNECTED) { Serial.print("IP : "); Serial.println(WiFi.localIP()); }
    }
    return;
  }
  if (!mqtt.connected() && (tMqtt == 0 || millis() - tMqtt > 3000)) {
    tMqtt = millis();
    ecran("Connexion MQTT", mqtt_server, 255, 160, 0);
    if (mqtt.connect("lerna-carte", nullptr, nullptr, "lerna/carte/etat", 0, true, "hors-ligne")) {
      Serial.println("MQTT connecte");
      mqtt.publish("lerna/carte/etat", "en-ligne", true);
      mqtt.subscribe("lerna/alarme");
      mqtt.subscribe("lerna/etat");
      mqtt.subscribe("lerna/jauge");
      mqtt.subscribe("lerna/msg");
      mqtt.subscribe("lerna/coffre");
      dernierL1[0] = dernierL2[0] = 0;   // on force le réaffichage
      afficherPhase();
    } else {
      Serial.print("MQTT echec, rc="); Serial.println(mqtt.state());
    }
  }
}

void lireBoutons() {
  for (uint8_t i = 0; i < NB_BOUTONS; i++) {
    bool actif = (digitalRead(PIN_BOUTON[i]) != reposBouton[i]);
    if (actif != etatBouton[i] && millis() - tBouton[i] > 30) {
      etatBouton[i] = actif;
      tBouton[i] = millis();
      if (!actif) continue;
      Serial.print("BOUTON "); Serial.println(NOM_BOUTON[i]);
      if (mqtt.connected()) {
        char topic[32];
        snprintf(topic, sizeof(topic), "lerna/bouton/%s", NOM_BOUTON[i]);
        mqtt.publish(topic, "1");
      }
      if (!alarme) bip(1500, 25);   // petit clic de confirmation
    }
  }
}

void setup() {
  Serial.begin(115200);
  unsigned long t = millis();
  while (!Serial && millis() - t < 3000);   // sans ordinateur branché, on démarre quand même

  pinMode(PIN_LED_ROUGE, OUTPUT); pinMode(PIN_LED_VERTE, OUTPUT); pinMode(PIN_LED_BLEUE, OUTPUT);
  pinMode(PIN_BUZZER, OUTPUT);
  digitalWrite(PIN_LED_ROUGE, LOW); digitalWrite(PIN_LED_VERTE, LOW); digitalWrite(PIN_LED_BLEUE, LOW);

  lcd.begin(16, 2);
  barre = new Grove_LED_Bar(PIN_BARRE_CLK, PIN_BARRE_DATA, false);
  barre->setLevel(0);
  ecran("L.E.R.N.A.", "Demarrage...", 255, 255, 255);

  // Les boutons Grove sont à BAS au repos et passent à HAUT quand on appuie : on mesure le niveau de repos au démarrage.
  for (uint8_t i = 0; i < NB_BOUTONS; i++) {
    pinMode(PIN_BOUTON[i], INPUT);
    uint8_t hauts = 0;
    for (uint8_t k = 0; k < 20; k++) { hauts += digitalRead(PIN_BOUTON[i]); delay(5); }
    reposBouton[i] = (hauts >= 10);
    etatBouton[i] = false;
    tBouton[i] = 0;
  }

  // petit double bip + balayage de la barre : on voit tout de suite que la carte démarre
  bip(1000, 100); delay(50); bip(1500, 100); delay(50);
  for (int n = 0; n <= 10; n++) { barre->setLevel(n); delay(40); }
  for (int n = 10; n >= 0; n--) { barre->setLevel(n); delay(40); }
  dernierNiveauBarre = 0;

  deplacerServo(SERVO_FERME);
  mqtt.setServer(mqtt_server, mqtt_port);
  mqtt.setCallback(onMessage);
  mqtt.setKeepAlive(15);
}

unsigned long tMqttLoop = 0;

void loop() {
  maintenirReseau();
  // pendant la sirène, la boucle tourne le plus vite possible pour garder un son régulier : MQTT est consulté toutes les 10 ms
  if (mqtt.connected() && (sonPeriode == 0 || millis() - tMqttLoop >= 10)) { mqtt.loop(); tMqttLoop = millis(); }
  lireBoutons();
  sirene();
  gererServo();
  sonMaj();
  if (sonPeriode == 0) delay(2);
}
