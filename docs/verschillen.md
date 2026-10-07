# Verschillen tussen eisen, ontwerp en product

Hoort bij checklistpunt 5: *"Verschillen tussen de eisen, het ontwerp en het opgeleverde product zijn duidelijk beschreven en uitgelegd."*

Bronnen: Planning & voortgang (KT1-W1), Technisch ontwerp (KT1-W2) en de opgeleverde applicatie (versie `v1.0`).

| # | Waar | Eis of ontwerp | Wat ik heb gebouwd | Waarom |
|---|---|---|---|---|
| 1 | Planning ↔ ontwerp | De planning heeft 16 functionele en 8 technische eisen (FE-01 t/m FE-16, TE-01 t/m TE-08). | Het ontwerp heeft er 10 en 5 (FE1 t/m FE10, TE1 t/m TE5). Alle eisen uit de planning zijn gebouwd. | In het ontwerp heb ik eisen samengevoegd om het overzichtelijk te houden, bijvoorbeeld trucks en plekken beheren samen als FE4. De koppeling staat in `docs/koppeltabel.md`. |
| 2 | ERD (ontwerp H7) | Tabel `locations` zonder plaats. | Extra kolom `city` (plaats). | Zonder plaats kan een bezoeker niet op plaats zoeken (FE-01 / FE1). Zoeken in het adres zou onbetrouwbaar zijn. |
| 3 | ERD (ontwerp H7) | Tabel `users` zonder status. | Extra kolom `actief` (1 = actief, 0 = geblokkeerd). | Het ontwerp (H5, scherm Accounts) zegt dat de beheerder accounts kan blokkeren. Daarvoor moet ergens staan of een account geblokkeerd is. |
| 4 | Rollen | De planning (RV-04) noemt bezoekers en planners. | Drie rollen: bezoeker, planner en beheerder. | Het technisch ontwerp (H6) voegt de beheerder toe, die accounts van planners maakt en blokkeert. Het ontwerp is later gemaakt en is gevolgd. |
| 5 | Routestatus | De planning spreekt van "bevestigd" (FE-12, FE-13). | De status heet `goedgekeurd`. | Het ontwerp gebruikt het woord "goedkeuren". Het betekent hetzelfde: pas daarna zien bezoekers de route. |
| 6 | Routes | Het ontwerp kent "concept" en "goedgekeurd". | Extra knop "Terug naar concept". | Zonder deze knop kan een goedgekeurde route nooit meer worden aangepast. De route is daarna meteen niet meer zichtbaar voor bezoekers, en moet opnieuw alle controles doorlopen bij goedkeuren. |
| 7 | Controles bij een stop | De planning (FE-10) vraagt alleen een controle op overlap. | Ook een controle op "plek is dicht" (buiten de openingstijden). | Dat staat in het ontwerp (H4 en H9: "Truck staat dubbel of plek is dicht → route wordt niet opgeslagen"). |
| 8 | Dagagenda | De planning noemt een "openbare dagagenda" (afbakening, T-28). | Geen aparte agendapagina. De startpagina toont per datum alle goedgekeurde foodtrucks, en de truckpagina toont per dag plek, tijden, openingstijden en menu. | Het technisch ontwerp (H5) heeft alleen een Startpagina en een Truckpagina voor bezoekers. Samen laten die de dagplanning zien, zoals het resultaat van T-28 vraagt: "plek, tijden en menu zijn zichtbaar". |
| 9 | Online zetten | De planning (afbakening) zegt: "Niet: openbaar online zetten". | De applicatie wordt online gezet op Plesk (dag 9). | De inleveropdracht van de docent vraagt een werkende link op de eigen Plesk-omgeving. |
| 10 | Beveiliging (ontwerp H11) | "HTTPS gebruiken." | Lokaal (XAMPP) zonder HTTPS. Op Plesk zetten we HTTPS aan met Let's Encrypt (dag 9). | Op een lokale computer is HTTPS niet nodig. Op de echte website wel. |
| 11 | Onderhoud (ontwerp H11) | Nachtelijke back-up, maandelijkse updates, wekelijks het foutenlogboek bekijken, eerst testen op een testwebsite. | Niet gebouwd in de applicatie. | Dit zijn afspraken voor het beheer na de oplevering, geen functies van de applicatie. De voorbereiding is er wel: fouten komen in het logbestand (zie dag 7) en de testdata is opnieuw te importeren. |
| 12 | Extra uit het ontwerp | Ontwerp H11: "Indexes op datum en plaats en resultaten per 12 tonen." | Gebouwd: indexes op `routes.route_date` en `locations.city`, en 12 resultaten per pagina met pagina-knoppen. | Geen verschil. Ik noem het hier omdat het niet in de planning stond, maar wel in het ontwerp. |

## Wat ik onderweg heb aangepast

| Wanneer | Wat | Waarom |
|---|---|---|
| Dag 4 | Testdata: de Grote Markt is elke dag open. | Volgens de testdata stond op zondag een truck op de Grote Markt, terwijl die plek dan dicht was. Mijn eigen controle zou dat weigeren. |
| Dag 7 | Technische fouten tonen een algemene melding. | Een bezoeker zag eerst de technische foutmelding, met tabelnamen. Dat is in strijd met ontwerp H9. |
| Dag 7 | Menu past op telefoon en tablet. | Het menu was op een telefoon een half scherm hoog en stak op een tablet buiten beeld. |
| Dag 8 | Openingstijden op de truckpagina, en gerechten wijzigen. | Bij het vergelijken met de planning zag ik dat FE-02 (openingstijden) en FE-14 ("past aan") nog niet helemaal klopten. |
