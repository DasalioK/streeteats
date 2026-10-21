<?php
/*
 * INTEGRATIETESTS: INLOGGEN, ROLLEN EN WACHTWOORDEN (met de testdatabase)
 * Hoort bij FE-15, TE-03 en TE-06.
 */

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

final class InloggenTest extends TestCase
{
    protected function setUp(): void
    {
        reset_testdatabase();
        $_SESSION = [];
    }

    #[Group('FE-15')]
    #[TestDox('IT23 · Zonder sessie is er niemand ingelogd')]
    public function testNiemandIngelogd(): void
    {
        $this->assertNull(ingelogde_gebruiker());
    }

    #[Group('FE-15')]
    #[TestDox('IT24 · Met user_id 2 in de sessie is de planner ingelogd, met de juiste rol')]
    public function testPlannerIngelogd(): void
    {
        $_SESSION['user_id'] = 2;
        $gebruiker = ingelogde_gebruiker();
        $this->assertSame('planner@example.com', $gebruiker['email']);
        $this->assertSame('planner', $gebruiker['role']);
    }

    #[Group('FE-15')]
    #[TestDox('IT25 · Randgeval: een geblokkeerd account verliest meteen de toegang, ook met een geldige sessie')]
    public function testGeblokkeerd(): void
    {
        $_SESSION['user_id'] = 2;
        $GLOBALS['db']->exec('UPDATE users SET actief = 0 WHERE id = 2');
        $this->assertNull(ingelogde_gebruiker());
    }

    #[Group('FE-15')]
    #[TestDox('IT26 · Unhappy flow: een user_id die niet bestaat, geeft geen ingelogde gebruiker')]
    public function testOnbekendeGebruiker(): void
    {
        $_SESSION['user_id'] = 999;
        $this->assertNull(ingelogde_gebruiker());
    }

    #[Group('TE-03')]
    #[TestDox('IT27 · Wachtwoorden staan als bcrypt-hash in de database, niet als leesbare tekst')]
    public function testWachtwoordGehasht(): void
    {
        $hashes = $GLOBALS['db']->query('SELECT password_hash FROM users')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($hashes as $hash) {
            $this->assertStringStartsWith('$2y$', $hash);
            $this->assertNotSame('demo-password', $hash);
            $this->assertTrue(password_verify('demo-password', $hash));
            $this->assertFalse(password_verify('fout', $hash));
        }
    }

    #[Group('TE-03')]
    #[TestDox('IT28 · Randgeval: hetzelfde wachtwoord geeft elke keer een andere hash (salt)')]
    public function testSalt(): void
    {
        $this->assertNotSame(password_hash('demo-password', PASSWORD_DEFAULT), password_hash('demo-password', PASSWORD_DEFAULT));
    }

    // ---------- vereis_rol(): wie mag op een beheerpagina komen? (FE-15, TE-06) ----------

    #[Group('FE-15')]
    #[TestDox('IT29 · Unhappy flow: een bezoeker (niet ingelogd) op een beheerpagina wordt naar inloggen gestuurd')]
    public function testVereisRolGast(): void
    {
        try {
            vereis_rol(['planner', 'beheerder']);
            $this->fail('De bezoeker had niet verder mogen komen');
        } catch (RuntimeException $e) {
            $this->assertSame('fout', $_SESSION['melding']['soort']);
            $this->assertSame('Log eerst in om deze pagina te bekijken.', $_SESSION['melding']['tekst']);
        }
    }

    #[Group('FE-15')]
    #[TestDox('IT30 · Unhappy flow: een planner op de pagina Accounts (alleen beheerder) krijgt "geen toegang"')]
    public function testVereisRolVerkeerdeRol(): void
    {
        $_SESSION['user_id'] = 2;   // planner
        ob_start();
        try {
            vereis_rol(['beheerder']);
            ob_end_clean();
            $this->fail('De planner had geen toegang mogen krijgen');
        } catch (RuntimeException $e) {
            $html = ob_get_clean();
            $this->assertStringContainsString('Je hebt geen toegang tot deze pagina.', $html);
        }
    }

    #[Group('FE-15')]
    #[TestDox('IT31 · Happy flow: een beheerder op de pagina Accounts komt er wel in')]
    public function testVereisRolGoed(): void
    {
        $_SESSION['user_id'] = 1;   // beheerder
        $gebruiker = vereis_rol(['beheerder']);
        $this->assertSame('beheerder', $gebruiker['role']);
    }

    // ---------- controleer_csrf(): geheime code bij elk formulier (TE-06) ----------

    #[Group('TE-06')]
    #[TestDox('IT32 · Happy flow: een formulier met de juiste geheime code wordt doorgelaten')]
    public function testCsrfGoed(): void
    {
        csrf_veld();   // maakt de code in de sessie
        $_POST['csrf'] = $_SESSION['csrf'];
        controleer_csrf();
        $this->assertTrue(true, 'Geen stop: het formulier mag verder');
        unset($_POST['csrf']);
    }

    #[Group('TE-06')]
    #[TestDox('IT33 · Unhappy flow: een formulier zonder geheime code wordt geweigerd')]
    public function testCsrfZonderCode(): void
    {
        unset($_POST['csrf']);
        ob_start();
        try {
            controleer_csrf();
            ob_end_clean();
            $this->fail('Het formulier had geweigerd moeten worden');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Je sessie is verlopen', ob_get_clean());
        }
    }

    #[Group('TE-06')]
    #[TestDox('IT34 · Unhappy flow: een formulier met een verkeerde (nep) geheime code wordt geweigerd')]
    public function testCsrfNepCode(): void
    {
        csrf_veld();
        $_POST['csrf'] = str_repeat('a', 64);
        ob_start();
        try {
            controleer_csrf();
            ob_end_clean();
            $this->fail('Het formulier had geweigerd moeten worden');
        } catch (RuntimeException $e) {
            ob_end_clean();
            $this->assertSame('Script gestopt', $e->getMessage());
        }
        unset($_POST['csrf']);
    }

    #[TestDox('IT35 · ga_naar() stuurt door en stopt daarna het script')]
    public function testGaNaarStopt(): void
    {
        $this->expectException(RuntimeException::class);
        ga_naar('login.php');
    }

    #[Group('TE-04')]
    #[TestDox('IT36 · SQL-injectie: een prepared statement behandelt \' OR \'1\'=\'1 als gewone tekst')]
    public function testSqlInjectie(): void
    {
        $query = $GLOBALS['db']->prepare('SELECT * FROM users WHERE email = ?');
        $query->execute(["' OR '1'='1"]);
        $this->assertFalse($query->fetch());
    }
}
