<?php
/*
 * OPSTARTBESTAND VOOR DE TESTS (PHPUnit leest dit als eerste)
 *
 * Uitleg:
 * - We gebruiken een aparte testdatabase "streeteats_test". De echte database "streeteats" blijft dus heel.
 * - reset_testdatabase() zet vóór elke integratietest dezelfde testdata terug, zodat tests elkaar niet beïnvloeden.
 * - De sessie zetten we aan zonder cookie, omdat er bij een test geen browser is.
 */

require __DIR__ . '/../vendor/autoload.php';

putenv('STREETEATS_TEST_DB=streeteats_test');

ini_set('session.use_cookies', '0');
ini_set('session.cache_limiter', '');
session_start();

/* Maakt de testdatabase opnieuw aan met de testdata uit database/streeteats.sql */
function reset_testdatabase()
{
    $sql = file_get_contents(__DIR__ . '/../database/streeteats.sql');
    // Hetzelfde SQL-bestand als de website, maar dan voor de testdatabase
    $sql = str_replace(
        ['CREATE DATABASE IF NOT EXISTS streeteats ', 'USE streeteats;'],
        ['CREATE DATABASE IF NOT EXISTS streeteats_test ', 'USE streeteats_test;'],
        $sql
    );
    $server = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '');
    $server->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $server->exec($sql);
}

reset_testdatabase();

// De code van de website laden. PHPUnit laadt dit bestand vanuit een functie,
// daarom zetten we $config en $db expliciet in $GLOBALS, zodat de functies ze kunnen vinden.
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/database.php';
$GLOBALS['config'] = $config;
$GLOBALS['db'] = $db;
require __DIR__ . '/../includes/functies.php';
require __DIR__ . '/../includes/controles.php';
require __DIR__ . '/../includes/login.php';
