<?php
/*
 * INLOGGEN EN RECHTEN (module Login)
 * Hoort bij: TE5 (login en rechten), TE4 (veilig opslaan)
 *           Technisch ontwerp H6 (rollen en rechten)
 *
 * Uitleg:
 * - Bezoekers loggen niet in.
 * - Planners en beheerders loggen in met e-mail en wachtwoord.
 * - Elke beheerpagina begint met bijv.  vereis_rol(['planner', 'beheerder']);
 *   Een knop verbergen is niet genoeg: PHP controleert het bij ELKE pagina en actie opnieuw.
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functies.php';

/*
 * Geeft de ingelogde gebruiker terug (als array), of null als er niemand is ingelogd.
 * We halen de gebruiker elke keer opnieuw uit de database. Is het account
 * intussen geblokkeerd (actief = 0), dan is de gebruiker meteen niet meer ingelogd.
 */
function ingelogde_gebruiker()
{
    global $db;

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $query = $db->prepare('SELECT id, naam, email, role FROM users WHERE id = ? AND actief = 1');
    $query->execute([$_SESSION['user_id']]);

    return $query->fetch() ?: null;
}

/*
 * Laat alleen gebruikers met een van deze rollen door.
 * - Niet ingelogd? Dan naar de loginpagina.
 * - Wel ingelogd maar verkeerde rol? Dan "geen toegang" en stoppen.
 */
function vereis_rol($rollen)
{
    $gebruiker = ingelogde_gebruiker();

    if ($gebruiker === null) {
        zet_melding('fout', 'Log eerst in om deze pagina te bekijken.');
        ga_naar('login.php');
    }

    if (!in_array($gebruiker['role'], $rollen, true)) {
        http_response_code(403);
        $titel = 'Geen toegang';
        require __DIR__ . '/header.php';
        echo '<p class="melding melding-fout"><strong>Fout:</strong> Je hebt geen toegang tot deze pagina.</p>';
        require __DIR__ . '/footer.php';
        exit;
    }

    return $gebruiker;
}
