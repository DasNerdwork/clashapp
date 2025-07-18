# clashscout.com

Dies ist das Backup & History Repository für die League of Legends Clash Webapp von DasNerdwork.net

## Intro

Die ClashScout.com Webapplikation, kurz ClashScout, ist ein Projekt welches hauptsächlich von 
Florian Falk und Pascal Gnadt entwickelt wurde.

Sie stellt ein einfaches Tool zur Optimierung der Scouting- und Bannphase in League of Legends Clash
Turnieren dar, indem sie komprimiert, effizient, schnell und dennoch simpel Daten von der League of 
Legends API fetcht, verarbeitet und unterstützend repräsentiert.

Das Projekt wurde im Januar 2022 ins Leben gerufen.

## Feature Übersicht

*   [x] **Gesamtübersicht eines Gegnerteams** komprimiert auf eine Website
*   [x] **Bannempfehlungen** automatisch generiert
*   [x] **Matchhistory** jedes einzelnen Spielers
*   [x] **Automatische Aktualisierungen** der Matchhistorie 
*   [x] **Auswertung von Rankdaten** inkl. Meisterschaftspunkte
*   [x] **Websocket** für Live dargestellte Bannauswahl
*   [x] **Champion Filterung** zum simpleren Navigieren
*   [x] **Useraccounts** mit angebundener Datenbank
*   [x] **Hohe Sicherheitsmaßnahmen** in Bereichen Verschlüsselung, Injection & Aufbewahrung 
*   [x] **Tiefgehende Datenverarbeitung** der einzelnen Spiel- und Spielerattribute
*   [x] **Live Benachrichtigung** der aktiven Seitenbetrachter
*   [x] **2-Faktor Authentifizierung** für Useraccounts
*   [x] **Useraccounts** mit angebundener Datenbank
*   [x] **Optimiertes CSS** durch **Tailwind**
*   [x] **Optimiertes Javascript** durch **Minifying**
*   [x] **Verschärft eingestellte NGINX Header**
*   [x] **Anbindung an League of Legends Accounts**
*   [x] **Tags** pro Spieler & Team zur schnellen identifierung von Faktoren
*   [x] **Premium** Accounts inkl. exkludierter Werbebanner
*   [x] **Discord Bot** angebunden an Github Status Updates
*   [ ] Persönlich einstellbare **League Userprofile**
und noch vieles mehr...

### Genutzte Technologien

* [TailwindCSS](https://tailwindcss.com/)
* [AlpineJS](https://alpinejs.dev/)
* [NodeJS](https://nodejs.org/)
* [PragmaRX\Google2FA](https://packagist.org/packages/pragmarx/google2fa)
* [BaconQrCode](https://github.com/Bacon/BaconQrCode)
* [websocket/ws](https://github.com/websockets/ws)
* [PHP8.2](https://www.php.net/releases/8.2/en.php)
* [NGINX](https://www.nginx.com/)
* [MySQL](https://www.php.net/manual/de/book.mysqli.php)
* [MongoDB](https://www.mongodb.com/de-de)

### Install

Um aktiv remote am Projekt zu arbeiten kann das Repository mit folgendem Befehl geklont werden

```
git clone https://github.com/DasNerdwork/clashapp.git
```

### Änderungen

Zum Hinzufügen von Änderungen müssen diese als Merge-Requests einhergehen. Dazu lohnt es sich einen eigenen Branch zu erstellen und von diesem aus am Ende einen Merge zu Erstellen

```
git checkout -b "Name-des-neuen-Features"
```

Sobald die Änderungen fertiggestellt wurden können sie mit folgenden befehlen commited werden

```
git add .
git commit --author "deine@email.com" -m "Beschreibung des neuen Features"
git push main $branchname
```
