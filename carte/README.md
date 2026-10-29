# Programme de la carte (Arduino MKR WiFi 1010)

La carte ne décide de rien. Elle envoie au site les appuis de boutons et affiche ce que le site lui dit, par MQTT (voir `site/README.md` pour la liste des sujets).

| Élément | Prise | Rôle |
| :--- | :--- | :--- |
| Bouton démarrage / arrêt | D0 / D1 | Arrêt = kill switch (victoire pendant l'alarme). Démarrage = nouvelle partie après la fin |
| Boutons serveur 1 à 4 | A0 à A3 | Redémarrage des serveurs, dans l'ordre routeur (4), BD (3), archives (2), web (1) |
| LED rouge | D2 | Clignote avec la sirène pendant l'alarme |
| LED verte | D3 | Allumée en jeu et à la victoire |
| LED bleue | A4 | Allumée pendant l'attente |
| Servo | D4 | Ouvre le coffre à la victoire (90°), le referme à la nouvelle partie |
| Buzzer (passif) | D5 | Sirène en alarme, mélodie à la victoire, petit clic à chaque appui. Volume réglable (voir plus bas) |
| Barre de LED | A5/A6 | Jauge de fuite, 10 LED pour 0 à 100 % |
| Écran LCD RVB | I2C | Phase et jauge, couleur selon la gravité. Pendant le redémarrage des serveurs : « Serveurs 2/4 » |

Au démarrage, la carte fait un double bip et un balayage de la barre de LED. Si le Wi-Fi ou le broker est injoignable, l'écran l'indique et elle réessaie toute seule.

## Avant de compiler

1. Copier `include/secrets.h.example` en `include/secrets.h`
2. Remplir le nom du Wi-Fi, son mot de passe et l'IP du PC qui fait tourner Mosquitto (commande `ipconfig`)

Le Wi-Fi doit être en 2,4 GHz. `secrets.h` est ignoré par git : le mot de passe du Wi-Fi ne part jamais sur GitHub.

## Compiler et envoyer (PlatformIO, dans VS Code)

```
pio run
pio run -t upload
pio device monitor
```

Le moniteur série est à 115200 bauds. Il affiche l'IP de la carte, chaque message reçu et chaque bouton pressé.

### Volume du buzzer

Le son est produit à la main par des impulsions étroites (`tone()` joue à plein volume). Le volume est la part de chaque période où la broche est à l'état haut : `BUZZER_VOLUME`, de 1 à 50 (50 = plein volume). Il vaut 8 par défaut. Pour le changer, ajouter dans `platformio.ini` :

```
build_flags = -DBUZZER_VOLUME=20
```

Si plusieurs options sont nécessaires, les mettre sur des lignes successives. Un volume plus bas rend aussi le son un peu plus aigre : à tester à l'oreille dans la salle.

Si la barre de LED reste éteinte, ses deux fils sont peut-être inversés : décommenter `build_flags = -DBARRE_FILS_INVERSES` dans `platformio.ini`.

## Tester le câblage

```
pio run -e cablage -t upload
```

Le programme `test_cablage/` fait défiler chaque LED, le buzzer, la barre, le servo et affiche chaque bouton pressé sur le moniteur série et l'écran. Il faut ensuite renvoyer le programme du jeu avec `pio run -t upload`.

## Si la carte n'affiche que des points ou « Connexion Wi-Fi »

Elle n'arrive pas à se connecter au Wi-Fi. Vérifier dans `include/secrets.h` :

- le nom du Wi-Fi et surtout le mot de passe : une lettre inversée suffit à le faire échouer (c'est arrivé : `mkpG` et `mpkG`)
- que le partage de connexion du téléphone est bien en 2,4 GHz (sur iPhone : « Optimiser la compatibilité »)
- que l'IP du PC (`MQTT_SERVER_IP`) est toujours la bonne : elle peut changer à chaque connexion au partage de connexion

## Tester sans le site

Avec Mosquitto lancé (voir `site/README.md`) :

```
mosquitto_pub -h localhost -t lerna/alarme -m ON
mosquitto_pub -h localhost -t lerna/alarme -m OFF
mosquitto_sub -h localhost -t "lerna/#" -v
```

La dernière commande affiche tout ce qui circule, dont les appuis de boutons de la carte.
