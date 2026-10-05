<?php
/*
 * CONTROLES VAN DE PLANNING (module Routes)
 * Hoort bij: FE6 (dubbele planning blokkeren) en technisch ontwerp H4, H8 en H9
 *
 * Uitleg:
 * Voordat een stop wordt opgeslagen, controleert PHP twee regels uit het ontwerp:
 *   1. Staat de truck niet dubbel? Een truck kan niet op twee plekken tegelijk staan.
 *   2. Is de plek open? De stop moet binnen de openingstijden van de plek vallen.
 * Gaat een regel fout, dan wordt de stop NIET opgeslagen en ziet de planner
 * welke truck en welke tijden het zijn (ontwerp H9).
 */

require_once __DIR__ . '/functies.php';

/*
 * Controleert een stop en geeft een lijst met foutmeldingen terug.
 * Lege lijst = alles in orde, de stop mag worden opgeslagen.
 *
 * $negeerStopId: bij het wijzigen van een bestaande stop slaan we die stop zelf over,
 * anders zou hij met zichzelf "botsen".
 */
function controleer_stop($db, $datum, $truckId, $plekId, $begin, $eind, $negeerStopId = 0)
{
    $fouten = [];

    // ---- Regel 1: staat de truck al ergens anders op hetzelfde moment? ----
    // Twee tijden overlappen als: nieuwe begin < andere eind  EN  nieuwe eind > andere begin.
    // Voorbeeld: 11:00-14:00 en 13:00-15:00 overlappen (13:00 ligt vóór 14:00).
    // Aansluitend, zoals 11:00-14:00 en daarna 14:00-16:00, mag wel.
    $query = $db->prepare(
        'SELECT stops.start_time, stops.end_time, locations.naam AS plek, trucks.naam AS truck
         FROM stops
         JOIN routes    ON routes.id = stops.route_id
         JOIN locations ON locations.id = stops.location_id
         JOIN trucks    ON trucks.id = stops.truck_id
         WHERE stops.truck_id = ?
           AND routes.route_date = ?
           AND stops.start_time < ?
           AND stops.end_time > ?
           AND stops.id <> ?'
    );
    $query->execute([$truckId, $datum, $eind, $begin, $negeerStopId]);

    foreach ($query->fetchAll() as $botsing) {
        $fouten[] = $botsing['truck'] . ' staat al op ' . $botsing['plek'] . ' van '
            . tijd($botsing['start_time']) . ' tot ' . tijd($botsing['end_time'])
            . '. Een truck kan niet op twee plekken tegelijk staan.';
    }

    // ---- Regel 2: is de plek open op die dag en die tijden? ----
    // date('N') geeft de dag van de week: 1 = maandag ... 7 = zondag (net als in de tabel opening_hours)
    $dag = (int) date('N', strtotime($datum));

    $query = $db->prepare(
        'SELECT locations.naam, opening_hours.open_time, opening_hours.close_time
         FROM locations
         LEFT JOIN opening_hours ON opening_hours.location_id = locations.id AND opening_hours.day = ?
         WHERE locations.id = ?'
    );
    $query->execute([$dag, $plekId]);
    $plek = $query->fetch();

    if ($plek['open_time'] === null) {
        // Geen openingstijd voor deze dag = de plek is dicht
        $fouten[] = $plek['naam'] . ' is op ' . dagnaam($dag) . ' dicht.';
    } elseif ($begin < tijd($plek['open_time']) || $eind > tijd($plek['close_time'])) {
        $fouten[] = $plek['naam'] . ' is op ' . dagnaam($dag) . ' alleen open van '
            . tijd($plek['open_time']) . ' tot ' . tijd($plek['close_time']) . '.';
    }

    return $fouten;
}

/*
 * VERGUNNINGEN VAN EEN ROUTE (FE7)
 * Geeft de namen terug van de plekken in deze route waarvan de vergunning
 * op de routedatum verlopen is. Lege lijst = alle vergunningen zijn geldig.
 *
 * Ontwerp H9: een route met een verlopen vergunning wordt WEL opgeslagen (met een waarschuwing),
 * maar kan NIET worden goedgekeurd.
 */
function verlopen_vergunningen($db, $route)
{
    $query = $db->prepare(
        'SELECT DISTINCT locations.naam, locations.permit_end_date
         FROM stops
         JOIN locations ON locations.id = stops.location_id
         WHERE stops.route_id = ?'
    );
    $query->execute([$route['id']]);

    $verlopen = [];
    foreach ($query->fetchAll() as $plek) {
        if (vergunning_verlopen($plek['permit_end_date'], $route['route_date'])) {
            $verlopen[] = $plek['naam'];
        }
    }
    return $verlopen;
}

/*
 * MAG DEZE ROUTE WORDEN GOEDGEKEURD? (FE8)
 * Geeft een lijst met redenen waarom het (nog) niet mag. Lege lijst = goedkeuren mag.
 *
 * We controleren bij het goedkeuren ALLES opnieuw. Er kan sinds het opslaan namelijk
 * iets veranderd zijn, bijvoorbeeld een andere route met dezelfde truck, of een vergunning
 * die intussen is verlopen.
 */
function redenen_niet_goedkeuren($db, $route)
{
    $redenen = [];

    $query = $db->prepare('SELECT * FROM stops WHERE route_id = ?');
    $query->execute([$route['id']]);
    $stops = $query->fetchAll();

    // Een lege route heeft geen zin voor bezoekers
    if (!$stops) {
        return ['De route heeft nog geen stops.'];
    }

    // Elke stop opnieuw controleren op dubbele planning en openingstijden (FE6)
    foreach ($stops as $stop) {
        $fouten = controleer_stop($db, $route['route_date'], $stop['truck_id'], $stop['location_id'], tijd($stop['start_time']), tijd($stop['end_time']), $stop['id']);
        $redenen = array_merge($redenen, $fouten);
    }

    // Vergunningen controleren (FE7)
    foreach (verlopen_vergunningen($db, $route) as $plek) {
        $redenen[] = 'De vergunning van ' . $plek . ' is verlopen. De route kan niet worden goedgekeurd.';
    }

    // array_unique: dezelfde melding maar één keer tonen
    return array_values(array_unique($redenen));
}
