<?php
/*
 * STARTPAGINA
 * Technisch ontwerp H5: "Startpagina - Iedereen - Zoeken op datum, plaats en soort eten"
 *
 * DAG 1: deze pagina laat alleen zien dat PHP (TE1) en de database (TE2) werken.
 * Het zoeken (FE1) bouwen we later op deze pagina.
 */

require 'includes/database.php';

// Hoeveel foodtrucks staan er in de database? Zo zien we dat de verbinding werkt.
$aantalTrucks = $db->query('SELECT COUNT(*) FROM trucks')->fetchColumn();

$titel = 'Welkom';
require 'includes/header.php';
?>

<h1>Waar staat mijn foodtruck?</h1>
<p>Hier kun je straks zoeken op datum, plaats en soort eten.</p>

<div class="kaart">
    <h2>Controle (dag 1)</h2>
    <ul>
        <li>PHP-versie: <?= e(PHP_VERSION) ?> (TE1: moet 8 of hoger zijn)</li>
        <li>Database verbonden: <?= e($aantalTrucks) ?> foodtrucks gevonden (TE2)</li>
    </ul>
</div>

<?php require 'includes/footer.php'; ?>
