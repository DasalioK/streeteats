<?php
/*
 * HULPFUNCTIES
 * Kleine functies die op veel pagina's nodig zijn. Ze staan hier één keer,
 * zodat we niets dubbel hoeven te schrijven.
 */

require_once __DIR__ . '/config.php';

/*
 * Sessie starten.
 * Een sessie onthoudt gegevens tussen pagina's, bijvoorbeeld wie er is ingelogd.
 * httponly = JavaScript kan het sessie-cookie niet lezen (veiliger).
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

/*
 * e() = "escape"
 * Maakt tekst veilig om op de pagina te tonen.
 * Als iemand bijv. <script> invult, wordt dat gewone tekst en geen code (bescherming tegen XSS).
 * Regel: alles wat uit de database of van de gebruiker komt, tonen we met e().
 */
function e($tekst)
{
    return htmlspecialchars((string) $tekst, ENT_QUOTES, 'UTF-8');
}

/*
 * url() maakt een link binnen de website.
 * Voorbeeld: url('beheer/trucks.php') wordt '/streeteats/beheer/trucks.php'
 */
function url($pad = '')
{
    global $config;
    return $config['basis_url'] . '/' . $pad;
}

/*
 * ga_naar() stuurt de gebruiker naar een andere pagina en stopt dit script.
 * We gebruiken dit na het opslaan van een formulier (zo wordt het niet dubbel verstuurd bij verversen).
 */
function ga_naar($pad)
{
    header('Location: ' . url($pad));
    exit;
}

/* ---------------------------------------------------------------------
 * INVOER CONTROLEREN (TE4)
 * --------------------------------------------------------------------- */

/* Klopt de datum echt? '2026-02-30' bestaat bijvoorbeeld niet. Formaat: jjjj-mm-dd */
function is_geldige_datum($tekst)
{
    $datum = DateTime::createFromFormat('Y-m-d', $tekst);
    return $datum && $datum->format('Y-m-d') === $tekst;
}

/* Is het een geldige tijd, zoals 09:30? (uren 00-23, minuten 00-59) */
function is_geldige_tijd($tekst)
{
    return preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $tekst) === 1;
}

/* Is de vergunning van een plek verlopen op een bepaalde datum? (FE7)
 * Een vergunning is geldig tot en met de einddatum. */
function vergunning_verlopen($einddatum, $opDatum)
{
    return $einddatum < $opDatum;
}

/* Tijd uit de database ('11:00:00') korter tonen: '11:00' */
function tijd($tekst)
{
    return substr($tekst, 0, 5);
}

/* Datum netjes tonen: '2026-10-04' wordt 'zaterdag 04-10-2026' */
function datum_tekst($datum)
{
    $tijdstip = strtotime($datum);
    return dagnaam((int) date('N', $tijdstip)) . ' ' . date('d-m-Y', $tijdstip);
}

/* Naam van de dag: 1 = maandag ... 7 = zondag */
function dagnaam($nummer)
{
    $dagen = [1 => 'maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'];
    return $dagen[$nummer] ?? '';
}

/* ---------------------------------------------------------------------
 * MELDINGEN (FE10)
 * Een melding zetten we in de sessie en tonen we op de volgende pagina.
 * Soorten: 'succes' (groen) en 'fout' (rood).
 * --------------------------------------------------------------------- */

function zet_melding($soort, $tekst)
{
    $_SESSION['melding'] = ['soort' => $soort, 'tekst' => $tekst];
}

/* Toont de melding één keer en haalt hem daarna weg. */
function toon_melding()
{
    if (empty($_SESSION['melding'])) {
        return;
    }
    $melding = $_SESSION['melding'];
    unset($_SESSION['melding']);

    // De melding heeft een kleur én een woord ervoor ("Gelukt:" / "Fout:"),
    // zodat hij ook zonder kleur te begrijpen is (toegankelijkheid, ontwerp H11).
    $woord = $melding['soort'] === 'succes' ? 'Gelukt:' : 'Fout:';
    echo '<p class="melding melding-' . e($melding['soort']) . '"><strong>' . $woord . '</strong> ' . e($melding['tekst']) . '</p>';
}

/* ---------------------------------------------------------------------
 * CSRF-BESCHERMING
 * Elk formulier krijgt een geheime code (token) die alleen deze sessie kent.
 * Bij het versturen controleren we die code. Zo kan een andere website
 * niet stiekem een formulier namens de ingelogde gebruiker versturen.
 * --------------------------------------------------------------------- */

/* Zet dit in elk formulier: <?= csrf_veld() ?> */
function csrf_veld()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">';
}

/* Roep dit aan bovenaan de verwerking van elk formulier (POST). */
function controleer_csrf()
{
    if (!isset($_POST['csrf'], $_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        http_response_code(403); // 403 = verboden
        exit('Je sessie is verlopen. Ga terug, ververs de pagina en probeer het opnieuw.');
    }
}
