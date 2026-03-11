## Git Commits

Commits folgen dem Conventional Commits Standard.

### Format
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]

### Types
- feat: Neues Feature
- fix: Bugfix
- docs: Nur Dokumentation
- style: Formatierung, kein Logik-Change
- refactor: Code-Umstrukturierung ohne Feature/Fix
- test: Tests hinzufügen oder anpassen
- chore: Build-Prozess, Dependencies, Config

### Regeln
- Beschreibung in Kleinbuchstaben, kein Punkt am Ende
- Scope in Klammern wenn sinnvoll: feat(api): ...
- Body nur wenn die Änderung nicht selbsterklärend ist
- Breaking Changes im Footer: BREAKING CHANGE: <beschreibung>
- Commits atomar halten — eine logische Änderung pro Commit

### Beispiele (aus diesem Projekt)
feat(api): enhance riot api rate limit handling
fix(mongodb): sanitize query values in aggregate pipeline
chore: update nginx config
docs: add schema.md for database structure
test: update phpunit suites after auth refactor