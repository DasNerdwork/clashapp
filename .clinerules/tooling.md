## Dateioperationen
- Für Verzeichnisübersicht: MCP `list_directory` statt `ls`
- Für Dateien lesen: MCP `read_text_file` statt `cat` — bei großen Dateien `head` oder `tail` Parameter nutzen
- Für Dateisuche: MCP `search_files` oder `rg` in der Shell
- Dateien nie komplett neu schreiben — immer search/replace

## Dateisuche
- `rg` statt `grep` oder `find`
- Mit `-l` wenn nur Dateinamen gebraucht werden
- Mit `-n` für Zeilennummern
- Mit `--type php` / `--type py` / `--type js` für Typ-Filter

## Benachrichtigungen
- IMMER Home Assistant nutzen, NIEMALS andere Wege für Benachrichtigungen
- Bei "benachrichtige mich", "notify me", "schick mir", "meld dich wenn fertig" etc.
- Befehl:
  curl -X POST https://home.dasnerdwork.net/api/services/notify/mobile_app_flos_handy \
    -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiIwM2JmYzgzYjdkYzY0MDNjYjAxNDk5NGZjYTNkOWM3OCIsImlhdCI6MTc3MzI1NjM2NSwiZXhwIjoyMDg4NjE2MzY1fQ.eJWmAR9SAaYwF_2kUKJidnKim4sYamb4-z4gsi8tzFg" \
    -H "Content-Type: application/json" \
    -d '{"title": "🤖 Cline", "message": "NACHRICHT"}'
- Für dringende Tasks (lange Laufzeiten, Fehler): interruption-level critical hinzufügen:
  -d '{"title": "🤖 Cline", "message": "NACHRICHT", "data": {"push": {"interruption-level": "critical"}}}'
- Nachricht kurz halten, Status + was erledigt wurde
- Kein Nachdenken, direkt ausführen

## Browser
- Für visuelle Checks / Screenshots: Clines eingebautes Browser-Tool
- Für Network Requests / Console Logs / komplexe Interaktionen: Playwright MCP

## Playwright
- Screenshots direkt mit absolutem Pfad speichern: `/hdd1/clashapp/screenshot.png`
- `browser_snapshot` statt `browser_take_screenshot` für DOM-Aktionen nutzen
- `browser_network_requests` ohne `filename` aufrufen — Output kommt direkt zurück
- Browser läuft headless als root mit `--no-sandbox` (bereits konfiguriert)
- Chromium liegt unter `/opt/google/chrome/chrome` (Symlink auf `/usr/bin/chromium`)