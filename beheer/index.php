<?php
/*
 * DASHBOARD
 * Technisch ontwerp H5: "Dashboard - Planner/beheerder - Overzicht van de planning en verlopen vergunningen"
 * Hoort bij: FE7 (vergunning), TE5 (rechten)
 *
 * Toont: de planning vanaf vandaag, verlopen vergunningen en links naar de beheerpagina's.
 */

require __DIR__ . '/../includes/login.php';

// Alleen planners en beheerders mogen hier komen (TE5)
$gebruiker = vereis_rol(['planner', 'beheerder']);

// Plekken waarvan de vergunning vandaag al verlopen is (FE7)
$query = $db->prepare('SELECT * FROM locations WHERE permit_end_date < ? ORDER BY permit_end_date');
$query->execute([date('Y-m-d')]);
$verlopen = $query->fetchAll();

// Overzicht van de planning: routes vanaf vandaag, met het aantal stops
$query = $db->prepare(
    'SELECT routes.id, routes.route_date, routes.status, COUNT(stops.id) AS aantal_stops
     FROM routes
     LEFT JOIN stops ON stops.route_id = routes.id
     WHERE routes.route_date >= ?
     GROUP BY routes.id
     ORDER BY routes.route_date'
);
$query->execute([date('Y-m-d')]);
$planning = $query->fetchAll();

$titel = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>

<h1>Dashboard</h1>
<p>Je bent ingelogd als <strong><?= e($gebruiker['naam']) ?></strong> (rol: <?= e($gebruiker['role']) ?>).</p>

<div class="kaart">
    <h2>Planning vanaf vandaag</h2>
    <?php if (!$planning): ?>
        <p>Er zijn geen routes gepland.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($planning as $route): ?>
                <li>
                    <a href="<?= url('beheer/route.php?id=' . $route['id']) ?>"><?= e(datum_tekst($route['route_date'])) ?></a>
                    - <?= e($route['aantal_stops']) ?> stop(s) -
                    <?php if ($route['status'] === 'goedgekeurd'): ?>
                        <span class="label label-groen">Goedgekeurd</span>
                    <?php else: ?>
                        <span class="label label-geel">Concept</span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<div class="kaart">
    <h2>Verlopen vergunningen</h2>
    <?php if (!$verlopen): ?>
        <p><span class="label label-groen">In orde</span> Alle vergunningen zijn geldig.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($verlopen as $plek): ?>
                <li>
                    <span class="label label-rood">Verlopen</span>
                    <a href="<?= url('beheer/plekken.php?bewerk=' . $plek['id']) ?>"><?= e($plek['naam']) ?></a>
                    - verlopen op <?= e(date('d-m-Y', strtotime($plek['permit_end_date']))) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<div class="kaart">
    <h2>Beheer</h2>
    <ul>
        <li><a href="<?= url('beheer/trucks.php') ?>">Foodtrucks</a></li>
        <li><a href="<?= url('beheer/plekken.php') ?>">Plekken, openingstijden en vergunningen</a></li>
        <li><a href="<?= url('beheer/routes.php') ?>">Routes</a></li>
        <li><a href="<?= url('beheer/menu.php') ?>">Menu per dag</a></li>
        <?php if ($gebruiker['role'] === 'beheerder'): ?>
            <li><a href="<?= url('beheer/accounts.php') ?>">Accounts van planners</a></li>
        <?php endif; ?>
    </ul>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
