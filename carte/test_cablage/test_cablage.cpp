// Test du câblage : à envoyer sur la carte pour vérifier chaque composant.
//   pio run -e cablage -t upload     puis     pio device monitor
// Le test tourne en boucle. Les boutons sont lus en permanence et affichés sur le moniteur série et sur l'écran.
#include <Arduino.h>
#include <Wire.h>
#include <Servo.h>
#include <rgb_lcd.h>
#include <Grove_LED_Bar.h>

// --- Branchements (prises de la carte d'extension) ---
const uint8_t PIN_LED_ROUGE = 2;    // prise D2
const uint8_t PIN_LED_VERTE = 3;    // prise D3
const uint8_t PIN_LED_BLEUE = A4;   // prise A4
const uint8_t PIN_BUZZER    = 5;    // prise D5
const uint8_t PIN_SERVO     = 4;    // prise D4
const uint8_t PIN_BARRE_1   = A5;   // prise double A5/A6 : première broche
const uint8_t PIN_BARRE_2   = A6;   // prise double A5/A6 : deuxième broche

const uint8_t NB_BOUTONS = 6;
const char*   NOM_BOUTON[NB_BOUTONS] = {"demarrage", "arret", "serveur1", "serveur2", "serveur3", "serveur4"};
const uint8_t PIN_BOUTON[NB_BOUTONS] = {0, 1, A0, A1, A2, A3};   // D0, D1, A0, A1, A2, A3
bool          reposBouton[NB_BOUTONS];
bool          etatBouton[NB_BOUTONS];
unsigned long tBouton[NB_BOUTONS];

rgb_lcd lcd;
Servo   servo;

void ecran(const char* l1, const char* l2, uint8_t r, uint8_t g, uint8_t b) {
  char buf[17];
  lcd.setRGB(r, g, b);
  snprintf(buf, sizeof(buf), "%-16.16s", l1); lcd.setCursor(0, 0); lcd.print(buf);
  snprintf(buf, sizeof(buf), "%-16.16s", l2); lcd.setCursor(0, 1); lcd.print(buf);
}

void lireBoutons() {
  for (uint8_t i = 0; i < NB_BOUTONS; i++) {
    bool actif = (digitalRead(PIN_BOUTON[i]) != reposBouton[i]);
    if (actif != etatBouton[i] && millis() - tBouton[i] > 30) {
      etatBouton[i] = actif;
      tBouton[i] = millis();
      Serial.print("BOUTON "); Serial.print(NOM_BOUTON[i]); Serial.println(actif ? " PRESSE" : " relache");
      if (actif) {
        char buf[17];
        snprintf(buf, sizeof(buf), "%-16.16s", NOM_BOUTON[i]);
        lcd.setCursor(0, 1); lcd.print(buf);
      }
    }
  }
}

void attendre(unsigned long ms) {
  unsigned long t0 = millis();
  while (millis() - t0 < ms) { lireBoutons(); delay(5); }
}

void etapeLeds() {
  Serial.println("TEST LED rouge"); ecran("LED rouge", "Appuie boutons", 255, 0, 0);
  digitalWrite(PIN_LED_ROUGE, HIGH); attendre(900); digitalWrite(PIN_LED_ROUGE, LOW);
  Serial.println("TEST LED verte"); ecran("LED verte", "Appuie boutons", 0, 255, 0);
  digitalWrite(PIN_LED_VERTE, HIGH); attendre(900); digitalWrite(PIN_LED_VERTE, LOW);
  Serial.println("TEST LED bleue"); ecran("LED bleue", "Appuie boutons", 0, 0, 255);
  digitalWrite(PIN_LED_BLEUE, HIGH); attendre(900); digitalWrite(PIN_LED_BLEUE, LOW);
}

void etapeBuzzer() {
  Serial.println("TEST buzzer : 3 notes (grave, moyenne, aigue)");
  ecran("BUZZER notes", "grave-moy-aigue", 255, 255, 0);
  tone(PIN_BUZZER, 440);  attendre(400); noTone(PIN_BUZZER); attendre(150);
  tone(PIN_BUZZER, 880);  attendre(400); noTone(PIN_BUZZER); attendre(150);
  tone(PIN_BUZZER, 1760); attendre(400); noTone(PIN_BUZZER); attendre(400);
  Serial.println("TEST buzzer : courant continu (sonne seulement si buzzer ACTIF)");
  ecran("BUZZER continu", "actif = sonne", 255, 255, 0);
  digitalWrite(PIN_BUZZER, HIGH); attendre(500); digitalWrite(PIN_BUZZER, LOW);
}

void remplirBarre(Grove_LED_Bar& barre) {
  for (int n = 0; n <= 10; n++) { barre.setLevel(n); attendre(120); }
  attendre(300);
  barre.setLevel(0);
}

void etapeBarre() {
  Serial.println("TEST barre de LED, ordre des fils 1 (A5=donnees, A6=horloge)");
  ecran("BARRE ordre 1", "A5 data A6 clk", 0, 120, 255);
  { Grove_LED_Bar barre(PIN_BARRE_2, PIN_BARRE_1, false); remplirBarre(barre); }
  Serial.println("TEST barre de LED, ordre des fils 2 (A6=donnees, A5=horloge)");
  ecran("BARRE ordre 2", "A6 data A5 clk", 0, 120, 255);
  { Grove_LED_Bar barre(PIN_BARRE_1, PIN_BARRE_2, false); remplirBarre(barre); }
}

void etapeServo() {
  Serial.println("TEST servo : 0 puis 90 puis 0 degres");
  ecran("SERVO", "0 > 90 > 0", 200, 0, 200);
  servo.attach(PIN_SERVO);
  servo.write(0);  attendre(700);
  servo.write(90); attendre(900);
  servo.write(0);  attendre(900);
  servo.detach();
}

void setup() {
  Serial.begin(115200);
  unsigned long t = millis();
  while (!Serial && millis() - t < 3000);
  Serial.println("=== TEST DU CABLAGE LERNA ===");

  pinMode(PIN_LED_ROUGE, OUTPUT); pinMode(PIN_LED_VERTE, OUTPUT); pinMode(PIN_LED_BLEUE, OUTPUT);
  pinMode(PIN_BUZZER, OUTPUT);
  digitalWrite(PIN_LED_ROUGE, LOW); digitalWrite(PIN_LED_VERTE, LOW); digitalWrite(PIN_LED_BLEUE, LOW);
  digitalWrite(PIN_BUZZER, LOW);

  lcd.begin(16, 2);
  ecran("TEST LERNA", "demarrage...", 255, 255, 255);

  for (uint8_t i = 0; i < NB_BOUTONS; i++) {
    pinMode(PIN_BOUTON[i], INPUT);
    uint8_t hauts = 0;
    for (uint8_t k = 0; k < 20; k++) { hauts += digitalRead(PIN_BOUTON[i]); delay(5); }
    reposBouton[i] = (hauts >= 10);
    etatBouton[i] = false;
    tBouton[i] = 0;
    Serial.print("bouton "); Serial.print(NOM_BOUTON[i]);
    Serial.print(" : niveau au repos = "); Serial.print(reposBouton[i] ? "HAUT" : "BAS");
    if (hauts != 0 && hauts != 20) Serial.print("  (instable : fil debranche ?)");
    Serial.println();
  }
  Serial.println("Appuie sur chaque bouton quand tu veux : il s'affiche ici et sur l'ecran.");
}

void loop() {
  etapeLeds();
  etapeBuzzer();
  etapeBarre();
  etapeServo();
  ecran("Cycle termine", "Appuie boutons", 255, 255, 255);
  Serial.println("--- fin du cycle, on recommence dans 4 s ---");
  attendre(4000);
}
