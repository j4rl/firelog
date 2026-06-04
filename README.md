# Skjutdagbok

Skjutdagbok är en personlig webbapp för att logga skyttepass, vapen och resultat. Appen är byggd för skyttar som vill få en tydlig historik över sina serier, följa sin utveckling över tid och enkelt kunna exportera sin data.

## Vad appen gör

Appen låter varje användare skapa ett eget konto och hantera sin egen skjutdagbok. Efter inloggning kan användaren registrera vapen, skapa skjutpass och mata in skottvärden serie för serie.

Poäng räknas automatiskt när en serie sparas. `X` räknas som 10 poäng och sparas även separat som antal X-träffar. Alla serier kopplas till ett skjutpass, och varje skjutpass kopplas till ett registrerat vapen.

## Funktioner

- Konto med registrering, inloggning och utloggning.
- Vapenregister med tillverkare, modell, kaliber, serienummer, vapenklass och anteckningar.
- Skjutpass med datum, plats, disciplin, avstånd, vapen och anteckningar.
- Förvalda discipliner med fritextstöd och tidigare använda discipliner som förslag.
- Snabb registrering av serier med knappar för `X`, `10` till `0`.
- Automatisk summering av poäng, antal X och antal skott.
- Märkesplupp för brons, silver och guldserie i 5-skotts precision enligt pistolskyttemärket. Om födelsedatum finns i profilen räknas ålder automatiskt för reducerade krav.
- Ångra senaste skott innan serien sparas.
- Dashboard med senaste pass, senaste serie, antal vapen, snitt för senaste 10 serier och bästa serie.
- Historik med filter på vapen och datumintervall.
- Statistik med totalsiffror, snitt per vapen, utvecklingsgraf och poängfördelning.
- CSV-export av sparade resultat.
- Grundläggande PWA-stöd via manifest och service worker.

## Teknisk översikt

Projektet är byggt med PHP, MySQL och vanlig JavaScript/CSS utan större ramverk.

```text
includes/            Gemensamma PHP-filer för databas, autentisering, layout och hjälpfunktioner
public/              Publika sidor, assets och API-endpoints
public/assets/       CSS och JavaScript för gränssnittet
public/api/          JSON-endpoints för att spara serier och hämta statistik
sql/schema.sql       Databasschema
```

Statistikgraferna använder Chart.js från CDN.

## Databas

Databasen heter som standard `shooting_log` och innehåller tabeller för:

- `users`: användarkonton och valfritt födelsedatum för automatisk åldersberäkning.
- `weapons`: användarens registrerade vapen.
- `shooting_sessions`: skjutpass.
- `series`: sparade serier med skottlista, totalpoäng, antal X och antal skott.

Databasanslutningen konfigureras i `includes/db.php`.
För befintliga databaser finns migrationerna i `sql/migrations/`.
Adminrollen aktiveras genom att sätta `users.is_admin = 1` för kontot som ska administrera appen, till exempel:

```sql
UPDATE users SET is_admin = 1 WHERE username = 'admin';
```

## Kom igång lokalt

Krav:

- PHP med PDO MySQL-stöd.
- MySQL eller MariaDB.
- En webbläsare.

Skapa databasen om den inte redan finns:

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS shooting_log CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Kontrollera sedan databasinställningarna i `includes/db.php`:

```php
$db_host = '127.0.0.1';
$db_name = 'shooting_log';
$db_user = 'root';
$db_pass = '';
$db_table_prefix = '';
```

Om flera appar delar samma databas kan tabellerna prefixas, till exempel:

```php
$db_table_prefix = 'firelog_';
```

Vid manuell import måste schemat använda samma prefix på tabellnamnen, till exempel `firelog_users`, `firelog_weapons`, `firelog_shooting_sessions` och `firelog_series`.

När appen öppnas skapas saknade tabeller automatiskt i den konfigurerade databasen. `sql/schema.sql` finns kvar som referens eller för manuell import.

Starta en lokal PHP-server från projektroten:

```bash
php -S localhost:8000 -t public
```

Öppna sedan:

```text
http://localhost:8000
```

## Typiskt arbetsflöde

1. Skapa ett konto eller logga in.
2. Lägg till minst ett vapen.
3. Skapa ett nytt skjutpass.
4. Registrera skotten i en serie.
5. Spara serien och fortsätt med nästa.
6. Följ resultat i dashboard, historik och statistik.

## Export

På statistiksidan finns en länk för att exportera sparade resultat som CSV. Exporten innehåller bland annat datum, plats, disciplin, avstånd, vapen, kaliber, klass, serienummer, serie, skott, totalpoäng, antal X och antal skott.

## Säkerhet och avgränsningar

Appen separerar data per inloggad användare och lösenord sparas som hashade värden. Den är främst tänkt som en enkel personlig skjutdagbok och bör kompletteras med produktionsanpassad konfiguration, HTTPS, säkra databasuppgifter och backup-rutiner innan den används skarpt.
