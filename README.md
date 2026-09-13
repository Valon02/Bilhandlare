# Bilhandlare

Ett PHP-projekt där bilannonser hämtas från Bilweb och sparas i en MySQL-databas.

Projektet innehåller en scraper som hämtar information om bilar och en söksida där användaren kan söka och filtrera bland annonserna.

## Live-version

https://bilhandlare.xo.je

## Funktioner

- Scrapar över 1000 annonser från Bilweb
- Sparar annonserna i MySQL
- Förhindrar dubletter med unikt vehicle_id
- Hämtar registreringsnummer och beskrivning från annonsernas detaljsidor
- AJAX-baserad sökning utan att sidan behöver laddas om
- Sökning på märke, modell och registreringsnummer
- Filter för märke, modell, årsmodell, miltal, bränsle och växellåda
- Sortering på pris och miltal
- Visar 25 annonser åt gången med möjlighet att visa fler
- Dynamiska antal i filtren

Databasen innehåller 2344 unika bilannonser.

## Teknik

- PHP
- MySQL
- HTML
- JavaScript för AJAX
- Bootstrap
- cURL
- DOMDocument och XPath

## Filer

- `index.php` - sidans gränssnitt och AJAX-anrop
- `search.php` - söker, sorterar och hämtar bilar från databasen
- `filters.php` - hämtar filteralternativ och antal
- `filter_helpers.php` - gemensamma funktioner för sökning och filter
- `scraper.php` - hämtar bilannonser från Bilweb
- `update_details.php` - hämtar registreringsnummer och beskrivning från varje annons
- `db.php` - skapar anslutningen till databasen

## Databas

Tabellen `cars` innehåller bland annat:

- märke
- modell
- årsmodell
- registreringsnummer
- pris
- miltal
- bränsle
- växellåda
- beskrivning
- länk till originalannonsen
- Bilwebs vehicle_id

`vehicle_id` är unikt och används för att undvika att samma annons sparas flera gånger.

## Databasinställningar

Databasuppgifter ligger i en separat `db_config.php` som inte laddas upp till GitHub.

En lokal eller egen konfigurationsfil behöver därför skapas för att köra projektet med en egen databas.
