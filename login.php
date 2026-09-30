<?php
/*
 * LOGINPAGINA
 * Technisch ontwerp H5: "Login - Planner/beheerder - E-mail en wachtwoord invullen en inloggen"
 * Hoort bij: TE4 (wachtwoord gehasht), TE5 (login en rechten)
 */

require 'includes/login.php';

// Al ingelogd? Dan meteen door naar het beheer.
if (ingelogde_gebruiker()) {
    ga_naar('beheer/index.php');
}

$fout = '';
$email = '';

// Is het formulier verstuurd? (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controleer_csrf();

    $email = trim($_POST['email'] ?? '');
    $wachtwoord = $_POST['wachtwoord'] ?? '';

    if ($email === '' || $wachtwoord === '') {
        $fout = 'Vul je e-mailadres en wachtwoord in.';
    } else {
        // Zoek de gebruiker op e-mail. Het ? is een prepared statement: veilig tegen SQL-injectie.
        $query = $db->prepare('SELECT * FROM users WHERE email = ?');
        $query->execute([$email]);
        $gebruiker = $query->fetch();

        // password_verify() vergelijkt het ingevulde wachtwoord met de hash in de database (TE4).
        // We zeggen bewust niet óf het e-mailadres of het wachtwoord fout is: dat helpt een hacker niet.
        if (!$gebruiker || !password_verify($wachtwoord, $gebruiker['password_hash'])) {
            $fout = 'E-mailadres of wachtwoord is onjuist.';
        } elseif ($gebruiker['actief'] == 0) {
            $fout = 'Dit account is geblokkeerd. Neem contact op met de beheerder.';
        } else {
            // Gelukt: nieuwe sessie-ID (tegen het stelen van een sessie) en onthouden wie er is ingelogd
            session_regenerate_id(true);
            $_SESSION['user_id'] = $gebruiker['id'];

            zet_melding('succes', 'Welkom, ' . $gebruiker['naam'] . '.');
            ga_naar('beheer/index.php');
        }
    }
}

$titel = 'Inloggen';
require 'includes/header.php';
?>

<h1>Inloggen</h1>
<p>Alleen voor planners en beheerders.</p>

<?php if ($fout): ?>
    <p class="melding melding-fout"><strong>Fout:</strong> <?= e($fout) ?></p>
<?php endif; ?>

<form method="post" class="kaart formulier">
    <?= csrf_veld() ?>

    <label for="email">E-mailadres</label>
    <input type="email" id="email" name="email" value="<?= e($email) ?>" required>

    <label for="wachtwoord">Wachtwoord</label>
    <input type="password" id="wachtwoord" name="wachtwoord" required>

    <button type="submit" class="knop">Inloggen</button>
</form>

<?php require 'includes/footer.php'; ?>
