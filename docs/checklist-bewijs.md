# Bewijs bij de zelfchecklist KT1-W3 (Realisatie)

Student: Dasalio (2211524) · Project: StreetEats · GitHub: https://github.com/DasalioK/streeteats

Per checklistpunt staat hier waar het bewijs te vinden is.

| # | Checklistpunt | Bewijs |
|---|---|---|
| 1 | Genoeg geplande functies gebouwd binnen de tijd en omvang | Alle 16 functionele en 8 technische eisen uit de planning zijn gebouwd: `docs/koppeltabel.md`. Gebouwd in 8 werkdagen (29-09 t/m 07-10-2026), binnen de afbakening van de planning. |
| 2 | Elke functie → eis, ontwerp en planningstaak | `docs/koppeltabel.md`: per eis (FE-01 t/m FE-16, TE-01 t/m TE-08) het hoofdstuk in het ontwerp, de taak (T-xx), het bestand en de test. Bovenaan elk PHP-bestand staat ook welk scherm uit het ontwerp en welke eisen erbij horen. |
| 3 | Software start en is te gebruiken; video | Installatie: `README.md`. Online: de link naar de Plesk-omgeving (wordt toegevoegd op dag 9). Video: max. 3 minuten en 200 MB (volgens de inleveropdracht). |
| 4 | Functies werken volgens de eisen | `docs/testrapport.md`: 149 tests, allemaal geslaagd, plus handmatige tests op telefoon, tablet en computer. |
| 5 | Verschillen beschreven en uitgelegd | `docs/verschillen.md`: 12 verschillen met reden, plus wat er onderweg is aangepast. |
| 6 | Logische indeling, duidelijke namen | Elk scherm uit het ontwerp is één bestand (`index.php`, `truck.php`, `login.php`, `beheer/trucks.php` …). Gedeelde code staat in `includes/`. Namen zijn steeds Nederlands en beschrijven wat iets doet (`vereis_rol`, `controleer_stop`, `verlopen_vergunningen`). |
| 7 | Duidelijke onderdelen, weinig dubbele code | Eén databaseverbinding (`includes/database.php`), één layout (`header.php`, `footer.php`), één plek voor de planningsregels (`includes/controles.php`), één plek voor rechten (`includes/login.php`) en één voor meldingen en controles (`includes/functies.php`). |
| 8 | Invoer gecontroleerd, fouten afgehandeld, gegevens betrouwbaar | Elke invoer wordt in PHP gecontroleerd (`docs/testrapport.md`, foute invoer). Technische fouten → algemene melding en logbestand (commit dag 7). Foreign keys en `ON DELETE CASCADE` in `database/streeteats.sql`. Truck of plek in gebruik kan niet worden verwijderd. |
| 9 | Passend beveiligd | Wachtwoorden met `password_hash`/`password_verify`; prepared statements tegen SQL-injectie; `e()` tegen XSS; `vereis_rol()` op elke beheerpagina; CSRF-code op elk formulier; `config.php` staat niet in Git; mappen `includes/` en `database/` zijn afgeschermd. Getest: `docs/testrapport.md`, rechten en beveiliging. |
| 10 | Eén centrale repository, definitieve versie duidelijk | https://github.com/DasalioK/streeteats (openbaar). Definitieve versie: branch `main`, met tag `v1.0` (wordt gezet op dag 9, nadat de site op Plesk werkt). |
| 11 | Commits verspreid, stap voor stap gebouwd en aangepast | Commits op 29-09, 30-09, 02-10, 04-10, 05-10, 06-10 en 07-10-2026. Elke dag één onderdeel; `fix:`-commits laten de aanpassingen zien. Zie het overzicht onderaan `docs/koppeltabel.md`. |
| 12 | Duidelijke commitberichten, branches/merges | Elk bericht begint met `feat:`, `fix:` of `docs:` en noemt de eis. Elke dag een eigen branch (`dag-2-inloggen` t/m `dag-8-afronding`), samengevoegd in `main` met een merge. |
