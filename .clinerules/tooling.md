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

## WhatsApp Benachrichtigungen
- Notification-Ziel (Gruppe): 120363427307706468@g.us
- Bei Phrasen wie "benachrichtige mich", "schick mir ne WhatsApp", 
  "meld dich wenn fertig", "notify me" → immer an meine Nummer senden
- Tool: WhatsApp MCP `send_message` mit meiner Nummer als Empfänger
- Nachricht kurz halten, Status + was erledigt wurde
- Beispiel: "✅ Task abgeschlossen: [kurze Beschreibung]"

## Browser
- Für visuelle Checks / Screenshots: Clines eingebautes Browser-Tool
- Für Network Requests / Console Logs / komplexe Interaktionen: Playwright MCP

## Playwright
- Screenshots direkt mit absolutem Pfad speichern: `/hdd1/clashapp/screenshot.png`
- `browser_snapshot` statt `browser_take_screenshot` für DOM-Aktionen nutzen
- `browser_network_requests` ohne `filename` aufrufen — Output kommt direkt zurück
- Browser läuft headless als root mit `--no-sandbox` (bereits konfiguriert)
- Chromium liegt unter `/opt/google/chrome/chrome` (Symlink auf `/usr/bin/chromium`)