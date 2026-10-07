# Testrapport StreetEats

Hoort bij checklistpunt 4 (functies werken volgens de eisen), 8 (invoer en foutafhandeling) en 9 (beveiliging), en bij planningstaken T-30 t/m T-34.

## Hoe is er getest?

- **Automatisch:** na elke bouwdag heb ik testscripts gedraaid die de website echt gebruiken, zoals een browser dat doet: pagina's openen, formulieren versturen en inloggen met de testaccounts. Daarna controleert het script de pagina en de database. Vóór elke test wordt de testdata opnieuw geïmporteerd, zodat elke test met dezelfde gegevens begint.
- **Regressietest:** na elke wijziging zijn de tests van **alle eerdere dagen** opnieuw gedraaid. Zo weet ik zeker dat een verbetering niets anders kapot heeft gemaakt.
- **Handmatig:** in de browser op telefoonformaat (375 px), tabletformaat (768 px) en computerformaat.
- **Omgeving:** Windows 11, XAMPP, PHP 8.2.12, MariaDB 10.4.32.

**Eindtest (T-34) op 07-10-2026: 149 tests, 149 geslaagd, 0 mislukt.**

## Resultaten per testronde

| Ronde | Onderdeel | Eisen (planning) | Tests | Resultaat |
|---|---|---|---|---|
| T1 | PHP-versie en databaseverbinding, 7 tabellen, 6 foreign keys | TE-01, TE-02 | handmatig + `index.php` | geslaagd |
| T2 | Inloggen, rollen, accounts | FE-15, FE-16, TE-03 t/m TE-06 | 22 | 22 geslaagd |
| T3 | Foodtrucks, plekken, openingstijden, vergunning | FE-05, FE-06, FE-07, FE-09, FE-11 | 25 | 25 geslaagd |
| T4 | Routes, stops, volgorde, dubbele planning, dichte plek | FE-08, FE-10 | 30 | 30 geslaagd |
| T5 | Vergunning bij goedkeuren, goedkeuren, menu, uitverkocht | FE-04, FE-11, FE-12, FE-14 | 24 | 24 geslaagd |
| T6 | Zoeken (12 per pagina), truckpagina, alleen goedgekeurd zichtbaar | FE-01 t/m FE-04, FE-13 | 27 | 27 geslaagd |
| T7 | Foutafhandeling (technische fout), responsive | FE-16, TE-07 | 10 + handmatig | 10 geslaagd |
| T8 | Openingstijden op truckpagina, gerecht wijzigen | FE-02, FE-14 | 11 | 11 geslaagd |
| **Totaal** | | | **149** | **149 geslaagd** |

## Wat er getest is (per soort)

### Normale invoer: werkt de functie?
- Truck, plek, openingstijd, route, stop en gerecht toevoegen, wijzigen en verwijderen, en daarna controleren in de database.
- Route met drie stops in de juiste volgorde; stop omhoog en omlaag; na verwijderen wordt de volgorde opnieuw genummerd (1, 2, 3).
- Route goedkeuren → zichtbaar voor bezoekers. Terug naar concept → niet meer zichtbaar.
- Zoeken op datum, op plaats ("Haarlem"), op plek ("museum", ook met kleine letters) en op soort eten.
- 18 resultaten → pagina 1 toont er 12, pagina 2 toont er 6.
- Gerecht voor morgen staat alleen op het menu van morgen.

### Foute invoer: krijgt de gebruiker een duidelijke melding?
- Lege velden → "Dit veld is verplicht." bij het juiste veld; het formulier blijft ingevuld.
- Te lange tekst, datum 30 februari, tijd 25:00, eindtijd vóór begintijd, prijs "abc" of 0, ongeldig e-mailadres, wachtwoord korter dan 8 tekens, dubbel e-mailadres.
- Rare waarden in de link (`?datum=hallo`, `?truck=abc`, `?pagina=99`) → geen crash, een logische standaardwaarde.
- Een truck of plek die nog in een route staat, kan niet worden verwijderd.

### Regels van de planning (FE-10, FE-11)
- Overlap (13:00–15:00 terwijl de truck 11:00–14:00 al op de Dam staat) → geweigerd, met de melding "Burger Beast staat al op Dam van 11:00 tot 14:00…".
- Aansluitende tijden (14:00 na 11:00–14:00) → toegestaan.
- Buiten de openingstijden, of op een dag dat de plek dicht is → geweigerd.
- Route met de verlopen vergunning van NDSM → waarschuwing; goedkeuren lukt niet.
- Overlap die later is ontstaan (via een andere route) → bij goedkeuren toch gevonden.
- Route zonder stops → kan niet worden goedgekeurd.

### Rechten en beveiliging (FE-15, TE-03 t/m TE-06)
- Een bezoeker opent een beheerpagina → doorgestuurd naar inloggen. Een bezoeker verstuurt een beheerformulier → geweigerd, er verandert niets.
- Een planner opent Accounts → "Je hebt geen toegang tot deze pagina." (status 403).
- Een geblokkeerde planner verliest meteen de toegang en kan niet meer inloggen. De beheerder kan zichzelf niet blokkeren.
- Wachtwoord staat in de database als bcrypt-hash (`$2y$…`), niet leesbaar.
- SQL-injectie (`' OR '1'='1`) bij inloggen en zoeken → werkt niet.
- XSS (`<script>alert(1)</script>`) in een naam, gerecht of zoekveld → wordt als gewone tekst getoond.
- Formulier zonder of met een nep-CSRF-code → geweigerd (403).
- Een stop of gerecht van een andere route of truck aanpassen via een zelfgemaakt formulier → geweigerd.
- `includes/config.php` en `database/streeteats.sql` opvragen via de browser → geweigerd (403).
- Databasefout nagebootst → de bezoeker ziet "Er is iets misgegaan. Probeer het later opnieuw.", en de details staan in het logbestand.

### Responsive (TE-07)
| Scherm | Getest | Resultaat |
|---|---|---|
| Telefoon (375 px) | startpagina, truckpagina, inloggen, dashboard, routes, plekken | Alles onder elkaar, menu 149 px hoog, pagina niet breder dan het scherm. Brede tabellen scrollen binnen hun eigen kader. |
| Tablet (768 px) | dashboard, menu | Menu past (eerst niet, opgelost op dag 7), resultaten in 2 kolommen |
| Computer | alle pagina's | Menu op één regel, resultaten in 3 kolommen |

## Gevonden fouten en oplossingen (T-33)

| Dag | Gevonden fout | Oplossing | Commit |
|---|---|---|---|
| 2 | Een formulier zonder CSRF-code gaf status 500 in plaats van "verboden" (Apache kent de code 419 niet). | Status 403 gebruikt. | in de commit van dag 2 |
| 4 | De testdata zette op zondag een truck op een plek die dan dicht was. | De Grote Markt is elke dag open in de testdata. | `fix: Grote Markt elke dag open in testdata` |
| 7 | Een bezoeker zag bij een databasefout technische details (tabelnamen, mappen). | `set_exception_handler()`: algemene melding op het scherm, details in het logbestand. | `fix: technische fouten tonen een algemene melding` |
| 7 | Het menu was op een telefoon 417 px hoog en stak op een tablet buiten beeld. | `flex-wrap`; nu 149 px hoog, geen scrollbalk meer. | `fix: menu past op telefoon en tablet` |
| 7 | Na een CSS-wijziging bleef de browser de oude stijl tonen. | Versienummer (`?v=`) achter `style.css`. | `fix: menu past op telefoon en tablet` |
| 8 | De truckpagina toonde geen openingstijden (FE-02), en gerechten konden niet worden gewijzigd (FE-14). | Allebei toegevoegd. | twee `feat:`-commits op dag 8 |
