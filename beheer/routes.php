<?php
/*
 * ROUTES: OVERZICHT EN NIEUWE ROUTE
 * Technisch ontwerp H5: "Routes - Planner/beheerder - Route met stops maken, opslaan en goedkeuren"
 * Hoort bij: FE5 (route maken), TE5 (rechten)
 *
 * Uitleg:
 * - Een route is de planning van één dag. Een nieuwe route krijgt altijd de status 'concept'
 *   (ontwerp H10: "Route eerst als concept"). Bezoekers zien een concept niet.
 * - Na het aanmaken ga je naar route.php, waar je de stops toevoegt.
 */

require __DIR__ . '/../includes/login.php';

$gebruiker = vereis_rol(['planner', 'beheerder']);

$fout = '';
$datum = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controleer_csrf();
    $actie = $_POST['actie'] ?? '';

    // ---- Nieuwe route maken ----
    if ($actie === 'nieuw') {
        $datum = trim($_POST['route_date'] ?? '');

        if (!is_geldige_datum($datum)) {
            $fout = 'Vul een geldige datum in.';
        } else {
            // created_by = wie de route maakt (users 1 -> N routes)
            $query = $db->prepare('INSERT INTO routes (route_date, status, created_by) VALUES (?, ?, ?)');
            $query->execute([$datum, 'concept', $gebruiker['id']]);

            zet_melding('succes', 'Route is aangemaakt als concept. Voeg nu de stops toe.');
            ga_naar('beheer/route.php?id=' . $db->lastInsertId());
        }
    }

    // ---- Route verwijderen (de stops gaan mee door ON DELETE CASCADE) ----
    if ($actie === 'verwijderen') {
        $query = $db->prepare('DELETE FROM routes WHERE id = ?');
        $query->execute([(int) ($_POST['id'] ?? 0)]);
        zet_melding('succes', 'Route is verwijderd.');
        ga_naar('beheer/routes.php');
    }
}

// Alle routes, nieuwste datum bovenaan, met het aantal stops per route
$routes = $db->query(
    'SELECT routes.*, users.naam AS gemaakt_door, COUNT(stops.id) AS aantal_stops
     FROM routes
     JOIN users ON users.id = routes.created_by
     LEFT JOIN stops ON stops.route_id = routes.id
     GROUP BY routes.id
     ORDER BY routes.route_date DESC, routes.id DESC'
)->fetchAll();

$titel = 'Routes';
require __DIR__ . '/../includes/header.php';
?>

<h1>Routes</h1>

<div class="kaart">
    <h2>Nieuwe route</h2>

    <?php if ($fout): ?>
        <p class="melding melding-fout"><strong>Fout:</strong> <?= e($fout) ?></p>
    <?php endif; ?>

    <form method="post" class="formulier">
        <?= csrf_veld() ?>
        <input type="hidden" name="actie" value="nieuw">

        <label for="route_date">Datum van de route</label>
        <input type="date" id="route_date" name="route_date" value="<?= e($datum) ?>" required>

        <button type="submit" class="knop">Route maken</button>
    </form>
</div>

<div class="kaart">
    <h2>Alle routes</h2>

    <?php if (!$routes): ?>
        <p>Er zijn nog geen routes.</p>
    <?php else: ?>
        <table class="tabel">
            <tr>
                <th>Datum</th>
                <th>Status</th>
                <th>Stops</th>
                <th>Gemaakt door</th>
                <th>Acties</th>
            </tr>
            <?php foreach ($routes as $route): ?>
                <tr>
                    <td><?= e(datum_tekst($route['route_date'])) ?></td>
                    <td>
                        <?php if ($route['status'] === 'goedgekeurd'): ?>
                            <span class="label label-groen">Goedgekeurd</span>
                        <?php else: ?>
                            <span class="label label-geel">Concept</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($route['aantal_stops']) ?></td>
                    <td><?= e($route['gemaakt_door']) ?></td>
                    <td class="acties">
                        <a class="knop knop-klein" href="route.php?id=<?= e($route['id']) ?>">Openen</a>
                        <form method="post" onsubmit="return confirm('Route met alle stops verwijderen?');">
                            <?= csrf_veld() ?>
                            <input type="hidden" name="actie" value="verwijderen">
                            <input type="hidden" name="id" value="<?= e($route['id']) ?>">
                            <button type="submit" class="knop knop-klein knop-rood">Verwijderen</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
