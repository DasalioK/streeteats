# Koppeltabel: eis → ontwerp → planning → code → test

Hoort bij checklistpunt 2: *"Bij elke gebouwde functie is duidelijk bij welke eis, welk deel van het ontwerp en welke taak in de planning deze hoort."*

## Twee nummeringen, één project

| Document | Eisen | Taken |
|---|---|---|
| Planning & voortgang (KT1-W1, 10-09-2026) | FE-01 t/m FE-16, TE-01 t/m TE-08 | T-01 t/m T-35 (46 uur) |
| Technisch ontwerp (KT1-W2, 17-09-2026) | FE1 t/m FE10, TE1 t/m TE5 | — |

In het technisch ontwerp heb ik eisen uit de planning samengevoegd, zodat het ontwerp overzichtelijk bleef. Zo zijn FE-05 (trucks) en FE-06 (plekken) samen FE4 geworden. Hieronder staat per eis uit de planning waar die in het ontwerp staat, in welke planningstaak hij is gebouwd, in welk bestand de code staat en met welke test hij is gecontroleerd. De testnummers verwijzen naar `docs/testrapport.md`.

## Functionele eisen

| Eis (planning) | Wat | Ontwerp | Taak | Code | Test | Gebouwd |
|---|---|---|---|---|---|---|
| FE-01 | Bezoeker zoekt op datum, plaats en soort eten | FE1 · H5 Startpagina · H11 (12 per pagina, indexes) | T-27 | `index.php` | T6 | dag 6 |
| FE-02 | Bezoeker bekijkt plek en openingstijden | FE2 · H5 Truckpagina | T-28 | `truck.php` | T6, T8 | dag 6 en 8 |
| FE-03 | Bezoeker bekijkt het dagmenu | FE2 · H5 Truckpagina | T-25, T-28 | `truck.php` | T6 | dag 6 |
| FE-04 | Bezoeker ziet uitverkochte items | FE3 | T-26 | `truck.php`, `beheer/menu.php` | T5, T6 | dag 5 en 6 |
| FE-05 | Planner beheert foodtrucks | FE4 · H5 Trucks | T-16 | `beheer/trucks.php` | T3 | dag 3 |
| FE-06 | Planner beheert plekken | FE4 · H5 Locaties | T-17 | `beheer/plekken.php` | T3 | dag 3 |
| FE-07 | Planner beheert openingstijden | H4 module Locaties · H5 Locaties | T-18 | `beheer/plekken.php` | T3 | dag 3 |
| FE-08 | Planner maakt route met meerdere stops | FE5 · H5 Routes · H8 | T-20, T-21, T-22 | `beheer/routes.php`, `beheer/route.php` | T4 | dag 4 |
| FE-09 | Planner slaat vergunning en einddatum op | H4 module Locaties · H5 Locaties | T-19 | `beheer/plekken.php` | T3 | dag 3 |
| FE-10 | Overlappende stops worden tegengehouden | FE6 · H8 · H9 | T-23 | `includes/controles.php` (`controleer_stop`) | T4 | dag 4 |
| FE-11 | Verlopen vergunning: melding of blokkade | FE7 · H9 | T-19, T-24 | `includes/controles.php` (`verlopen_vergunningen`), `beheer/route.php`, `beheer/index.php` | T3, T5 | dag 3 en 5 |
| FE-12 | Planner bevestigt (keurt goed) een route | FE8 · H10 "route eerst als concept" | T-24 | `beheer/route.php`, `includes/controles.php` (`redenen_niet_goedkeuren`) | T5 | dag 5 |
| FE-13 | Bezoekers zien alleen bevestigde routes | FE8 · H6 | T-24, T-28 | `index.php`, `truck.php` | T6 | dag 6 |
| FE-14 | Planner maakt en past een dagmenu aan | FE9 · H5 Menu | T-25 | `beheer/menu.php` | T5, T8 | dag 5 en 8 |
| FE-15 | Alleen functies die bij je rol horen | TE5 · H6 Rollen en rechten | T-15, T-32 | `includes/login.php` (`vereis_rol`), `beheer/accounts.php` | T2 + alle tests "gast/planner" | dag 2 |
| FE-16 | Meldingen bij succes, fout, leeg, verboden | FE10 · H9 | T-29 | `includes/functies.php` (`zet_melding`, `toon_melding`), alle pagina's | T2 t/m T7 | dag 2 t/m 7 |

## Technische eisen

| Eis (planning) | Wat | Ontwerp | Taak | Code | Test | Gebouwd |
|---|---|---|---|---|---|---|
| TE-01 | PHP 8 of hoger | TE1 | T-12 | hele project (getest op PHP 8.2.12) | T1 | dag 1 |
| TE-02 | MySQL/MariaDB | TE2 · H7 ERD | T-13, T-14 | `database/streeteats.sql`, `includes/database.php` | T1 | dag 1 |
| TE-03 | Wachtwoorden veilig gehasht | TE4 · H11 | T-15, T-30 | `login.php` (`password_verify`), `beheer/accounts.php` (`password_hash`) | T2 | dag 2 |
| TE-04 | Formulieren controleren lege en foute invoer | TE4 · H9 | T-29, T-30 | alle formulieren, `includes/functies.php` (`is_geldige_datum`, `is_geldige_tijd`) | T2 t/m T8 | dag 2 t/m 8 |
| TE-05 | Invoer veilig op de pagina tonen | H11 Beveiliging | T-30 | `includes/functies.php` (`e()`) | T2, T3, T5, T6 (XSS) | dag 1 |
| TE-06 | Pagina's en acties gecontroleerd op rechten | TE5 · H6 | T-15, T-32 | `includes/login.php` (`vereis_rol`), CSRF in `includes/functies.php` | T2 t/m T5 | dag 2 |
| TE-07 | Werkt op telefoon, tablet en computer | TE3 | T-11, T-30 | `css/style.css`, `includes/header.php` | T7 | dag 1, 6 en 7 |
| TE-08 | Duidelijke en nette code | H4 Opbouw · H10 | T-33 | indeling: één bestand per scherm, gedeelde code in `includes/` | code-review | alle dagen |

## Planningstaken die niet in code zitten

| Taak | Wat | Waar |
|---|---|---|
| T-01 t/m T-06 | Analyse en eisen | Planning (KT1-W1) |
| T-07 t/m T-11 | Database, ERD, schermen, mobiel ontwerp | Technisch ontwerp (KT1-W2) |
| T-30 t/m T-34 | Testen, fouten oplossen, eindtest | `docs/testrapport.md`, de `fix:`-commits van dag 4 en 7 |
| T-35 | Documentatie | `README.md` en de map `docs/` |

## Commits per dag (GitHub: github.com/DasalioK/streeteats)

| Dag | Datum | Commits | Taken |
|---|---|---|---|
| 1 | 29-09-2026 | `feat: projectbasis, database met testdata en layout` | T-12, T-13, T-14 |
| 2 | 30-09-2026 | `feat: inloggen, rollen en accountbeheer` | T-15 |
| 3 | 02-10-2026 | `feat: foodtrucks en plekken beheren met openingstijden en vergunning` | T-16 t/m T-19 |
| 4 | 04-10-2026 | `fix: Grote Markt elke dag open in testdata` · `feat: routes met meerdere stops, dubbele planning en dichte plek blokkeren` | T-20 t/m T-23, T-33 |
| 5 | 05-10-2026 | `feat: vergunning controleren en route goedkeuren` · `feat: menu per dag beheren en uitverkocht zetten` | T-19, T-24, T-25, T-26 |
| 6 | 05-10-2026 | `feat: indexes op datum en plaats` · `feat: zoeken voor bezoekers en truckpagina met menu` | T-27, T-28 |
| 7 | 06-10-2026 | 2× `fix:` (foutafhandeling, mobiel) · `docs:` | T-29 t/m T-33 |
| 8 | 07-10-2026 | `feat:` openingstijden op truckpagina · `feat:` gerecht wijzigen · `docs:` documentatie | T-28, T-25, T-34, T-35 |
