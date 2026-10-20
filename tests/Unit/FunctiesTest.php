<?php
/*
 * UNITTESTS: LOSSE FUNCTIES UIT includes/functies.php
 * Hoort bij checklistpunt 3 (losse functies testen) en 6 (normaal, fout en randgeval).
 *
 * Uitleg: een unittest test één functie los, zonder database of browser.
 * Elke test geeft de functie een invoer en controleert met assert...() of de uitkomst klopt.
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

final class FunctiesTest extends TestCase
{
    // ---------------- e(): veilig tonen (TE-05, XSS) ----------------

    #[Group('TE-05')]
    #[TestDox('e() maakt een <script>-tag onschadelijk')]
    public function testEscapeScript(): void
    {
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', e('<script>alert(1)</script>'));
    }

    #[Group('TE-05')]
    #[TestDox('e() maakt aanhalingstekens onschadelijk (randgeval: invoer in een HTML-attribuut)')]
    public function testEscapeAanhalingstekens(): void
    {
        $this->assertSame('&quot; onmouseover=&quot;x&#039;', e('" onmouseover="x\''));
    }

    #[Group('TE-05')]
    #[TestDox('e() laat gewone tekst ongemoeid, ook met é en €')]
    public function testEscapeGewoneTekst(): void
    {
        $this->assertSame('Café Crème € 9,50', e('Café Crème € 9,50'));
    }

    #[Group('TE-05')]
    #[TestDox('e() werkt ook met een getal of null (randgeval)')]
    public function testEscapeGeenTekst(): void
    {
        $this->assertSame('12', e(12));
        $this->assertSame('', e(null));
    }

    // ---------------- is_geldige_datum() (TE-04) ----------------

    public static function datums(): array
    {
        return [
            'gewone datum (happy)'               => ['2026-10-20', true],
            'laatste dag van het jaar (rand)'    => ['2026-12-31', true],
            '29 februari in schrikkeljaar (rand)' => ['2028-02-29', true],
            '29 februari geen schrikkeljaar'     => ['2026-02-29', false],
            '30 februari bestaat niet'           => ['2026-02-30', false],
            'maand 13 bestaat niet'              => ['2026-13-01', false],
            'verkeerd formaat (dd-mm-jjjj)'      => ['20-10-2026', false],
            'tekst'                              => ['morgen', false],
            'leeg'                               => ['', false],
        ];
    }

    #[DataProvider('datums')]
    #[Group('TE-04')]
    #[TestDox('is_geldige_datum("$datum") geeft $verwacht')]
    public function testGeldigeDatum(string $datum, bool $verwacht): void
    {
        $this->assertSame($verwacht, is_geldige_datum($datum));
    }

    // ---------------- is_geldige_tijd() (TE-04) ----------------

    public static function tijden(): array
    {
        return [
            'gewone tijd (happy)'          => ['13:00', true],
            'middernacht (rand)'           => ['00:00', true],
            'laatste minuut (rand)'        => ['23:59', true],
            '24:00 bestaat niet'           => ['24:00', false],
            '60 minuten bestaat niet'      => ['12:60', false],
            'zonder voorloopnul'           => ['9:30', false],
            'met seconden'                 => ['09:30:00', false],
            'leeg'                         => ['', false],
        ];
    }

    #[DataProvider('tijden')]
    #[Group('TE-04')]
    #[TestDox('is_geldige_tijd("$tijd") geeft $verwacht')]
    public function testGeldigeTijd(string $tijd, bool $verwacht): void
    {
        $this->assertSame($verwacht, is_geldige_tijd($tijd));
    }

    // ---------------- vergunning_verlopen() (FE-11) ----------------

    #[Group('FE-11')]
    #[TestDox('Vergunning is NIET verlopen op de laatste geldige dag (randgeval)')]
    public function testVergunningLaatsteDag(): void
    {
        $this->assertFalse(vergunning_verlopen('2026-10-20', '2026-10-20'));
    }

    #[Group('FE-11')]
    #[TestDox('Vergunning IS verlopen op de dag na de einddatum (randgeval)')]
    public function testVergunningDagErna(): void
    {
        $this->assertTrue(vergunning_verlopen('2026-10-20', '2026-10-21'));
    }

    #[Group('FE-11')]
    #[TestDox('Vergunning die nog lang geldig is, is niet verlopen')]
    public function testVergunningGeldig(): void
    {
        $this->assertFalse(vergunning_verlopen('2027-06-30', '2026-10-20'));
    }

    #[Group('FE-11')]
    #[TestDox('Vergunning over de jaargrens: 31-12 geldig, 1-1 verlopen (randgeval)')]
    public function testVergunningJaargrens(): void
    {
        $this->assertFalse(vergunning_verlopen('2026-12-31', '2026-12-31'));
        $this->assertTrue(vergunning_verlopen('2026-12-31', '2027-01-01'));
    }

    // ---------------- tijd(), datum_tekst(), dagnaam() ----------------

    #[Group('FE-02')]
    #[TestDox('tijd() maakt van 11:00:00 uit de database 11:00')]
    public function testTijdKorter(): void
    {
        $this->assertSame('11:00', tijd('11:00:00'));
    }

    #[Group('FE-01')]
    #[TestDox('datum_tekst() toont de dag en de datum, bijvoorbeeld "dinsdag 20-10-2026"')]
    public function testDatumTekst(): void
    {
        $this->assertSame('dinsdag 20-10-2026', datum_tekst('2026-10-20'));
        $this->assertSame('zondag 25-10-2026', datum_tekst('2026-10-25'));
    }

    #[Group('FE-07')]
    #[TestDox('dagnaam() kent maandag (1) t/m zondag (7) en geeft leeg bij 0 of 8 (randgeval)')]
    public function testDagnaam(): void
    {
        $this->assertSame('maandag', dagnaam(1));
        $this->assertSame('zondag', dagnaam(7));
        $this->assertSame('', dagnaam(0));
        $this->assertSame('', dagnaam(8));
    }

    #[TestDox('url() maakt een link binnen de website')]
    public function testUrl(): void
    {
        $this->assertSame('/streeteats/beheer/trucks.php', url('beheer/trucks.php'));
    }

    // ---------------- meldingen (FE-16) ----------------

    #[Group('FE-16')]
    #[TestDox('Een succesmelding verschijnt één keer, met het woord "Gelukt:" erbij')]
    public function testMeldingSucces(): void
    {
        zet_melding('succes', 'Foodtruck is toegevoegd.');

        ob_start();
        toon_melding();
        $html = ob_get_clean();

        $this->assertStringContainsString('melding-succes', $html);
        $this->assertStringContainsString('<strong>Gelukt:</strong> Foodtruck is toegevoegd.', $html);

        // Een tweede keer tonen: de melding is weg
        ob_start();
        toon_melding();
        $this->assertSame('', ob_get_clean());
    }

    #[Group('FE-16')]
    #[TestDox('Een foutmelding krijgt het woord "Fout:" en wordt veilig getoond')]
    public function testMeldingFout(): void
    {
        zet_melding('fout', 'Naam <b> mag niet');

        ob_start();
        toon_melding();
        $html = ob_get_clean();

        $this->assertStringContainsString('melding-fout', $html);
        $this->assertStringContainsString('<strong>Fout:</strong> Naam &lt;b&gt; mag niet', $html);
    }

    // ---------------- CSRF (TE-06) ----------------

    #[Group('TE-06')]
    #[TestDox('csrf_veld() maakt een verborgen veld met een geheime code van 64 tekens')]
    public function testCsrfVeld(): void
    {
        $veld = csrf_veld();
        $this->assertMatchesRegularExpression('/<input type="hidden" name="csrf" value="[a-f0-9]{64}">/', $veld);
        $this->assertSame($veld, csrf_veld(), 'Binnen één sessie blijft de code hetzelfde');
    }
}
