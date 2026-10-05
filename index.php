<?php
/*
 * STARTPAGINA: FOODTRUCKS ZOEKEN
 * Technisch ontwerp H5: "Startpagina - Iedereen - Zoeken op datum, plaats en soort eten"
 * Hoort bij: FE1 (foodtrucks zoeken), FE8 (alleen goedgekeurde routes), FE10 (lege melding)
 *
 * Uitleg:
 * - Bezoekers hoeven niet in te loggen.
 * - Het zoekformulier gebruikt GET: de zoekwoorden staan in de link (bijv. ?datum=...&plaats=Haarlem).
 * - We tonen ALLEEN stops van routes met de status 'goedgekeurd'. Een concept zien bezoekers nooit.
 * - Ontwerp H11: resultaten per 12 tonen. Zijn er meer, dan komen er pagina-knoppen.
 */

require 'includes/database.php';
require_once 'includes/functies.php';

const PER_PAGINA = 12;

// ---- Zoekwoorden uit de link halen ----
$datum = $_GET['datum'] ?? date('Y-m-d');
$plaats = trim($_GET['plaats'] ?? '');
$soort = trim($_GET['soort'] ?? '');
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));

$fout = '';
if (!is_geldige_datum($datum)) {
    $fout = 'Vul een geldige datum in. We tonen nu vandaag.';
    $datum = date('Y-m-d');
}

// ---- De zoekvoorwaarden opbouwen ----
// We maken de WHERE één keer en gebruiken hem voor het tellen én voor het ophalen (geen dubbele code).
$waar = 'routes.status = ? AND routes.route_date = ?';
$waarden = ['goedgekeurd', $datum];

if ($plaats !== '') {
    // Zoekt in de plaats (Haarlem) én in de naam van de plek (Westerpark).
    // addcslashes: een % of _ van de bezoeker is gewone tekst en geen joker.
    $waar .= ' AND (locations.city LIKE ? OR locations.naam LIKE ?)';
    $zoek = '%' . addcslashes($plaats, '%_') . '%';
    $waarden[] = $zoek;
    $waarden[] = $zoek;
}
if ($soort !== '') {
    $waar .= ' AND trucks.kitchen_type = ?';
    $waarden[] = $soort;
}

$van = 'FROM stops
        JOIN routes    ON routes.id = stops.route_id
        JOIN trucks    ON trucks.id = stops.truck_id
        JOIN locations ON locations.id = stops.location_id
        WHERE ' . $waar;

// Hoeveel resultaten zijn er in totaal? Nodig voor de pagina-knoppen.
$query = $db->prepare('SELECT COUNT(*) ' . $van);
$query->execute($waarden);
$totaal = (int) $query->fetchColumn();
$aantalPaginas = max(1, (int) ceil($totaal / PER_PAGINA));
$pagina = min($pagina, $aantalPaginas);

// De resultaten van deze pagina. LIMIT = hoeveel, OFFSET = hoeveel overslaan.
// Dit zijn getallen die we zelf berekenen (geen invoer), dus ze mogen in de query staan.
$query = $db->prepare(
    'SELECT trucks.id AS truck_id, trucks.naam AS truck, trucks.kitchen_type,
            locations.naam AS plek, locations.city, stops.start_time, stops.end_time '
    . $van .
    ' ORDER BY stops.start_time, trucks.naam
      LIMIT ' . PER_PAGINA . ' OFFSET ' . (($pagina - 1) * PER_PAGINA)
);
$query->execute($waarden);
$resultaten = $query->fetchAll();

// Alle soorten eten voor de keuzelijst
$soorten = $db->query('SELECT DISTINCT kitchen_type FROM trucks ORDER BY kitchen_type')->fetchAll(PDO::FETCH_COLUMN);

/* Link naar een andere pagina met dezelfde zoekwoorden */
function pagina_link($nummer)
{
    global $datum, $plaats, $soort;
    return '?' . http_build_query(['datum' => $datum, 'plaats' => $plaats, 'soort' => $soort, 'pagina' => $nummer]);
}

$titel = 'Foodtrucks zoeken';
require 'includes/header.php';
?>

<h1>Waar staat mijn foodtruck?</h1>
<p>Zoek op datum, plaats en soort eten. Je ziet alleen de goedgekeurde planning.</p>

<?php if ($fout): ?>
    <p class="melding melding-fout"><strong>Fout:</strong> <?= e($fout) ?></p>
<?php endif; ?>

<form method="get" class="kaart zoekformulier">
    <div>
        <label for="datum">Datum</label>
        <input type="date" id="datum" name="datum" value="<?= e($datum) ?>">
    </div>
    <div>
        <label for="plaats">Plaats of plek</label>
        <input type="text" id="plaats" name="plaats" value="<?= e($plaats) ?>" placeholder="Bijv. Haarlem of Dam">
    </div>
    <div>
        <label for="soort">Soort eten</label>
        <select id="soort" name="soort">
            <option value="">Alle soorten</option>
            <?php foreach ($soorten as $s): ?>
                <option value="<?= e($s) ?>" <?= $s === $soort ? 'selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <button type="submit" class="knop">Zoeken</button>
    </div>
</form>

<h2>Resultaten voor <?= e(datum_tekst($datum)) ?></h2>

<?php if (!$resultaten): ?>
    <!-- FE10: duidelijke melding bij een leeg resultaat -->
    <p class="melding melding-info"><strong>Geen resultaat:</strong> Geen foodtrucks gevonden voor deze zoekopdracht.</p>
<?php else: ?>
    <p><?= $totaal ?> <?= $totaal === 1 ? 'resultaat' : 'resultaten' ?> gevonden.</p>

    <div class="kaarten">
        <?php foreach ($resultaten as $r): ?>
            <div class="kaart">
                <h3><?= e($r['truck']) ?></h3>
                <p><span class="label label-grijs"><?= e($r['kitchen_type']) ?></span></p>
                <p><strong><?= e($r['plek']) ?></strong>, <?= e($r['city']) ?><br>
                    <?= e(tijd($r['start_time'])) ?> - <?= e(tijd($r['end_time'])) ?> uur</p>
                <a class="knop knop-klein" href="truck.php?id=<?= e($r['truck_id']) ?>&amp;datum=<?= e($datum) ?>">Menu en details</a>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($aantalPaginas > 1): ?>
        <nav class="paginas" aria-label="Pagina's">
            <?php if ($pagina > 1): ?><a href="<?= e(pagina_link($pagina - 1)) ?>">&larr; Vorige</a><?php endif; ?>
            <span>Pagina <?= $pagina ?> van <?= $aantalPaginas ?></span>
            <?php if ($pagina < $aantalPaginas): ?><a href="<?= e(pagina_link($pagina + 1)) ?>">Volgende &rarr;</a><?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
