<?php
/*
 * BOVENKANT VAN ELKE PAGINA (layout)
 * Hoort bij: TE3 (responsive) en technisch ontwerp H5 (schermen)
 *
 * Uitleg: elke pagina doet eerst  $titel = '...';  en dan  require 'includes/header.php';
 * Zo hoeven we het menu maar op één plek te maken.
 * Het menu past zich aan de rol aan. Let op: dat is alleen voor het gemak.
 * De echte beveiliging zit in vereis_rol() bovenaan elke beheerpagina.
 */

require_once __DIR__ . '/login.php';

$ingelogd = ingelogde_gebruiker();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <!-- Deze regel zorgt dat de site op een telefoon goed geschaald wordt (TE3) -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titel ?? 'StreetEats') ?> - StreetEats</title>
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
</head>
<body>

<header class="kop">
    <a class="logo" href="<?= url() ?>">StreetEats</a>
    <nav class="menu">
        <a href="<?= url() ?>">Zoeken</a>

        <?php if ($ingelogd): ?>
            <a href="<?= url('beheer/index.php') ?>">Dashboard</a>
            <a href="<?= url('beheer/trucks.php') ?>">Foodtrucks</a>
            <a href="<?= url('beheer/plekken.php') ?>">Plekken</a>
            <a href="<?= url('beheer/routes.php') ?>">Routes</a>
            <a href="<?= url('beheer/menu.php') ?>">Menu</a>
            <?php if ($ingelogd['role'] === 'beheerder'): ?>
                <a href="<?= url('beheer/accounts.php') ?>">Accounts</a>
            <?php endif; ?>
            <a href="<?= url('logout.php') ?>">Uitloggen (<?= e($ingelogd['naam']) ?>)</a>
        <?php else: ?>
            <a href="<?= url('login.php') ?>">Inloggen</a>
        <?php endif; ?>
    </nav>
</header>

<main class="inhoud">
    <?php toon_melding(); ?>
