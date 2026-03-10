# Sicherheits- und Performance-Updates für ClashApp

## Überblick

Diese Datei dokumentiert die durchgeführten Sicherheits- und Performance-Verbesserungen im ClashApp-Projekt.

## Implementierte Verbesserungen

### 1. Security.php

**Hash-basierte Champion-Validierung**
- Verwendet HMAC-256 für sichere Champion-Validierung
- Schutz gegen Code Injection durch Unicode-Tricks
- Normalisierter String-Vergleich (kleinbuchstaben, ohne Leerzeichen)

**Verfügbare Funktionen:**
```php
use Security\Security;

// Champion-Validierung
$result = Security\Security::validateChampion($champId, $champName);

// Input-Sanitierung
$safeInput = Security\Security::sanitize($userInput);

// Spielername-Validierung
$isValid = Security\Security::isValidPlayerName($playerName);
```

### 2. Logger.php

**Syslog-basiertes Logging mit Dateifallback**
- Automatische Dateigrößenbegrenzung (max. 10MB)
- Log-Level: DEBUG, INFO, WARNING, ERROR, CRITICAL

**Verwendung:**
```php
use Logging\Logger;

Logger::getInstance()->info("Eine Info-Nachricht");
Logger::getInstance()->error("Ein Fehler: " . $error);
Logger::getInstance()->warning("Eine Warnung");
```

### 3. RateLimiter.php

**DoS-Schutz durch Rate-Limitierung**
- Redis-basierte Limitierung (mit In-Memory Fallback)
- Konfigurierbare Limits pro IP/Session

**Verwendung:**
```php
use Security\RateLimiter;

$limiter = RateLimiter::getInstance();

// Prüfen ob Anfrage erlaubt
if (!$limiter->isAllowed('user_' . $userId, 100, 60)) {
    http_response_code(429);
    echo json_encode(['status' => 'error', 'message' => 'Too many requests']);
    exit;
}
```

### 4. API_Cache.php

**Riot-API Caching**
- Redis-Cache mit konfigurierbarem TTL (Standard: 1 Stunde für Spielerdaten)
- Reduziert API-Last signifikant

**Verwendung:**
```php
use Cache\API_Cache;

$cache = API_Cache::getInstance();

// Daten holen
$data = $cache->get('player:PUUID:abc123');

// Daten speichern
$cache->set('player:PUUID:abc123', $playerData);

// Mit TTL speichern
$cache->setWithTTL('player:PUUID:abc123', $playerData, 600); // 10 Minuten
```

### 5. csrf.php

**CSRF-Token-Manager**
- Schutz gegen Cross-Site Request Forgery
- Token mit automatischer Expiry

**Verfügbare Funktionen:**
```php
use Security\CSRF;

// Token generieren
$token = CSRF::generateToken();

// Token verifizieren
$isValid = CSRF::verifyToken($token);

// Token erhalten
$token = CSRF::getToken();

// Token regenerieren
$newToken = CSRF::regenerateToken();
```

### 6. ajax/csrf-token.php

**AJAX-Endpoint zum Abrufen von CSRF-Tokens**

Verwendung im Frontend:
```javascript
fetch('/ajax/csrf-token.php')
    .then(r => r.json())
    .then(data => {
        document.body.dataset.csrfToken = data.token;
    });
```

### 7. ajax/rate-limit.php

**Rate-Limiting Wrapper für AJAX-Endpunkte**

**Verfügbare Funktionen:**
```php
// Rate-Limit prüfen
$result = checkRateLimit('user_' . $userId, 100, 60);

// CSRF-Token prüfen
validateRequest($_POST, 100, 60);
```

## Performance-Verbesserungen

### 1. Persistent MongoDB-Verbindung (WebSocket-Server)

Die MongoDB-Verbindung wird nun beim Server-Start etabliert und bleibt persistent:

**Vorteile:**
- Keine Verbindungsaufbau-Overheads bei jedem Request
- Connection Pooling (maxPoolSize: 10, minPoolSize: 2)
- Verbesserte Fehlerbehandlung

### 2. Riot-API Caching

Spielerdaten werden für 10 Minuten gecacht:

**Vorteile:**
- Bis zu 99% weniger API-Requests für populäre Spieler
- Schnellere Antwortzeiten
- Geringere Last auf die Riot API

### 3. Verbesserte Log-Rotation

Logs werden automatisch bei 10MB Größe begrenzt:

**Vorteile:**
- Keine riesigen Logdateien
- Geringerer Speicherbedarf
- Einfachere Wartung

## Migrationshinweise

### Frontend-Anpassungen

Alle AJAX-Formulare müssen nun CSRF-Token enthalten:

```javascript
// Beim Login/Session-Start Token abrufen
fetch('/ajax/csrf-token.php')
    .then(r => r.json())
    .then(data => {
        document.body.dataset.csrfToken = data.token;
    });

// Bei Formular-Submissions
fetch('/ajax/endpoint.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': document.body.dataset.csrfToken
    },
    body: JSON.stringify(data)
});
```

### Backend-Anpassungen

AJAX-Endpunkte sollten die neuen Sicherheitsfunktionen verwenden:

```php
<?php
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/RateLimiter.php';

use Security\CSRF;
use Security\RateLimiter;

// CSRF-Token verifizieren
if (!CSRF::verifyToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

// Rate-Limit prüfen
$limiter = RateLimiter::getInstance();
if (!$limiter->isAllowed('ajax_' . $_SERVER['REMOTE_ADDR'], 100, 60)) {
    http_response_code(429);
    echo json_encode(['status' => 'error', 'message' => 'Too many requests']);
    exit;
}

// ... normale Verarbeitung ...
?>
```

## Konfiguration

### Redis-Konfiguration

Die Caching- und Rate-Limiting-Funktionen nutzen Redis. Wenn Redis nicht verfügbar ist, wird automatisch ein In-Memory-Fallback verwendet.

**Umgebungsvariablen:**
```bash
REDIS_HOST=localhost
CACHE_TTL=3600  # TTL in Sekunden (Standard: 3600 = 1 Stunde)
```

### Logging-Konfiguration

**Umgebungsvariablen:**
```bash
LOG_DIR=/hdd1/clashapp/data/logs
MAX_LOG_SIZE=10485760  # 10MB in Bytes
```

## Sicherheitshinweise

1. **Secret Salt**
   - Das Secret Salt in `Security.php` sollte geändert werden
   - Erstelle eine neue UUID und ersetze den aktuellen Salt-Wert

2. **CSRF-Token**
   - CSRF-Token sollten nach jedem Login regeneriert werden
   - Veraltete Tokens werden automatisch nach 1 Stunde ungültig

3. **Rate-Limiting**
   - Die Standard-Limits sind für normale Nutzung konfiguriert
   - Bei hoher Last können die Limits angepasst werden

## Troubleshooting

### Probleme mit Redis

Wenn Redis nicht verfügbar ist, wird automatisch auf In-Memory-Limitierung zurückgegriffen. Dies funktioniert nur während der Lebensdauer des PHP-Prozesses.

**Lösung:** Redis installieren und konfigurieren

```bash
# Redis installieren
apt-get install redis-server

# Redis konfigurieren
redis-cli ping  # Sollte "PONG" zurückgeben
```

### Cache-Probleme

Wenn der Cache nicht funktioniert, prüfe die Redis-Verbindung:

```bash
redis-cli -h localhost ping
```

## Lizenz

Dieses Update ist Teil des ClashApp-Projekts.
Alle Rechte vorbehalten © 2026 DasNerdwork