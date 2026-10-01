<?php
/*
 * FOODTRUCKS BEHEREN
 * Technisch ontwerp H5: "Trucks - Planner/beheerder - Trucks toevoegen, wijzigen en verwijderen"
 * Hoort bij: FE4 (trucks en plekken beheren), TE4 (invoer controleren), TE5 (rechten)
 *
 * Uitleg - deze ene pagina doet alles:
 * - Bovenaan: een lijst van alle trucks.
 * - Onderaan: een formulier. Zonder ?bewerk=.. is het "nieuwe truck",
 *   met ?bewerk=3 wordt truck 3 in het formulier gezet om te wijzigen.
 * - Verwijderen gaat met een klein formulier per truck (POST, met CSRF-code).
 */

require __DIR__ . '/../includes/login.php';

vereis_rol(['planner', 'beheerder']);

$fouten = [];
$truck = ['id' => '', 'naam' => '', 'kitchen_type' => '', 'description' => ''];

// ---- Formulier verstuurd ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controleer_csrf();
    $actie = $_POST['actie'] ?? '';

    // Opslaan: nieuw (geen id) of wijzigen (wel een id)
    if ($actie === 'opslaan') {
        $truck['id'] = (int) ($_POST['id'] ?? 0);
        $truck['naam'] = trim($_POST['naam'] ?? '');
        $truck['kitchen_type'] = trim($_POST['kitchen_type'] ?? '');
        $truck['description'] = trim($_POST['description'] ?? '');

        // Server controleert alles zelf; maxlength in HTML is alleen extra gemak
        if ($truck['naam'] === '') {
            $fouten['naam'] = 'Dit veld is verplicht.';
        } elseif (mb_strlen($truck['naam']) > 100) {
            $fouten['naam'] = 'Maximaal 100 tekens.';
        }
        if ($truck['kitchen_type'] === '') {
            $fouten['kitchen_type'] = 'Dit veld is verplicht.';
        } elseif (mb_strlen($truck['kitchen_type']) > 50) {
            $fouten['kitchen_type'] = 'Maximaal 50 tekens.';
        }

        if (empty($fouten)) {
            if ($truck['id'] > 0) {
                $query = $db->prepare('UPDATE trucks SET naam = ?, kitchen_type = ?, description = ? WHERE id = ?');
                $query->execute([$truck['naam'], $truck['kitchen_type'], $truck['description'], $truck['id']]);
                zet_melding('succes', 'Foodtruck is gewijzigd.');
            } else {
                $query = $db->prepare('INSERT INTO trucks (naam, kitchen_type, description) VALUES (?, ?, ?)');
                $query->execute([$truck['naam'], $truck['kitchen_type'], $truck['description']]);
                zet_melding('succes', 'Foodtruck is toegevoegd.');
            }
            ga_naar('beheer/trucks.php');
        }
    }

    // Verwijderen
    if ($actie === 'verwijderen') {
        $id = (int) ($_POST['id'] ?? 0);

        // Staat de truck nog in een route? Dan mag hij niet weg, anders klopt de planning niet meer.
        $query = $db->prepare('SELECT COUNT(*) FROM stops WHERE truck_id = ?');
        $query->execute([$id]);

        if ($query->fetchColumn() > 0) {
            zet_melding('fout', 'Deze foodtruck staat nog in een route en kan niet worden verwijderd.');
        } else {
            // Het menu van de truck gaat automatisch mee (ON DELETE CASCADE in de database)
            $query = $db->prepare('DELETE FROM trucks WHERE id = ?');
            $query->execute([$id]);
            zet_melding('succes', 'Foodtruck is verwijderd.');
        }
        ga_naar('beheer/trucks.php');
    }
}

// ---- Wijzigen: truck ophalen om in het formulier te zetten ----
if (isset($_GET['bewerk']) && empty($fouten)) {
    $query = $db->prepare('SELECT * FROM trucks WHERE id = ?');
    $query->execute([(int) $_GET['bewerk']]);
    $truck = $query->fetch() ?: $truck;
}

$trucks = $db->query('SELECT * FROM trucks ORDER BY naam')->fetchAll();

$titel = 'Foodtrucks';
require __DIR__ . '/../includes/header.php';
?>

<h1>Foodtrucks</h1>

<div class="kaart">
    <?php if (!$trucks): ?>
        <p>Er zijn nog geen foodtrucks.</p>
    <?php else: ?>
        <table class="tabel">
            <tr>
                <th>Naam</th>
                <th>Soort eten</th>
                <th>Beschrijving</th>
                <th>Acties</th>
            </tr>
            <?php foreach ($trucks as $t): ?>
                <tr>
                    <td><?= e($t['naam']) ?></td>
                    <td><?= e($t['kitchen_type']) ?></td>
                    <td><?= e($t['description']) ?></td>
                    <td class="acties">
                        <a class="knop knop-klein" href="?bewerk=<?= e($t['id']) ?>">Wijzigen</a>
                        <form method="post" onsubmit="return confirm('Weet je zeker dat je deze truck wilt verwijderen?');">
                            <?= csrf_veld() ?>
                            <input type="hidden" name="actie" value="verwijderen">
                            <input type="hidden" name="id" value="<?= e($t['id']) ?>">
                            <button type="submit" class="knop knop-klein knop-rood">Verwijderen</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<div class="kaart">
    <h2><?= $truck['id'] ? 'Foodtruck wijzigen' : 'Nieuwe foodtruck' ?></h2>

    <?php if ($fouten): ?>
        <p class="melding melding-fout"><strong>Fout:</strong> De foodtruck is niet opgeslagen. Controleer de velden.</p>
    <?php endif; ?>

    <form method="post" class="formulier">
        <?= csrf_veld() ?>
        <input type="hidden" name="actie" value="opslaan">
        <input type="hidden" name="id" value="<?= e($truck['id']) ?>">

        <label for="naam">Naam</label>
        <input type="text" id="naam" name="naam" maxlength="100" value="<?= e($truck['naam']) ?>">
        <?php if (isset($fouten['naam'])): ?><span class="veld-fout"><?= e($fouten['naam']) ?></span><?php endif; ?>

        <label for="kitchen_type">Soort eten</label>
        <input type="text" id="kitchen_type" name="kitchen_type" maxlength="50" value="<?= e($truck['kitchen_type']) ?>">
        <?php if (isset($fouten['kitchen_type'])): ?><span class="veld-fout"><?= e($fouten['kitchen_type']) ?></span><?php endif; ?>

        <label for="description">Beschrijving</label>
        <textarea id="description" name="description" rows="3"><?= e($truck['description']) ?></textarea>

        <button type="submit" class="knop">Opslaan</button>
        <?php if ($truck['id']): ?>
            <a href="trucks.php">Annuleren</a>
        <?php endif; ?>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
