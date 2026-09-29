<?php
/*
 * DATABASEVERBINDING
 * Hoort bij: TE2 (MySQL/MariaDB) en TE4 (veilig opslaan)
 *
 * Uitleg:
 * - We gebruiken PDO om met de database te praten.
 * - Elk ander bestand doet: require 'includes/database.php';
 *   en kan dan de variabele $db gebruiken. Zo staat de verbinding maar op één plek.
 * - Later gebruiken we altijd "prepared statements" (met ? in de query).
 *   Dat beschermt tegen SQL-injectie, omdat invoer van de gebruiker nooit als SQL wordt uitgevoerd.
 */

require_once __DIR__ . '/config.php';

try {
    $db = new PDO(
        'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_naam'] . ';charset=utf8mb4',
        $config['db_gebruiker'],
        $config['db_wachtwoord']
    );

    // Bij een fout gooit PDO een exception, zodat we de fout kunnen opvangen
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Resultaten krijgen we als array met kolomnamen, bijv. $truck['naam']
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $fout) {
    // Technisch ontwerp H9: de details gaan naar een logbestand, niet naar het scherm
    error_log('Databasefout: ' . $fout->getMessage());
    exit('Er is iets misgegaan. Probeer het later opnieuw.');
}
