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

/*
 * TECHNISCHE FOUTEN NETJES AFHANDELEN (ontwerp H9)
 * Gaat er onverwacht iets mis (bijv. in een query), dan mag de bezoeker NIET de technische
 * details zien, zoals tabelnamen en mappen op de server. Dat helpt alleen een hacker.
 * - display_errors uit: PHP toont zelf geen fouten meer op het scherm.
 * - set_exception_handler: elke fout die nergens wordt opgevangen, komt hier terecht.
 *   De details schrijven we in het logbestand (error_log), de bezoeker ziet een algemene melding.
 *   In XAMPP staat dat logbestand hier: C:\xamp\apache\logs\error.log
 */
ini_set('display_errors', '0');

set_exception_handler(function ($fout) use ($config) {
    error_log('StreetEats fout: ' . $fout->getMessage() . ' in ' . $fout->getFile() . ' regel ' . $fout->getLine());
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="nl"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1"><title>Fout - StreetEats</title></head>'
        . '<body style="font-family: Arial, sans-serif; padding: 24px;">'
        . '<p style="background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 8px;">'
        . '<strong>Fout:</strong> Er is iets misgegaan. Probeer het later opnieuw.</p>'
        . '<p><a href="' . htmlspecialchars($config['basis_url']) . '/">Naar de startpagina</a></p></body></html>';
});

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
