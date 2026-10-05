<?php
/*
 * MENU BEHEREN
 * Technisch ontwerp H5: "Menu - Planner/beheerder - Gerechten per dag toevoegen en op uitverkocht zetten"
 * Hoort bij: FE9 (menu beheren), FE3 (uitverkocht), TE4 (invoer controleren), TE5 (rechten)
 *
 * Uitleg:
 * - Bovenaan kies je een truck en een datum. Je ziet dan het menu van die truck op die dag.
 * - Een gerecht hoort altijd bij één truck én één datum (menu_date). Een gerecht voor morgen
 *   is daardoor alleen morgen zichtbaar (test uit het ontwerp bij FE9).
 * - "Uitverkocht" zet de kolom available op 0. Nog een keer klikken zet hem weer op 1.
 */

require __DIR__ . '/../includes/login.php';

vereis_rol(['planner', 'beheerder']);

// Gekozen truck en datum komen uit de link (GET). Geen datum gekozen? Dan vandaag.
$truckId = (int) ($_GET['truck'] ?? 0);
$datum = $_GET['datum'] ?? date('Y-m-d');
if (!is_geldige_datum($datum)) {
    $datum = date('Y-m-d');
}

$trucks = $db->query('SELECT id, naam FROM trucks ORDER BY naam')->fetchAll();

// Geen (geldige) truck gekozen? Dan de eerste truck uit de lijst.
$truckIds = array_column($trucks, 'id');
if (!in_array($truckId, $truckIds) && $trucks) {
    $truckId = (int) $trucks[0]['id'];
}

$fouten = [];
$gerecht = ['name' => '', 'price' => ''];

// Na een actie gaan we terug naar dezelfde truck en datum
$terug = 'beheer/menu.php?truck=' . $truckId . '&datum=' . $datum;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controleer_csrf();
    $actie = $_POST['actie'] ?? '';

    // ---- Gerecht toevoegen ----
    if ($actie === 'toevoegen') {
        $gerecht['name'] = trim($_POST['name'] ?? '');
        // Komma mag ook: 8,50 wordt 8.50
        $gerecht['price'] = str_replace(',', '.', trim($_POST['price'] ?? ''));

        if ($gerecht['name'] === '') {
            $fouten['name'] = 'Dit veld is verplicht.';
        } elseif (mb_strlen($gerecht['name']) > 100) {
            $fouten['name'] = 'Maximaal 100 tekens.';
        }
        // is_numeric: is het een getal? Daarna: groter dan 0 en niet te hoog
        if (!is_numeric($gerecht['price']) || $gerecht['price'] <= 0 || $gerecht['price'] > 999) {
            $fouten['price'] = 'Vul een geldige prijs in, bijvoorbeeld 8,50.';
        }
        if (!$trucks) {
            $fouten['name'] = 'Voeg eerst een foodtruck toe.';
        }

        if (empty($fouten)) {
            $query = $db->prepare('INSERT INTO menu_items (truck_id, name, price, available, menu_date) VALUES (?, ?, ?, 1, ?)');
            $query->execute([$truckId, $gerecht['name'], $gerecht['price'], $datum]);
            zet_melding('succes', $gerecht['name'] . ' is toegevoegd aan het menu.');
            ga_naar($terug);
        }
    }

    // ---- Uitverkocht aan/uit (FE3) ----
    // "AND truck_id = ?" zorgt dat je alleen gerechten van de gekozen truck kunt aanpassen
    if ($actie === 'uitverkocht') {
        $query = $db->prepare('UPDATE menu_items SET available = 1 - available WHERE id = ? AND truck_id = ?');
        $query->execute([(int) ($_POST['id'] ?? 0), $truckId]);
        zet_melding('succes', 'De status van het gerecht is aangepast.');
        ga_naar($terug);
    }

    // ---- Gerecht verwijderen ----
    if ($actie === 'verwijderen') {
        $query = $db->prepare('DELETE FROM menu_items WHERE id = ? AND truck_id = ?');
        $query->execute([(int) ($_POST['id'] ?? 0), $truckId]);
        zet_melding('succes', 'Het gerecht is verwijderd.');
        ga_naar($terug);
    }
}

// Het menu van de gekozen truck op de gekozen dag
$query = $db->prepare('SELECT * FROM menu_items WHERE truck_id = ? AND menu_date = ? ORDER BY name');
$query->execute([$truckId, $datum]);
$menu = $query->fetchAll();

$titel = 'Menu';
require __DIR__ . '/../includes/header.php';
?>

<h1>Menu</h1>

<div class="kaart">
    <!-- Kiezen gaat met GET: de keuze komt in de link, dan kun je hem ook bewaren of delen -->
    <form method="get" class="formulier">
        <label for="truck">Foodtruck</label>
        <select id="truck" name="truck">
            <?php foreach ($trucks as $t): ?>
                <option value="<?= e($t['id']) ?>" <?= $t['id'] == $truckId ? 'selected' : '' ?>><?= e($t['naam']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="datum">Datum</label>
        <input type="date" id="datum" name="datum" value="<?= e($datum) ?>">

        <button type="submit" class="knop">Menu tonen</button>
    </form>
</div>

<div class="kaart">
    <h2>Menu op <?= e(datum_tekst($datum)) ?></h2>

    <?php if (!$menu): ?>
        <p>Er staan nog geen gerechten op het menu voor deze dag.</p>
    <?php else: ?>
        <table class="tabel">
            <tr>
                <th>Gerecht</th>
                <th>Prijs</th>
                <th>Status</th>
                <th>Acties</th>
            </tr>
            <?php foreach ($menu as $item): ?>
                <tr>
                    <td><?= e($item['name']) ?></td>
                    <td>&euro; <?= e(number_format($item['price'], 2, ',', '.')) ?></td>
                    <td>
                        <?php if ($item['available']): ?>
                            <span class="label label-groen">Beschikbaar</span>
                        <?php else: ?>
                            <span class="label label-rood">Uitverkocht</span>
                        <?php endif; ?>
                    </td>
                    <td class="acties">
                        <form method="post">
                            <?= csrf_veld() ?>
                            <input type="hidden" name="actie" value="uitverkocht">
                            <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                            <button type="submit" class="knop knop-klein">
                                <?= $item['available'] ? 'Zet op uitverkocht' : 'Weer beschikbaar' ?>
                            </button>
                        </form>
                        <form method="post" onsubmit="return confirm('Dit gerecht verwijderen?');">
                            <?= csrf_veld() ?>
                            <input type="hidden" name="actie" value="verwijderen">
                            <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                            <button type="submit" class="knop knop-klein knop-rood">Verwijderen</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<div class="kaart">
    <h2>Gerecht toevoegen</h2>

    <?php if ($fouten): ?>
        <p class="melding melding-fout"><strong>Fout:</strong> Het gerecht is niet opgeslagen. Controleer de velden.</p>
    <?php endif; ?>

    <form method="post" class="formulier">
        <?= csrf_veld() ?>
        <input type="hidden" name="actie" value="toevoegen">

        <label for="name">Naam van het gerecht</label>
        <input type="text" id="name" name="name" maxlength="100" value="<?= e($gerecht['name']) ?>">
        <?php if (isset($fouten['name'])): ?><span class="veld-fout"><?= e($fouten['name']) ?></span><?php endif; ?>

        <label for="price">Prijs in euro</label>
        <input type="text" id="price" name="price" inputmode="decimal" placeholder="8,50" value="<?= e($gerecht['price']) ?>">
        <?php if (isset($fouten['price'])): ?><span class="veld-fout"><?= e($fouten['price']) ?></span><?php endif; ?>

        <button type="submit" class="knop">Toevoegen</button>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
