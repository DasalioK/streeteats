<?php
/*
 * INTEGRATIETESTS: DE REGELS VAN DE PLANNING SAMEN MET DE DATABASE
 * Hoort bij checklistpunt 4 (onderdelen samen testen) en 6 (normaal, fout en randgeval).
 *
 * Uitleg: deze tests gebruiken de echte functies uit includes/controles.php samen met de
 * testdatabase "streeteats_test". Vóór elke test wordt dezelfde testdata teruggezet.
 *
 * Testdata (vandaag):  Burger Beast  Dam          11:00-14:00
 *                      Burger Beast  Museumplein  15:00-19:00
 *                      Pizza Wheels  Grote Markt  11:30-16:00   (Grote Markt open 10:00-18:00)
 *                      Green Bites   Westerpark   12:00-18:00   (conceptroute)
 * Testdata (morgen):   Taco Truck    NDSM         13:00-20:00   (vergunning NDSM verlopen)
 */

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

final class PlanningsregelsTest extends TestCase
{
    private PDO $db;
    private string $vandaag;
    private string $morgen;

    // Truck- en plek-id's uit de testdata
    private const BURGER = 1, TACO = 2, PIZZA = 3, GREEN = 4;
    private const DAM = 1, MUSEUMPLEIN = 2, WESTERPARK = 3, NDSM = 4, GROTE_MARKT = 5;

    protected function setUp(): void
    {
        reset_testdatabase();
        $this->db = $GLOBALS['db'];
        $this->vandaag = date('Y-m-d');
        $this->morgen = date('Y-m-d', strtotime('+1 day'));
    }

    private function route(int $id): array
    {
        $query = $this->db->prepare('SELECT * FROM routes WHERE id = ?');
        $query->execute([$id]);
        return $query->fetch();
    }

    // ================= FE-10: dubbele planning =================

    #[Group('FE-10')]
    #[TestDox('IT01 · Happy flow: een truck die nog vrij is, mag op een open plek staan')]
    public function testVrijeTruck(): void
    {
        $this->assertSame([], controleer_stop($this->db, $this->vandaag, self::TACO, self::DAM, '15:00', '17:00'));
    }

    #[Group('FE-10')]
    #[TestDox('IT02 · Unhappy flow: overlap wordt gevonden en de melding noemt truck, plek en tijden')]
    public function testOverlap(): void
    {
        $fouten = controleer_stop($this->db, $this->vandaag, self::BURGER, self::WESTERPARK, '13:00', '15:00');
        $this->assertCount(1, $fouten);
        $this->assertSame('Burger Beast staat al op Dam van 11:00 tot 14:00. Een truck kan niet op twee plekken tegelijk staan.', $fouten[0]);
    }

    #[Group('FE-10')]
    #[TestDox('IT03 · Randgeval: precies aansluiten (14:00 na 11:00-14:00, en tot 15:00 vóór 15:00-19:00) is GEEN overlap')]
    public function testAansluiten(): void
    {
        $this->assertSame([], controleer_stop($this->db, $this->vandaag, self::BURGER, self::WESTERPARK, '14:00', '15:00'));
    }

    #[Group('FE-10')]
    #[TestDox('IT04 · Randgeval: één minuut overlap (13:59) wordt wél gevonden')]
    public function testEenMinuutOverlap(): void
    {
        $this->assertCount(1, controleer_stop($this->db, $this->vandaag, self::BURGER, self::WESTERPARK, '13:59', '14:30'));
    }

    #[Group('FE-10')]
    #[TestDox('IT05 · Randgeval: een stop die twee bestaande stops omsluit, geeft twee meldingen')]
    public function testOmsluiten(): void
    {
        $this->assertCount(2, controleer_stop($this->db, $this->vandaag, self::BURGER, self::DAM, '10:00', '20:00'));
    }

    #[Group('FE-10')]
    #[TestDox('IT06 · Dezelfde tijden op een andere dag zijn geen overlap')]
    public function testAndereDag(): void
    {
        $this->assertSame([], controleer_stop($this->db, $this->morgen, self::BURGER, self::DAM, '11:00', '14:00'));
    }

    #[Group('FE-10')]
    #[TestDox('IT07 · Twee verschillende trucks mogen tegelijk op dezelfde plek staan')]
    public function testAndereTruckZelfdePlek(): void
    {
        $this->assertSame([], controleer_stop($this->db, $this->vandaag, self::TACO, self::DAM, '11:00', '14:00'));
    }

    #[Group('FE-10')]
    #[TestDox('IT08 · Bij het opnieuw controleren van een bestaande stop botst die stop niet met zichzelf')]
    public function testNegeerZichzelf(): void
    {
        $this->assertSame([], controleer_stop($this->db, $this->vandaag, self::BURGER, self::DAM, '11:00', '14:00', 1));
    }

    // ================= FE-10 / ontwerp H9: plek dicht =================

    #[Group('FE-10')]
    #[TestDox('IT09 · Randgeval: precies tot sluitingstijd (16:00-18:00 op de Grote Markt) mag')]
    public function testTotSluitingstijd(): void
    {
        $this->assertSame([], controleer_stop($this->db, $this->vandaag, self::PIZZA, self::GROTE_MARKT, '16:00', '18:00'));
    }

    #[Group('FE-10')]
    #[TestDox('IT10 · Unhappy flow: één minuut na sluitingstijd (18:01) wordt geweigerd')]
    public function testNaSluitingstijd(): void
    {
        $fouten = controleer_stop($this->db, $this->vandaag, self::TACO, self::GROTE_MARKT, '17:00', '18:01');
        $this->assertCount(1, $fouten);
        $this->assertStringContainsString('alleen open van 10:00 tot 18:00', $fouten[0]);
    }

    #[Group('FE-10')]
    #[TestDox('IT11 · Unhappy flow: vóór openingstijd (10:59 in het Westerpark, open vanaf 11:00) wordt geweigerd')]
    public function testVoorOpeningstijd(): void
    {
        $this->assertStringContainsString('alleen open van 11:00 tot 21:00', controleer_stop($this->db, $this->vandaag, self::TACO, self::WESTERPARK, '10:59', '12:00')[0]);
    }

    #[Group('FE-07')]
    #[TestDox('IT12 · Unhappy flow: een plek zonder openingstijd op die dag is dicht')]
    public function testPlekDicht(): void
    {
        $dag = (int) date('N');
        $this->db->prepare('DELETE FROM opening_hours WHERE location_id = ? AND day = ?')->execute([self::GROTE_MARKT, $dag]);

        $fouten = controleer_stop($this->db, $this->vandaag, self::TACO, self::GROTE_MARKT, '12:00', '13:00');
        $this->assertSame(['Grote Markt is op ' . dagnaam($dag) . ' dicht.'], $fouten);
    }

    // ================= FE-11 / FE-12: vergunning en goedkeuren =================

    #[Group('FE-11')]
    #[TestDox('IT13 · De route van morgen met NDSM heeft een verlopen vergunning')]
    public function testVerlopenVergunning(): void
    {
        $this->assertSame(['NDSM'], verlopen_vergunningen($this->db, $this->route(3)));
    }

    #[Group('FE-11')]
    #[TestDox('IT14 · De goedgekeurde route van vandaag heeft alleen geldige vergunningen')]
    public function testGeldigeVergunningen(): void
    {
        $this->assertSame([], verlopen_vergunningen($this->db, $this->route(1)));
    }

    #[Group('FE-11')]
    #[TestDox('IT15 · Randgeval: een vergunning die vandaag afloopt, is vandaag nog geldig')]
    public function testVergunningVandaagAf(): void
    {
        $this->db->prepare('UPDATE locations SET permit_end_date = ? WHERE id = ?')->execute([$this->vandaag, self::WESTERPARK]);
        $this->assertSame([], verlopen_vergunningen($this->db, $this->route(2)));
    }

    #[Group('FE-12')]
    #[TestDox('IT16 · Unhappy flow: een route met een verlopen vergunning mag niet worden goedgekeurd')]
    public function testGoedkeurenVerlopen(): void
    {
        $redenen = redenen_niet_goedkeuren($this->db, $this->route(3));
        $this->assertContains('De vergunning van NDSM is verlopen. De route kan niet worden goedgekeurd.', $redenen);
    }

    #[Group('FE-12')]
    #[TestDox('IT17 · Happy flow: de conceptroute van vandaag mag worden goedgekeurd')]
    public function testGoedkeurenMag(): void
    {
        $this->assertSame([], redenen_niet_goedkeuren($this->db, $this->route(2)));
    }

    #[Group('FE-12')]
    #[TestDox('IT18 · Unhappy flow: een route zonder stops mag niet worden goedgekeurd')]
    public function testGoedkeurenLeeg(): void
    {
        $this->db->exec("INSERT INTO routes (route_date, status, created_by) VALUES (CURDATE(), 'concept', 2)");
        $leeg = $this->route((int) $this->db->lastInsertId());
        $this->assertSame(['De route heeft nog geen stops.'], redenen_niet_goedkeuren($this->db, $leeg));
    }

    #[Group('FE-12')]
    #[TestDox('IT19 · Randgeval: een overlap die later is ontstaan, wordt bij goedkeuren toch gevonden')]
    public function testGoedkeurenLatereOverlap(): void
    {
        // Buiten de controle om een botsende stop in de conceptroute zetten
        $this->db->exec("INSERT INTO stops (route_id, truck_id, location_id, start_time, end_time, stop_order) VALUES (2, 1, 3, '12:00', '13:00', 2)");
        $redenen = redenen_niet_goedkeuren($this->db, $this->route(2));
        $this->assertNotEmpty($redenen);
        $this->assertStringContainsString('Burger Beast staat al op Dam', $redenen[0]);
    }

    // ================= TE-02: database betrouwbaar =================

    #[Group('TE-02')]
    #[TestDox('IT20 · Foreign key: een stop met een truck die niet bestaat, wordt door de database geweigerd')]
    public function testForeignKey(): void
    {
        $this->expectException(PDOException::class);
        $this->db->exec("INSERT INTO stops (route_id, truck_id, location_id, start_time, end_time, stop_order) VALUES (1, 999, 1, '10:00', '11:00', 9)");
    }

    #[Group('TE-02')]
    #[TestDox('IT21 · ON DELETE CASCADE: als een route wordt verwijderd, gaan de stops mee')]
    public function testCascade(): void
    {
        $this->db->exec('DELETE FROM routes WHERE id = 1');
        $this->assertSame(0, (int) $this->db->query('SELECT COUNT(*) FROM stops WHERE route_id = 1')->fetchColumn());
    }

    #[Group('TE-02')]
    #[TestDox('IT22 · Alle 7 tabellen uit het ERD bestaan, met 6 foreign keys')]
    public function testTabellen(): void
    {
        $tabellen = $this->db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        foreach (['users', 'trucks', 'locations', 'opening_hours', 'routes', 'stops', 'menu_items'] as $tabel) {
            $this->assertContains($tabel, $tabellen);
        }
        $fks = $this->db->query("SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = 'streeteats_test'")->fetchColumn();
        $this->assertSame(6, (int) $fks);
    }
}
