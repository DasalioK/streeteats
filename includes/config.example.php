<?php
/*
 * VOORBEELD-INSTELLINGEN
 *
 * Uitleg: kopieer dit bestand naar "config.php" en vul je eigen gegevens in.
 * config.php staat in .gitignore, zodat echte wachtwoorden nooit op GitHub komen.
 */

$config = [
    'db_host' => 'localhost',
    'db_naam' => 'streeteats',
    'db_gebruiker' => 'root',
    'db_wachtwoord' => '',      // in XAMPP is dit standaard leeg

    // Map in htdocs waar het project staat. Nodig om links goed te maken.
    'basis_url' => '/streeteats',
];
