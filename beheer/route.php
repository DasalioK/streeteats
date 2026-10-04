<?php
/*
 * ÉÉN ROUTE: STOPS TOEVOEGEN, VOLGORDE AANPASSEN EN VERWIJDEREN
 * Technisch ontwerp H5 (scherm Routes) en H8 (proces van een route opslaan)
 * Hoort bij: FE5 (route met meerdere stops), FE6 (dubbele planning blokkeren), TE4, TE5
 *
 * Uitleg - wat gebeurt er als de planner een stop toevoegt?
 *   1. PHP controleert of alles is ingevuld en of de tijden geldig zijn.
 *   2. controleer_stop() (includes/controles.php) kijkt of de truck niet dubbel staat
 *      en of de plek open is.
 *   3. Alleen als alles klopt, wordt de stop opgeslagen. Anders ziet de planner
 *      precies wat er mis is, en blijft het formulier ingevuld.
 * Alleen een route met de status 'concept' kan worden aangepast.
 */

require __DIR__ . '/../includes/login.php';
require __DIR__ . '/../includes/controles.php';

vereis_rol(['planner', 'beheerder']);

// ---- Route ophalen. Bestaat hij niet? Terug naar het overzicht. ----
$routeId = (int) ($_GET['id'] ?? 0);
$query = $db->prepare('SELECT * FROM routes WHERE id = ?');
$query->execute([$routeId]);
$route = $query->fetch();

if (!$route) {
    zet_melding('fout', 'Deze route bestaat niet.');
    ga_naar('beheer/routes.php');
}

$isConcept = $route['status'] === 'concept';
$fouten = [];
$stop = ['truck_id' => '', 'location_id' => '', 'start_time' => '', 'end_time' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controleer_csrf();
    $actie = $_POST['actie'] ?? '';

    // Een goedgekeurde route mag niet meer veranderen (ook niet via een zelfgemaakt formulier)
    if (!$isConcept) {
        zet_melding('fout', 'Een goedgekeurde route kan niet meer worden aangepast.');
        ga_naar('beheer/route.php?id=' . $routeId);
    }

    // ---- Stop toevoegen ----
    if ($actie === 'stop_toevoegen') {
        $stop['truck_id'] = (int) ($_POST['truck_id'] ?? 0);
        $stop['location_id'] = (int) ($_POST['location_id'] ?? 0);
        $stop['start_time'] = $_POST['start_time'] ?? '';
        $stop['end_time'] = $_POST['end_time'] ?? '';

        // Stap 1: is alles ingevuld en geldig?
        $query = $db->prepare('SELECT COUNT(*) FROM trucks WHERE id = ?');
        $query->execute([$stop['truck_id']]);
        if ($query->fetchColumn() == 0) {
            $fouten[] = 'Kies een foodtruck.';
        }
        $query = $db->prepare('SELECT COUNT(*) FROM locations WHERE id = ?');
        $query->execute([$stop['location_id']]);
        if ($query->fetchColumn() == 0) {
            $fouten[] = 'Kies een plek.';
        }
        if (!is_geldige_tijd($stop['start_time']) || !is_geldige_tijd($stop['end_time'])) {
            $fouten[] = 'Vul een geldige begintijd en eindtijd in.';
        } elseif ($stop['end_time'] <= $stop['start_time']) {
            $fouten[] = 'De eindtijd moet na de begintijd liggen.';
        }

        // Stap 2: de regels van de planning (FE6). Alleen als stap 1 goed ging.
        if (empty($fouten)) {
            $fouten = controleer_stop($db, $route['route_date'], $stop['truck_id'], $stop['location_id'], $stop['start_time'], $stop['end_time']);
        }

        // Stap 3: opslaan. De nieuwe stop komt achteraan (hoogste volgnummer + 1).
        if (empty($fouten)) {
            $query = $db->prepare('SELECT COALESCE(MAX(stop_order), 0) + 1 FROM stops WHERE route_id = ?');
            $query->execute([$routeId]);
            $volgnummer = $query->fetchColumn();

            $query = $db->prepare('INSERT INTO stops (route_id, truck_id, location_id, start_time, end_time, stop_order) VALUES (?, ?, ?, ?, ?, ?)');
            $query->execute([$routeId, $stop['truck_id'], $stop['location_id'], $stop['start_time'], $stop['end_time'], $volgnummer]);

            zet_melding('succes', 'Stop is toegevoegd.');
            ga_naar('beheer/route.php?id=' . $routeId);
        }
    }

    // ---- Stop verwijderen ----
    if ($actie === 'stop_verwijderen') {
        // "AND route_id = ?" zorgt dat je alleen een stop van DEZE route kunt verwijderen
        $query = $db->prepare('DELETE FROM stops WHERE id = ? AND route_id = ?');
        $query->execute([(int) ($_POST['stop_id'] ?? 0), $routeId]);
        hernummer_stops($db, $routeId);
        zet_melding('succes', 'Stop is verwijderd.');
        ga_naar('beheer/route.php?id=' . $routeId);
    }

    // ---- Stop een plek omhoog of omlaag ----
    if ($actie === 'omhoog' || $actie === 'omlaag') {
        $ids = stop_ids_op_volgorde($db, $routeId);
        $plaats = array_search((int) ($_POST['stop_id'] ?? 0), $ids);
        $buur = $actie === 'omhoog' ? $plaats - 1 : $plaats + 1;

        // Alleen wisselen als er een buurman is (de eerste kan niet omhoog, de laatste niet omlaag)
        if ($plaats !== false && isset($ids[$buur])) {
            [$ids[$plaats], $ids[$buur]] = [$ids[$buur], $ids[$plaats]];
            sla_volgorde_op($db, $ids);
        }
        ga_naar('beheer/route.php?id=' . $routeId);
    }
}

/* De id's van de stops van een route, in de huidige volgorde */
function stop_ids_op_volgorde($db, $routeId)
{
    $query = $db->prepare('SELECT id FROM stops WHERE route_id = ? ORDER BY stop_order');
    $query->execute([$routeId]);
    return array_map('intval', $query->fetchAll(PDO::FETCH_COLUMN));
}

/* Geeft de stops volgnummer 1, 2, 3 ... in de volgorde van de lijst */
function sla_volgorde_op($db, $ids)
{
    $query = $db->prepare('UPDATE stops SET stop_order = ? WHERE id = ?');
    foreach ($ids as $index => $id) {
        $query->execute([$index + 1, $id]);
    }
}

/* Na verwijderen: geen gaten in de nummering (1, 3 wordt 1, 2) */
function hernummer_stops($db, $routeId)
{
    sla_volgorde_op($db, stop_ids_op_volgorde($db, $routeId));
}

// ---- Gegevens voor de pagina ----
$query = $db->prepare(
    'SELECT stops.*, trucks.naam AS truck, locations.naam AS plek, locations.city
     FROM stops
     JOIN trucks    ON trucks.id = stops.truck_id
     JOIN locations ON locations.id = stops.location_id
     WHERE stops.route_id = ?
     ORDER BY stops.stop_order'
);
$query->execute([$routeId]);
$stops = $query->fetchAll();

$trucks = $db->query('SELECT id, naam FROM trucks ORDER BY naam')->fetchAll();
$plekken = $db->query('SELECT id, naam, city FROM locations ORDER BY naam')->fetchAll();

$titel = 'Route ' . datum_tekst($route['route_date']);
require __DIR__ . '/../includes/header.php';
?>

<p><a href="routes.php">&larr; Alle routes</a></p>

<h1>Route <?= e(datum_tekst($route['route_date'])) ?></h1>
<p>
    Status:
    <?php if ($isConcept): ?>
        <span class="label label-geel">Concept</span> (nog niet zichtbaar voor bezoekers)
    <?php else: ?>
        <span class="label label-groen">Goedgekeurd</span> (zichtbaar voor bezoekers)
    <?php endif; ?>
</p>

<div class="kaart">
    <h2>Stops</h2>

    <?php if (!$stops): ?>
        <p>Deze route heeft nog geen stops.</p>
    <?php else: ?>
        <table class="tabel">
            <tr>
                <th>#</th>
                <th>Tijd</th>
                <th>Foodtruck</th>
                <th>Plek</th>
                <?php if ($isConcept): ?><th>Acties</th><?php endif; ?>
            </tr>
            <?php foreach ($stops as $nr => $s): ?>
                <tr>
                    <td><?= e($s['stop_order']) ?></td>
                    <td><?= e(tijd($s['start_time'])) ?> - <?= e(tijd($s['end_time'])) ?></td>
                    <td><?= e($s['truck']) ?></td>
                    <td><?= e($s['plek']) ?>, <?= e($s['city']) ?></td>
                    <?php if ($isConcept): ?>
                        <td class="acties">
                            <?php if ($nr > 0): ?>
                                <form method="post">
                                    <?= csrf_veld() ?>
                                    <input type="hidden" name="actie" value="omhoog">
                                    <input type="hidden" name="stop_id" value="<?= e($s['id']) ?>">
                                    <button type="submit" class="knop knop-klein" aria-label="Stop omhoog">&uarr;</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($nr < count($stops) - 1): ?>
                                <form method="post">
                                    <?= csrf_veld() ?>
                                    <input type="hidden" name="actie" value="omlaag">
                                    <input type="hidden" name="stop_id" value="<?= e($s['id']) ?>">
                                    <button type="submit" class="knop knop-klein" aria-label="Stop omlaag">&darr;</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" onsubmit="return confirm('Deze stop verwijderen?');">
                                <?= csrf_veld() ?>
                                <input type="hidden" name="actie" value="stop_verwijderen">
                                <input type="hidden" name="stop_id" value="<?= e($s['id']) ?>">
                                <button type="submit" class="knop knop-klein knop-rood">Verwijderen</button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php if ($isConcept): ?>
    <div class="kaart">
        <h2>Stop toevoegen</h2>

        <?php if ($fouten): ?>
            <div class="melding melding-fout">
                <strong>Fout:</strong> de stop is niet opgeslagen.
                <ul>
                    <?php foreach ($fouten as $f): ?>
                        <li><?= e($f) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" class="formulier">
            <?= csrf_veld() ?>
            <input type="hidden" name="actie" value="stop_toevoegen">

            <label for="truck_id">Foodtruck</label>
            <select id="truck_id" name="truck_id">
                <option value="">Kies een foodtruck</option>
                <?php foreach ($trucks as $t): ?>
                    <option value="<?= e($t['id']) ?>" <?= $t['id'] == $stop['truck_id'] ? 'selected' : '' ?>><?= e($t['naam']) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="location_id">Plek</label>
            <select id="location_id" name="location_id">
                <option value="">Kies een plek</option>
                <?php foreach ($plekken as $p): ?>
                    <option value="<?= e($p['id']) ?>" <?= $p['id'] == $stop['location_id'] ? 'selected' : '' ?>><?= e($p['naam']) ?> (<?= e($p['city']) ?>)</option>
                <?php endforeach; ?>
            </select>

            <label for="start_time">Begintijd</label>
            <input type="time" id="start_time" name="start_time" value="<?= e($stop['start_time']) ?>">

            <label for="end_time">Eindtijd</label>
            <input type="time" id="end_time" name="end_time" value="<?= e($stop['end_time']) ?>">

            <button type="submit" class="knop">Stop toevoegen</button>
        </form>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
