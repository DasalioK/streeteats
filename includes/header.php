<?php
/*
 * BOVENKANT VAN ELKE PAGINA (layout)
 * Hoort bij: TE3 (responsive) en technisch ontwerp H5 (schermen)
 *
 * Uitleg: elke pagina doet eerst  $titel = '...';  en dan  require 'includes/header.php';
 * Zo hoeven we het menu maar op één plek te maken.
 */

require_once __DIR__ . '/functies.php';
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
        <a href="<?= url('login.php') ?>">Inloggen</a>
    </nav>
</header>

<main class="inhoud">
