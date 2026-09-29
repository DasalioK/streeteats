<?php
/*
 * HULPFUNCTIES
 * Kleine functies die op veel pagina's nodig zijn. Ze staan hier één keer,
 * zodat we niets dubbel hoeven te schrijven.
 */

require_once __DIR__ . '/config.php';

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
