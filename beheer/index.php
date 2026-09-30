<?php
/*
 * DASHBOARD
 * Technisch ontwerp H5: "Dashboard - Planner/beheerder - Overzicht van de planning en verlopen vergunningen"
 *
 * DAG 2: alleen de toegangscontrole en links naar de onderdelen.
 * Het overzicht van de planning en de vergunningen komt op een latere dag.
 */

require __DIR__ . '/../includes/login.php';

// Alleen planners en beheerders mogen hier komen (TE5)
$gebruiker = vereis_rol(['planner', 'beheerder']);

$titel = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>

<h1>Dashboard</h1>
<p>Je bent ingelogd als <strong><?= e($gebruiker['naam']) ?></strong> (rol: <?= e($gebruiker['role']) ?>).</p>

<div class="kaart">
    <h2>Beheer</h2>
    <ul>
        <li>Foodtrucks <em>(komt op dag 3)</em></li>
        <li>Plekken <em>(komt op dag 3)</em></li>
        <li>Routes <em>(komt op dag 4)</em></li>
        <li>Menu <em>(komt op dag 5)</em></li>
        <?php if ($gebruiker['role'] === 'beheerder'): ?>
            <li><a href="<?= url('beheer/accounts.php') ?>">Accounts van planners</a></li>
        <?php endif; ?>
    </ul>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
