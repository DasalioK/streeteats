<?php
/*
 * TRUCKPAGINA
 * Technisch ontwerp H5: "Truckpagina - Iedereen - Plek, tijden en menu van een truck bekijken"
 * Hoort bij: FE2 (truck en menu bekijken), FE3 (uitverkocht tonen), FE8 (alleen goedgekeurd)
 *           Planning: FE-02, FE-03, FE-04, FE-13 en taak T-28 (dagagenda: plek, tijden en menu zichtbaar)
 *
 * Uitleg:
 * - De link ziet er zo uit: truck.php?id=1&datum=2026-10-05
 * - Waar staat de truck die dag? Alleen stops uit goedgekeurde routes.
 * - Wat staat er op het menu die dag? Uitverkochte gerechten tonen we met het woord "Uitverkocht".
 */

require 'includes/database.php';
require_once 'includes/functies.php';

$datum = $_GET['datum'] ?? date('Y-m-d');
if (!is_geldige_datum($datum)) {
    $datum = date('Y-m-d');
}

// ---- De truck ophalen. Bestaat hij niet? Terug naar de startpagina met een melding. ----
$query = $db->prepare('SELECT * FROM trucks WHERE id = ?');
$query->execute([(int) ($_GET['id'] ?? 0)]);
$truck = $query->fetch();

if (!$truck) {
    zet_melding('fout', 'Deze foodtruck bestaat niet.');
    ga_naar('');
}

// ---- Waar en hoe laat staat de truck op deze dag? (alleen goedgekeurd: FE8) ----
// Planning FE-02: de bezoeker ziet ook de openingstijden van de plek op die dag.
// Daarom koppelen we opening_hours erbij, voor de weekdag van de gekozen datum (1 = maandag ... 7 = zondag).
$query = $db->prepare(
    'SELECT locations.naam AS plek, locations.address, locations.city, stops.start_time, stops.end_time,
            opening_hours.open_time, opening_hours.close_time
     FROM stops
     JOIN routes    ON routes.id = stops.route_id
     JOIN locations ON locations.id = stops.location_id
     LEFT JOIN opening_hours ON opening_hours.location_id = locations.id AND opening_hours.day = ?
     WHERE stops.truck_id = ? AND routes.route_date = ? AND routes.status = ?
     ORDER BY stops.start_time'
);
$query->execute([(int) date('N', strtotime($datum)), $truck['id'], $datum, 'goedgekeurd']);
$stops = $query->fetchAll();

// ---- Het menu van deze dag ----
$query = $db->prepare('SELECT * FROM menu_items WHERE truck_id = ? AND menu_date = ? ORDER BY name');
$query->execute([$truck['id'], $datum]);
$menu = $query->fetchAll();

$titel = $truck['naam'];
require 'includes/header.php';
?>

<p><a href="<?= url('?datum=' . e($datum)) ?>">&larr; Terug naar zoeken</a></p>

<h1><?= e($truck['naam']) ?></h1>
<p><span class="label label-grijs"><?= e($truck['kitchen_type']) ?></span></p>
<?php if ($truck['description']): ?>
    <p><?= e($truck['description']) ?></p>
<?php endif; ?>

<form method="get" class="kaart zoekformulier">
    <input type="hidden" name="id" value="<?= e($truck['id']) ?>">
    <div>
        <label for="datum">Andere dag bekijken</label>
        <input type="date" id="datum" name="datum" value="<?= e($datum) ?>">
    </div>
    <div>
        <button type="submit" class="knop">Toon</button>
    </div>
</form>

<div class="twee-kolommen">
    <div class="kaart">
        <h2>Waar en hoe laat?</h2>
        <p><?= e(datum_tekst($datum)) ?></p>

        <?php if (!$stops): ?>
            <p class="melding melding-info"><strong>Geen resultaat:</strong> Deze foodtruck staat op deze dag niet gepland.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($stops as $s): ?>
                    <li>
                        <strong><?= e(tijd($s['start_time'])) ?> - <?= e(tijd($s['end_time'])) ?> uur</strong>:
                        <?= e($s['plek']) ?>, <?= e($s['address']) ?>, <?= e($s['city']) ?>
                        <?php if ($s['open_time']): ?>
                            <br><small>Openingstijden van deze plek: <?= e(tijd($s['open_time'])) ?> - <?= e(tijd($s['close_time'])) ?> uur</small>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="kaart">
        <h2>Menu</h2>

        <?php if (!$menu): ?>
            <p class="melding melding-info"><strong>Geen resultaat:</strong> Er is nog geen menu voor deze dag.</p>
        <?php else: ?>
            <table class="tabel">
                <?php foreach ($menu as $item): ?>
                    <!-- FE3: uitverkocht met een woord én een kleur, en doorgestreept -->
                    <tr class="<?= $item['available'] ? '' : 'uitverkocht' ?>">
                        <td><?= e($item['name']) ?></td>
                        <td>&euro; <?= e(number_format($item['price'], 2, ',', '.')) ?></td>
                        <td><?php if (!$item['available']): ?><span class="label label-rood">Uitverkocht</span><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
