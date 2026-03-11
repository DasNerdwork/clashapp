# agents.md — clashapp
> League of Legends Clash Scouting Webapp für Turnier-Optimierung

## 1. Kritische Regeln
<!-- Dinge die NIEMALS verletzt werden dürfen. Maximal 7 Punkte. -->
- **NIEMALS** User-Input ohne Validierung (CSRF-Token, XSS-Protection, Input-Sanitization)
- **NIEMALS** API-Keys oder Secrets in Code speichern — immer via `getenv()` aus `.env`
- **NIEMALS** Cache ignorieren: `fileExistsWithCache()` für alle `file_exists()` Checks nutzen
- **NIEMALS** Match-Daten ohne MongoDB-Helper abfragen — immer über `MongoDBHelper`
- **NIEMALS** Rate-Limits ignorieren: 429 Statuscodes mit Retry-After warten (max 3 Versuche)
- **NIEMALS** ohne `sanitize()` von `Security\Security.php` Output rendern
- **NIEMALS** MongoDB-Queries ohne `sanitizeMongoQueryValue()` ausführen

## 2. Projekttyp & Stack
<!-- z.B. "Node.js REST API + React Frontend, TypeScript, PostgreSQL via Prisma" -->
PHP 8.x Webapp (Apache/Nginx) + MongoDB + Node.js WebSocket Server + Tailwind CSS + Alpine.js. League of Legends Riot API Integration für Matchdaten, Champion-Mastery, Ranked-Infos.

## 3. Verzeichnisstruktur
<!-- Nur Verzeichnisse die Logik enthalten. node_modules, dist, .git weglassen. Format: pfad/ — was hier liegt -->
- `accounts/` — User-Authentifizierung (login, register, 2FA, settings, qr-codes)
- `ajax/` — AJAX-Endpoints für Frontend-Kommunikation
- `css/` — Tailwind CSS Stylesheets (output.css, uncompacted.css)
- `data/` — Patch-Daten, Logs, Temporäre Dateien
  - `data/logs/` — Logdateien für alle Skripte
  - `data/patch/` — League of Legends Patch-Daten (champion.json, summoner.json, images)
- `db/` — MongoDB-Helper (mongo-db.php, mongodb.py, clash-db.php)
- `docs/` — Dokumentation (SECURITY_UPDATES.md)
- `js/` — Frontend JavaScript (main.js, clash.js, qr-codes.js, websocket.js, ajax.js, alpine.js)
- `lang/` — Übersetzungen (de_DE.csv, en_US.csv, translate.php)
- `pages/` — PHP Seiten (index.php, profile.php, team.php, minigames.php, 404.php, champion.php, graphs-and-formulas.php)
- `scripts/` — Python Skripte für Daten-Fetching und Maintenance
- `src/` — PHP-Klassen & Funktionen (API_Cache.php, apiFunctions.php, Security.php, Logger.php, csrf.php, functions.php, update.php, RateLimiter.php)
- `templates/` — PHP Template-Teile (head.php, header.php, footer.php)
- `test/` — PHPUnit Tests (accountsTest.php, apiFunctionsTest.php, clashDBTest.php, functionsTest.php, mongoDBTest.php, templatesTest.php, translateTest.php, updateTest.php)
- `websocket/` — Node.js WebSocket Server für Live-Bannphase
- `vendor/` — Composer Dependencies

## 4. Wichtige Dateien
<!-- Maximal 20 Dateien. Nur Dateien die direkt editiert werden. Format: pfad — Zweck (+ Fallstrick) -->

### Backend / PHP
1. `src/functions.php` — Globale PHP-Funktionen (Match-Ranking, Champion-Icons, Statistiken) — Veraltetes PHP 5 Syntax, viele globale Variablen
2. `src/apiFunctions.php` — Riot API Wrapper mit Rate-Limit Handling (429 Retry-After)
3. `src/Security.php` — Input-Validierung & XSS-Schutz — `sanitize()` für alle Outputs
4. `src/Logger.php` — Syslog-Logger mit Rotation (Singleton) — Dateigrößenlimits beachten
5. `src/csrf.php` — CSRF-Token-Manager — `validateRequest()` bei POST/PUT/DELETE
6. `src/API_Cache.php` — API-Caching mit Expire-Zeiten
7. `src/RateLimiter.php` — Rate-Limiting für API-Aufrufe
8. `db/mongo-db.php` — MongoDB-Helper mit `sanitizeMongoQueryValue()`
9. `db/clash-db.php` — Account-Management mit PASSWORD_BCRYPT
10. `ajax/csrf-token.php` — CSRF-Token für Frontend

### AJAX-Endpoints: siehe Section 10

### Python Skripte
11. `scripts/statFetcher.py` — averageStats.json für Rollen (FILL, TOP, JUNGLE, MIDDLE, UTILITY, BOTTOM) — MongoDB-Abfragen, loggt in `/data/logs/statFetcher.log`
12. `scripts/matchCollector.py` — Riot API Matchdaten mit Rate-Limit Handling, PID-Management
13. `scripts/champAggregator.py` — Champion-Statistiken aggregieren (Winrate/Banrate)
14. `scripts/patcher.py` — Patch-Update (Download, AVIF, Cleanup) — Langsam, benötigt Pillow
15. `scripts/mongodb_cleanup.sh` — Collections leeren — Nur Admins!

### Node.js
16. `websocket/server.js` — WebSocket Server (Port 8083, SSL, HMAC-Validierung) — SSL-Zertifikat-Pfad beachten
17. `websocket/package.json` — Node.js Dependencies

## 5. Befehle
<!-- Exakte Befehle, keine Prosa -->

### PHP / Composer
- `composer install` — Dependencies installieren
- `composer update` — Dependencies aktualisieren
- `php vendor/bin/phpunit` — Tests ausführen
- `php -v` — PHP Version

### Python Skripte
- `python scripts/statFetcher.py` — averageStats.json für Rollen
- `python scripts/matchCollector.py` — Matchdaten von Riot API
- `python scripts/champAggregator.py` — Champion-Statistiken
- `python scripts/patcher.py` — Patch-Daten (Hintergrund via cron)
- `python scripts/patcher.py --abbr` — Nur Abbreviations.json
- `python scripts/mongodb_cleanup.sh` — Collections leeren (Admins!)

### Node.js / WebSocket
- `cd websocket && npm install` — Dependencies
- `cd websocket && npm start` — Server starten
- `cd websocket && npm run dev` — Server (Entwicklung)

### Tailwind CSS
- `npx tailwindcss -i css/clash.css -o css/output.css --minify --watch` — Build (via watchTailwind.sh)
- `scripts/watchTailwind.sh` — Watch-Modus

### System / Maintenance
- `pm2 list` — Prozesse
- `pm2 restart 'WS-Server'` — WebSocket Restart
- `mongosh 'mongodb://...'` — MongoDB Shell
- `systemctl status nginx` — Nginx Status
- `git rev-list --all --count` — Commit-Zähler

## 6. Workflow
<!-- Wie eine typische Änderung durch die Codebase fließt. Schritt für Schritt, maximal 8 Schritte. -->

### Feature-Entwicklung
1. **Neue Funktion in `src/functions.php`**: Globale Hilfsfunktion hinzufügen
2. **API-Integration**: Bei Riot API Calls → `src/apiFunctions.php` mit Rate-Limit Handling (429 Retry-After)
3. **Datenbank**: Alle DB-Queries über `db/mongo-db.php` mit `sanitizeMongoQueryValue()`
4. **Sicherheit**: CSRF-Token von `ajax/csrf-token.php` laden, `Security::sanitize()` für Output
5. **Tests**: Änderungen mit `php vendor/bin/phpunit` validieren
6. **Logs**: Wichtige Events via `Logger::getInstance()->info()` loggen
7. **Frontend**: Alpine.js für Interaktivität, Tailwind CSS Klassen für Styling
8. **Deployment**: Git Merge Request erstellen, nicht direkt auf main pushen

### AJAX-Endpoint hinzufügen
1. **PHP Handler**: Neue Datei in `ajax/` erstellen (z.B. `ajax/my-endpoint.php`)
2. **CSRF-Schutz**: `Security\CSRF::validateRequest()` bei POST/PUT/DELETE
3. **Input-Validierung**: `Security::isValidPlayerName()`, `Security::sanitize()` für User-Inputs
4. **Frontend Integration**: JS-Call in `js/main.js` oder `js/clash.js` hinzufügen
5. **Tests**: PHPUnit Test in `test/` für neuen Endpoint schreiben

### Python-Daten-Fetch einrichten
1. **Skript erstellen**: Neue Datei in `scripts/` (z.B. `scripts/myFetcher.py`)
2. **MongoDB-Helper**: Von `db/mongodb.py` importieren
3. **Riot API**: Von `scripts/matchCollector.py` Pattern kopieren
4. **Logging**: RotatingFileHandler mit `/data/logs/myFetcher.log`
5. **Cron Job**: Eintrag in `/etc/cron.d/clashapp` hinzufügen

### Neue Übersetzung hinzufügen
1. **CSV Datei**: Eintrag in `lang/de_DE.csv` und `lang/en_US.csv` hinzufügen
2. **Format**: `"Key","Value"` mit Anführungszeichen bei Kommas im Value
3. **PHP Function**: `__("Key")` überall verwenden wo Übersetzung benötigt wird
4. **Test**: In `test/translateTest.php` prüfen dass Key existiert

## 7. Architekturentscheidungen
<!-- Muster die du ZWINGEND einhalten musst um konsistent zu bleiben. -->
- MongoDB: Nur über `MongoDBHelper` in `db/mongo-db.php`
- CSRF: `Security\CSRF::validateRequest()` bei POST/PUT/DELETE
- Input: `Security::isValidPlayerName()`, `Security::sanitize()`
- Rate-Limit: Max 100 MatchIDs pro Request, 429 → Retry-After (max 3)
- Cache: `fileExistsWithCache()` für Datei-Checks, globales Caching in `functions.php`
- Secrets: Immer `getenv()` aus `.env`

## 8. Fallstricke
<!-- Nicht offensichtliche Probleme -->
- `MDB_HOST`, `MDB_USER`, `MDB_PW`, `API_KEY` in `.env` setzen (sonst MongoDB/API lautlos ab)
- WebSocket SSL-Zertifikat: `/etc/letsencrypt/live/dasnerdwork.net/`
- Match-Log Rotation: >10MB wird halbiert (`data/logs/matchDownloader.log`)
- Kayn Darkin: Transform-Champions (id=1,2) brauchen Icon-Overlays
- MongoDB: `sanitizeMongoQueryValue()` bei allen Queries
- Avatar-Icons: Temporär ändern für 2FA-Verifizierung
- 2FA: Google Authenticator, 6-stelliger Code
- Account-Deaktivierung: 48-72h Delay vor Löschung

## 9. Nicht veränderbar
<!-- Dinge die du in diesem Projekt NIEMALS verändern sollst. -->
- `db/mongo-db.php` — MongoDB-Helper Struktur
- `src/Security.php` — Nur erweitern, nicht umschreiben
- `src/csrf.php` — Kritische Security-Infrastruktur
- `websocket/server.js` — Protokoll und HMAC-Validierung
- `scripts/statFetcher.py` — averageStats.json Logik
- `data/misc/averageStats.json` — Nicht manuell editieren
- `composer.json` — Nur via Merge Request
- `data/misc/abbreviations.json` — Von `patcher.py` aktualisiert

## 10. AJAX-Endpoints
<!-- Vollständige Tabelle aller AJAX-Dateien -->

| Datei | Methode | Parameter | Rückgabe |
|-------|---------|-----------|----------|
| `ajax/calcTeamRanking.php` | POST | `teamName` (required: Summoner Name) | `{ranking: number, teamName: string}` |
| `ajax/captcha-config.php` | GET | — | `{captcha_enabled: boolean, captcha_type: string}` |
| `ajax/captcha-request.php` | POST | `captcha_response` (required) | `{valid: boolean, message: string}` |
| `ajax/csrf-token.php` | GET | — | `{token: string}` |
| `ajax/downloadMatch.php` | POST | `matchId` (required), `puuid` (optional) | `{matchData: object, status: string}` |
| `ajax/generatePlayerColumn.php` | POST | `playerName`, `teamName` (required) | `{html: string, playerId: string}` |
| `ajax/onAllFinish.php` | POST | `matchId`, `playerName` (required) | `{status: string}` |
| `ajax/onSingleFinish.php` | POST | `matchId`, `playerName` (required) | `{status: string}` |
| `ajax/pixelGuesser.php` | POST | `captcha_response` (required) | `{valid: boolean, message: string}` |
| `ajax/rate-limit.php` | GET | `client_ip`, `endpoint` (required) | `{limit: number, remaining: number, reset: number}` |

## 11. Datenbankstruktur
Datenbankschema: in MongoDB direkt einsehbar, Collections: players, matches, teams

## 12. Abhängigkeiten
<!-- Composer und npm-Packages mit Zweck -->

### PHP
- `pragmarx/google2fa` — 2FA/Google Authenticator
- `mongodb/mongodb` — MongoDB Driver
- `bacon/bacon-qr-code` — QR-Code Generierung
- `fabianwennink/iconcaptcha` — Captcha-Validierung
- `predis/predis` — Redis Client (optional)
- `vlucas/phpdotenv` — .env Parsing
- `phpunit/phpunit` — Testing

### Node.js
- `axios` — HTTP Client
- `ansi-regex` — Terminal Farben

### System
- Python 3.10+
- Pillow
- MongoDB
- Nginx
- PM2
- Redis (optional)