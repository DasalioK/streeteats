<?php
/*
 * PLEKKEN BEHEREN (met openingstijden en vergunning)
 * Technisch ontwerp H5: "Locaties - Planner/beheerder - Plekken, openingstijden en vergunning beheren"
 * Hoort bij: FE4 (plekken beheren), FE7 (vergunning), TE4 (invoer controleren), TE5 (rechten)
 *
 * Uitleg - deze pagina werkt net als trucks.php:
 * - Lijst van alle plekken, met de status van de vergunning (geldig / verlopen).
 * - Formulier voor een nieuwe plek of (met ?bewerk=..) een bestaande plek.
 * - Bij een bestaande plek staan onderaan ook de openingstijden per dag.
 */

require __DIR__ . '/../includes/login.php';

vereis_rol(['planner', 'beheerder']);

$vandaag = date('Y-m-d');
$fouten = [];
$plek = ['id' => '', 'naam' => '', 'address' => '', 'city' => '', 'permit_number' => '', 'permit_end_date' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controleer_csrf();
    $actie = $_POST['actie'] ?? '';

    // ---- Plek opslaan (nieuw of wijzigen) ----
    if ($actie === 'opslaan') {
        foreach (['naam', 'address', 'city', 'permit_number', 'permit_end_date'] as $veld) {
            $plek[$veld] = trim($_POST[$veld] ?? '');
            if ($plek[$veld] === '') {
                $fouten[$veld] = 'Dit veld is verplicht.';
            }
        }
        $plek['id'] = (int) ($_POST['id'] ?? 0);

        if (!isset($fouten['permit_end_date']) && !is_geldige_datum($plek['permit_end_date'])) {
            $fouten['permit_end_date'] = 'Vul een geldige datum in.';
        }

        if (empty($fouten)) {
            $waarden = [$plek['naam'], $plek['address'], $plek['city'], $plek['permit_number'], $plek['permit_end_date']];

            if ($plek['id'] > 0) {
                $query = $db->prepare('UPDATE locations SET naam = ?, address = ?, city = ?, permit_number = ?, permit_end_date = ? WHERE id = ?');
                $query->execute([...$waarden, $plek['id']]);
                zet_melding('succes', 'Plek is gewijzigd.');
                ga_naar('beheer/plekken.php?bewerk=' . $plek['id']);
            } else {
                $query = $db->prepare('INSERT INTO locations (naam, address, city, permit_number, permit_end_date) VALUES (?, ?, ?, ?, ?)');
                $query->execute($waarden);
                zet_melding('succes', 'Plek is toegevoegd. Vul nu de openingstijden in.');
                ga_naar('beheer/plekken.php?bewerk=' . $db->lastInsertId());
            }
        }
    }

    // ---- Plek verwijderen ----
    if ($actie === 'verwijderen') {
        $id = (int) ($_POST['id'] ?? 0);

        $query = $db->prepare('SELECT COUNT(*) FROM stops WHERE location_id = ?');
        $query->execute([$id]);

        if ($query->fetchColumn() > 0) {
            zet_melding('fout', 'Deze plek wordt nog gebruikt in een route en kan niet worden verwijderd.');
        } else {
            // De openingstijden gaan automatisch mee (ON DELETE CASCADE)
            $query = $db->prepare('DELETE FROM locations WHERE id = ?');
            $query->execute([$id]);
            zet_melding('succes', 'Plek is verwijderd.');
        }
        ga_naar('beheer/plekken.php');
    }

    // ---- Openingstijd opslaan voor één dag ----
    if ($actie === 'tijd_opslaan') {
        $plekId = (int) ($_POST['location_id'] ?? 0);
        $dag = (int) ($_POST['day'] ?? 0);
        $open = $_POST['open_time'] ?? '';
        $dicht = $_POST['close_time'] ?? '';

        // Bestaat de plek wel?
        $query = $db->prepare('SELECT COUNT(*) FROM locations WHERE id = ?');
        $query->execute([$plekId]);

        if ($query->fetchColumn() == 0) {
            zet_melding('fout', 'Deze plek bestaat niet.');
            ga_naar('beheer/plekken.php');
        } elseif ($dag < 1 || $dag > 7 || !is_geldige_tijd($open) || !is_geldige_tijd($dicht)) {
            zet_melding('fout', 'Vul een geldige dag en tijden in.');
        } elseif ($dicht <= $open) {
            zet_melding('fout', 'De sluitingstijd moet na de openingstijd liggen.');
        } else {
            // Heeft deze dag al een tijd? Dan eerst weghalen, zodat er per dag maar één regel is.
            $db->prepare('DELETE FROM opening_hours WHERE location_id = ? AND day = ?')->execute([$plekId, $dag]);
            $query = $db->prepare('INSERT INTO opening_hours (location_id, day, open_time, close_time) VALUES (?, ?, ?, ?)');
            $query->execute([$plekId, $dag, $open, $dicht]);
            zet_melding('succes', 'Openingstijd voor ' . dagnaam($dag) . ' is opgeslagen.');
        }
        ga_naar('beheer/plekken.php?bewerk=' . $plekId);
    }

    // ---- Openingstijd verwijderen (plek is die dag dicht) ----
    if ($actie === 'tijd_verwijderen') {
        $plekId = (int) ($_POST['location_id'] ?? 0);
        $query = $db->prepare('DELETE FROM opening_hours WHERE id = ? AND location_id = ?');
        $query->execute([(int) ($_POST['id'] ?? 0), $plekId]);
        zet_melding('succes', 'Openingstijd is verwijderd. De plek is die dag dicht.');
        ga_naar('beheer/plekken.php?bewerk=' . $plekId);
    }
}

// ---- Wijzigen: plek en openingstijden ophalen ----
$openingstijden = [];
if (isset($_GET['bewerk']) && empty($fouten)) {
    $query = $db->prepare('SELECT * FROM locations WHERE id = ?');
    $query->execute([(int) $_GET['bewerk']]);
    $plek = $query->fetch() ?: $plek;
}
if ($plek['id']) {
    $query = $db->prepare('SELECT * FROM opening_hours WHERE location_id = ? ORDER BY day');
    $query->execute([$plek['id']]);
    $openingstijden = $query->fetchAll();
}

$plekken = $db->query('SELECT * FROM locations ORDER BY city, naam')->fetchAll();

$titel = 'Plekken';
require __DIR__ . '/../includes/header.php';
?>

<h1>Plekken</h1>

<div class="kaart">
    <?php if (!$plekken): ?>
        <p>Er zijn nog geen plekken.</p>
    <?php else: ?>
        <table class="tabel">
            <tr>
                <th>Plek</th>
                <th>Adres</th>
                <th>Vergunning</th>
                <th>Geldig t/m</th>
                <th>Acties</th>
            </tr>
            <?php foreach ($plekken as $p): ?>
                <tr>
                    <td><?= e($p['naam']) ?></td>
                    <td><?= e($p['address']) ?>, <?= e($p['city']) ?></td>
                    <td><?= e($p['permit_number']) ?></td>
                    <td>
                        <?= e(date('d-m-Y', strtotime($p['permit_end_date']))) ?>
                        <?php if (vergunning_verlopen($p['permit_end_date'], $vandaag)): ?>
                            <span class="label label-rood">Verlopen</span>
                        <?php else: ?>
                            <span class="label label-groen">Geldig</span>
                        <?php endif; ?>
                    </td>
                    <td class="acties">
                        <a class="knop knop-klein" href="?bewerk=<?= e($p['id']) ?>">Wijzigen</a>
                        <form method="post" onsubmit="return confirm('Weet je zeker dat je deze plek wilt verwijderen?');">
                            <?= csrf_veld() ?>
                            <input type="hidden" name="actie" value="verwijderen">
                            <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                            <button type="submit" class="knop knop-klein knop-rood">Verwijderen</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<div class="kaart">
    <h2><?= $plek['id'] ? 'Plek wijzigen' : 'Nieuwe plek' ?></h2>

    <?php if ($fouten): ?>
        <p class="melding melding-fout"><strong>Fout:</strong> De plek is niet opgeslagen. Controleer de velden.</p>
    <?php endif; ?>

    <form method="post" class="formulier">
        <?= csrf_veld() ?>
        <input type="hidden" name="actie" value="opslaan">
        <input type="hidden" name="id" value="<?= e($plek['id']) ?>">

        <?php
        // Alle velden hebben dezelfde opbouw. Daarom één lijst en een lus (geen dubbele code).
        $velden = [
            'naam' => ['Naam van de plek', 'text'],
            'address' => ['Adres', 'text'],
            'city' => ['Plaats', 'text'],
            'permit_number' => ['Vergunningnummer', 'text'],
            'permit_end_date' => ['Vergunning geldig tot en met', 'date'],
        ];
        foreach ($velden as $naam => [$label, $type]): ?>
            <label for="<?= $naam ?>"><?= $label ?></label>
            <input type="<?= $type ?>" id="<?= $naam ?>" name="<?= $naam ?>" value="<?= e($plek[$naam]) ?>">
            <?php if (isset($fouten[$naam])): ?><span class="veld-fout"><?= e($fouten[$naam]) ?></span><?php endif; ?>
        <?php endforeach; ?>

        <button type="submit" class="knop">Opslaan</button>
        <?php if ($plek['id']): ?>
            <a href="plekken.php">Annuleren</a>
        <?php endif; ?>
    </form>
</div>

<?php if ($plek['id']): ?>
    <div class="kaart">
        <h2>Openingstijden van <?= e($plek['naam']) ?></h2>
        <p>Staat een dag er niet bij? Dan is de plek die dag dicht.</p>

        <table class="tabel">
            <tr>
                <th>Dag</th>
                <th>Open</th>
                <th>Dicht</th>
                <th>Actie</th>
            </tr>
            <?php foreach ($openingstijden as $tijd): ?>
                <tr>
                    <td><?= e(dagnaam($tijd['day'])) ?></td>
                    <td><?= e(substr($tijd['open_time'], 0, 5)) ?></td>
                    <td><?= e(substr($tijd['close_time'], 0, 5)) ?></td>
                    <td>
                        <form method="post">
                            <?= csrf_veld() ?>
                            <input type="hidden" name="actie" value="tijd_verwijderen">
                            <input type="hidden" name="id" value="<?= e($tijd['id']) ?>">
                            <input type="hidden" name="location_id" value="<?= e($plek['id']) ?>">
                            <button type="submit" class="knop knop-klein knop-rood">Verwijderen</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <h3>Openingstijd toevoegen of wijzigen</h3>
        <form method="post" class="formulier">
            <?= csrf_veld() ?>
            <input type="hidden" name="actie" value="tijd_opslaan">
            <input type="hidden" name="location_id" value="<?= e($plek['id']) ?>">

            <label for="day">Dag</label>
            <select id="day" name="day">
                <?php for ($dag = 1; $dag <= 7; $dag++): ?>
                    <option value="<?= $dag ?>"><?= dagnaam($dag) ?></option>
                <?php endfor; ?>
            </select>

            <label for="open_time">Open vanaf</label>
            <input type="time" id="open_time" name="open_time" required>

            <label for="close_time">Dicht om</label>
            <input type="time" id="close_time" name="close_time" required>

            <button type="submit" class="knop">Openingstijd opslaan</button>
        </form>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
