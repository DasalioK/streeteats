<?php
/*
 * EIS-DEKKING (requirements coverage) AUTOMATISCH BEREKENEN
 * Hoort bij checklistpunt 7 (minimaal 90% van de functionele eisen getest) en 8 (automatische output).
 *
 * Uitleg:
 * - Elke PHPUnit-test heeft een #[Group('FE-..')] en elke Playwright-test noemt de FE's in de titel.
 * - Dit script vraagt beide testprogramma's welke tests er zijn, telt per functionele eis
 *   hoeveel tests hem controleren, en rekent het percentage uit.
 *
 * Gebruik:  php tests/eis-dekking.php
 */

chdir(__DIR__ . '/..');

// Alle functionele eisen uit de planning (KT1-W1)
$eisen = [];
for ($i = 1; $i <= 16; $i++) {
    $eisen[sprintf('FE-%02d', $i)] = ['phpunit' => 0, 'e2e' => 0];
}

// 1. PHPUnit: "php vendor/bin/phpunit --list-groups" geeft bijv. " - FE-10 (11 tests)"
foreach (explode("\n", (string) shell_exec('php vendor/bin/phpunit --list-groups 2>&1')) as $regel) {
    if (preg_match('/- (FE-\d\d) \((\d+) tests?\)/', $regel, $m) && isset($eisen[$m[1]])) {
        $eisen[$m[1]]['phpunit'] = (int) $m[2];
    }
}

// 2. Playwright: "npx playwright test --list" geeft de titels van alle tests
foreach (explode("\n", (string) shell_exec('npx playwright test --list 2>&1')) as $regel) {
    if (!str_contains($regel, '.spec.js')) {
        continue;
    }
    // "FE-01 t/m FE-04" betekent FE-01, FE-02, FE-03 en FE-04
    $regel = preg_replace_callback('/FE-(\d\d) t\/m FE-(\d\d)/', function ($m) {
        return implode(', ', array_map(fn ($n) => sprintf('FE-%02d', $n), range((int) $m[1], (int) $m[2])));
    }, $regel);
    preg_match_all('/FE-\d\d/', $regel, $gevonden);
    foreach (array_unique($gevonden[0]) as $fe) {
        if (isset($eisen[$fe])) {
            $eisen[$fe]['e2e']++;
        }
    }
}

// 3. Overzicht maken
$uit = "EIS-DEKKING STREETEATS (" . date('d-m-Y H:i') . ")\n\n";
$uit .= sprintf("%-7s %8s %6s   %s\n", 'Eis', 'PHPUnit', 'E2E', 'Getest?');
$getest = 0;
foreach ($eisen as $fe => $aantal) {
    $ok = $aantal['phpunit'] + $aantal['e2e'] > 0;
    $getest += $ok ? 1 : 0;
    $uit .= sprintf("%-7s %8d %6d   %s\n", $fe, $aantal['phpunit'], $aantal['e2e'], $ok ? 'ja' : 'NEE');
}
$procent = round($getest / count($eisen) * 100);
$uit .= "\nGetest: $getest van " . count($eisen) . " functionele eisen = $procent%  (eis: minimaal 90%) -> " . ($procent >= 90 ? 'GEHAALD' : 'NIET GEHAALD') . "\n";

echo $uit;
@mkdir(__DIR__ . '/coverage', 0777, true);
file_put_contents(__DIR__ . '/coverage/eis-dekking.txt', $uit);
