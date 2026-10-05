# StreetEats

Webapplicatie voor foodtruckcollectief **Rolling Flavours**. Planners maken de planning van de foodtrucks; bezoekers zoeken zonder in te loggen waar een truck staat en wat er op het menu staat.

Student: Dasalio (2211524) · MBO 4 Software Developer · KT1-W3 Realisatie

## Techniek

- PHP 8+ (getest met 8.2), zonder framework
- MySQL / MariaDB
- HTML, CSS en een beetje JavaScript

## Installeren (XAMPP)

1. Zet de map `streeteats` in `htdocs`.
2. Start **Apache** en **MySQL** in het XAMPP Control Panel.
3. Open phpMyAdmin → tabblad **Importeren** → kies `database/streeteats.sql` → **Start**.
4. Kopieer `includes/config.example.php` naar `includes/config.php` en pas zo nodig de gegevens aan.
5. Open <http://localhost/streeteats/>.

## Testaccounts

Alleen voor testen, geen echte personen. Wachtwoord voor allebei: `demo-password`

| E-mail | Rol |
|---|---|
| beheerder@example.com | beheerder |
| planner@example.com | planner |

## Voortgang

| Dag | Onderdeel | Eisen |
|---|---|---|
| 1 | Projectbasis, database met testdata, layout | TE1, TE2 |
| 2 | Inloggen, uitloggen, rollen, accounts voor de beheerder | TE4, TE5 |
| 3 | Foodtrucks en plekken beheren, openingstijden, vergunningen | FE4, FE7 |
| 4 | Routes met meerdere stops, dubbele planning en dichte plek blokkeren | FE5, FE6 |
| 5 | Vergunning-waarschuwing, route goedkeuren, menu per dag met uitverkocht | FE7, FE8, FE9, FE3 |
| 6 | Zoeken voor bezoekers (12 per pagina), truckpagina met menu | FE1, FE2, FE3, FE8 |
