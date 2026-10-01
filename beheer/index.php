<?php
/*
 * DASHBOARD
 * Technisch ontwerp H5: "Dashboard - Planner/beheerder - Overzicht van de planning en verlopen vergunningen"
 * Hoort bij: FE7 (vergunning), TE5 (rechten)
 *
 * DAG 3: links naar de beheerpagina's en een lijst met verlopen vergunningen.
 * Het overzicht van de planning komt op dag 4.
 */

require __DIR__ . '/../includes/login.php';

// Alleen planners en beheerders mogen hier komen (TE5)
$gebruiker = vereis_rol(['planner', 'beheerder']);

// Plekken waarvan de vergunning vandaag al verlopen is (FE7)
$query = $db->prepare('SELECT * FROM locations WHERE permit_end_date < ? ORDER BY permit_end_date');
$query->execute([date('Y-m-d')]);
$verlopen = $query->fetchAll();

$titel = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>

<h1>Dashboard</h1>
<p>Je bent ingelogd als <strong><?= e($gebruiker['naam']) ?></strong> (rol: <?= e($gebruiker['role']) ?>).</p>

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
        <li>Routes <em>(komt op dag 4)</em></li>
        <li>Menu <em>(komt op dag 5)</em></li>
        <?php if ($gebruiker['role'] === 'beheerder'): ?>
            <li><a href="<?= url('beheer/accounts.php') ?>">Accounts van planners</a></li>
        <?php endif; ?>
    </ul>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
