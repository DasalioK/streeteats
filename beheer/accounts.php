<?php
/*
 * ACCOUNTS BEHEREN
 * Technisch ontwerp H5: "Accounts - Beheerder - Accounts van planners maken en blokkeren"
 * Hoort bij: TE4 (wachtwoord hashen, invoer controleren) en TE5 (rechten)
 *
 * Uitleg:
 * - Alleen de beheerder mag deze pagina openen (vereis_rol).
 * - Nieuw account: we controleren de invoer en slaan het wachtwoord op als hash.
 * - Blokkeren: actief wordt 0. Die gebruiker kan dan niet meer inloggen,
 *   en als die al ingelogd is, verliest die meteen de toegang.
 */

require __DIR__ . '/../includes/login.php';

$beheerder = vereis_rol(['beheerder']);

$fouten = [];                                  // foutmelding per veld
$invoer = ['naam' => '', 'email' => ''];       // zodat het formulier ingevuld blijft na een fout

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controleer_csrf();
    $actie = $_POST['actie'] ?? '';

    // ---- Actie 1: nieuw planneraccount maken ----
    if ($actie === 'nieuw') {
        $invoer['naam'] = trim($_POST['naam'] ?? '');
        $invoer['email'] = trim($_POST['email'] ?? '');
        $wachtwoord = $_POST['wachtwoord'] ?? '';

        // Invoer controleren (TE4)
        if ($invoer['naam'] === '') {
            $fouten['naam'] = 'Dit veld is verplicht.';
        }
        if (!filter_var($invoer['email'], FILTER_VALIDATE_EMAIL)) {
            $fouten['email'] = 'Vul een geldig e-mailadres in.';
        }
        if (strlen($wachtwoord) < 8) {
            $fouten['wachtwoord'] = 'Het wachtwoord moet minstens 8 tekens hebben.';
        }

        // Bestaat dit e-mailadres al?
        if (empty($fouten['email'])) {
            $query = $db->prepare('SELECT id FROM users WHERE email = ?');
            $query->execute([$invoer['email']]);
            if ($query->fetch()) {
                $fouten['email'] = 'Er is al een account met dit e-mailadres.';
            }
        }

        // Alles goed? Dan opslaan. password_hash() maakt van het wachtwoord een onleesbare hash (TE4).
        if (empty($fouten)) {
            $query = $db->prepare('INSERT INTO users (naam, email, password_hash, role) VALUES (?, ?, ?, ?)');
            $query->execute([$invoer['naam'], $invoer['email'], password_hash($wachtwoord, PASSWORD_DEFAULT), 'planner']);

            zet_melding('succes', 'Account voor ' . $invoer['naam'] . ' is aangemaakt.');
            ga_naar('beheer/accounts.php');
        }
    }

    // ---- Actie 2: account blokkeren of weer toestaan ----
    if ($actie === 'blokkeren') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id === (int) $beheerder['id']) {
            zet_melding('fout', 'Je kunt je eigen account niet blokkeren.');
        } else {
            // 1 - actief: van 1 wordt 0 (geblokkeerd) en van 0 wordt 1 (weer actief)
            $query = $db->prepare('UPDATE users SET actief = 1 - actief WHERE id = ?');
            $query->execute([$id]);
            zet_melding('succes', 'De status van het account is aangepast.');
        }
        ga_naar('beheer/accounts.php');
    }
}

// Alle accounts ophalen voor de lijst
$accounts = $db->query('SELECT id, naam, email, role, actief FROM users ORDER BY naam')->fetchAll();

$titel = 'Accounts';
require __DIR__ . '/../includes/header.php';
?>

<h1>Accounts</h1>

<?php if ($fouten): ?>
    <p class="melding melding-fout"><strong>Fout:</strong> Het account is niet opgeslagen. Controleer de velden hieronder.</p>
<?php endif; ?>

<div class="kaart">
    <h2>Alle accounts</h2>
    <table class="tabel">
        <tr>
            <th>Naam</th>
            <th>E-mail</th>
            <th>Rol</th>
            <th>Status</th>
            <th>Actie</th>
        </tr>
        <?php foreach ($accounts as $account): ?>
            <tr>
                <td><?= e($account['naam']) ?></td>
                <td><?= e($account['email']) ?></td>
                <td><?= e($account['role']) ?></td>
                <td><?= $account['actief'] ? 'Actief' : 'Geblokkeerd' ?></td>
                <td>
                    <?php if ($account['id'] != $beheerder['id']): ?>
                        <form method="post">
                            <?= csrf_veld() ?>
                            <input type="hidden" name="actie" value="blokkeren">
                            <input type="hidden" name="id" value="<?= e($account['id']) ?>">
                            <button type="submit" class="knop knop-klein">
                                <?= $account['actief'] ? 'Blokkeren' : 'Weer toestaan' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<div class="kaart">
    <h2>Nieuw planneraccount</h2>
    <form method="post" class="formulier">
        <?= csrf_veld() ?>
        <input type="hidden" name="actie" value="nieuw">

        <label for="naam">Naam</label>
        <input type="text" id="naam" name="naam" value="<?= e($invoer['naam']) ?>">
        <?php if (isset($fouten['naam'])): ?><span class="veld-fout"><?= e($fouten['naam']) ?></span><?php endif; ?>

        <label for="email">E-mailadres</label>
        <input type="email" id="email" name="email" value="<?= e($invoer['email']) ?>">
        <?php if (isset($fouten['email'])): ?><span class="veld-fout"><?= e($fouten['email']) ?></span><?php endif; ?>

        <label for="wachtwoord">Wachtwoord (minstens 8 tekens)</label>
        <input type="password" id="wachtwoord" name="wachtwoord">
        <?php if (isset($fouten['wachtwoord'])): ?><span class="veld-fout"><?= e($fouten['wachtwoord']) ?></span><?php endif; ?>

        <button type="submit" class="knop">Account aanmaken</button>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
