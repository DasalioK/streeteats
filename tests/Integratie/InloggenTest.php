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
    #[TestDox('Zonder sessie is er niemand ingelogd')]
    public function testNiemandIngelogd(): void
    {
        $this->assertNull(ingelogde_gebruiker());
    }

    #[Group('FE-15')]
    #[TestDox('Met user_id 2 in de sessie is de planner ingelogd, met de juiste rol')]
    public function testPlannerIngelogd(): void
    {
        $_SESSION['user_id'] = 2;
        $gebruiker = ingelogde_gebruiker();
        $this->assertSame('planner@example.com', $gebruiker['email']);
        $this->assertSame('planner', $gebruiker['role']);
    }

    #[Group('FE-15')]
    #[TestDox('Randgeval: een geblokkeerd account verliest meteen de toegang, ook met een geldige sessie')]
    public function testGeblokkeerd(): void
    {
        $_SESSION['user_id'] = 2;
        $GLOBALS['db']->exec('UPDATE users SET actief = 0 WHERE id = 2');
        $this->assertNull(ingelogde_gebruiker());
    }

    #[Group('FE-15')]
    #[TestDox('Unhappy flow: een user_id die niet bestaat, geeft geen ingelogde gebruiker')]
    public function testOnbekendeGebruiker(): void
    {
        $_SESSION['user_id'] = 999;
        $this->assertNull(ingelogde_gebruiker());
    }

    #[Group('TE-03')]
    #[TestDox('Wachtwoorden staan als bcrypt-hash in de database, niet als leesbare tekst')]
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
    #[TestDox('Randgeval: hetzelfde wachtwoord geeft elke keer een andere hash (salt)')]
    public function testSalt(): void
    {
        $this->assertNotSame(password_hash('demo-password', PASSWORD_DEFAULT), password_hash('demo-password', PASSWORD_DEFAULT));
    }

    #[Group('TE-04')]
    #[TestDox('SQL-injectie: een prepared statement behandelt \' OR \'1\'=\'1 als gewone tekst')]
    public function testSqlInjectie(): void
    {
        $query = $GLOBALS['db']->prepare('SELECT * FROM users WHERE email = ?');
        $query->execute(["' OR '1'='1"]);
        $this->assertFalse($query->fetch());
    }
}
