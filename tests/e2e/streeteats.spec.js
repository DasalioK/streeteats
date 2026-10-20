// END-TO-END-TESTS STREETEATS (KT1-W4)
// Uitleg: deze tests gebruiken de website zoals een echte gebruiker: muis bewegen, klikken en typen.
// Zo testen ze frontend (HTML/CSS), backend (PHP) en database (MySQL) samen.
// Van elke test maakt Playwright een video. Na elke test controleert expect() of het juiste op het scherm staat.
const { test, expect } = require('@playwright/test');
const { resetDatabase, start, pauze, klik, typ, typTijd, kies, bekijk, inloggen } = require('./hulp');

// Datum zoals de website hem toont, bijv. "dinsdag 20-10-2026" (0 = vandaag, 1 = morgen)
function datumTekst(dagenErbij) {
    const d = new Date();
    d.setDate(d.getDate() + dagenErbij);
    const dagen = ['zondag', 'maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag'];
    const tweeCijfers = n => String(n).padStart(2, '0');
    return `${dagen[d.getDay()]} ${tweeCijfers(d.getDate())}-${tweeCijfers(d.getMonth() + 1)}-${d.getFullYear()}`;
}

test.beforeEach(async ({ page }) => {
    resetDatabase();   // elke test begint met dezelfde testdata
    await start(page); // zichtbare muiscursor voor de video
});

test('1. Bezoeker zoekt een foodtruck en bekijkt plek, tijden en menu (FE-01 t/m FE-04, FE-13)', async ({ page }) => {
    await page.goto('');
    await pauze(page, 1200);

    // Happy flow: zoeken op plaats
    await typ(page, page.getByLabel('Plaats of plek'), 'Haarlem');
    await klik(page, page.getByRole('button', { name: 'Zoeken' }));
    await expect(page.getByText('1 resultaat gevonden.')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Pizza Wheels' })).toBeVisible();
    await bekijk(page, page.getByRole('heading', { name: 'Pizza Wheels' }), 1500);

    // Leeg resultaat: Green Bites staat alleen in een conceptroute, dus bezoekers zien hem niet (FE-13)
    await typ(page, page.getByLabel('Plaats of plek'), '');
    await kies(page, page.getByLabel('Soort eten'), 'Vegetarisch');
    await klik(page, page.getByRole('button', { name: 'Zoeken' }));
    await expect(page.getByText('Geen foodtrucks gevonden voor deze zoekopdracht.')).toBeVisible();
    await pauze(page, 1500);

    // Truckpagina: plek, tijden, openingstijden en menu met uitverkocht
    await kies(page, page.getByLabel('Soort eten'), 'Burgers');
    await klik(page, page.getByRole('button', { name: 'Zoeken' }));
    await klik(page, page.getByRole('link', { name: 'Menu en details' }).first());
    await expect(page.getByRole('heading', { name: 'Burger Beast' })).toBeVisible();
    await expect(page.getByText('Openingstijden van deze plek: 10:00 - 22:00 uur')).toBeVisible();
    await expect(page.locator('tr.uitverkocht', { hasText: 'Double cheese' })).toContainText('Uitverkocht');
    await bekijk(page, page.locator('tr.uitverkocht', { hasText: 'Double cheese' }), 2500);
});

test('2. Planner voegt stops toe: dubbele planning en dichte plek worden geweigerd (FE-08, FE-10)', async ({ page }) => {
    await inloggen(page, 'planner@example.com', 'demo-password');
    await expect(page.getByText('Welkom, Demo Planner.')).toBeVisible();
    await pauze(page, 1200);

    await klik(page, page.locator('.menu').getByRole('link', { name: 'Routes' }));
    const conceptVandaag = page.locator('tr', { hasText: datumTekst(0) }).filter({ hasText: 'Concept' });
    await klik(page, conceptVandaag.getByRole('link', { name: 'Openen' }));
    await expect(page.getByRole('heading', { name: 'Stop toevoegen' })).toBeVisible();

    // Unhappy flow 1: Burger Beast staat vandaag al van 11:00 tot 14:00 op de Dam
    await kies(page, page.getByLabel('Foodtruck'), 'Burger Beast');
    await kies(page, page.getByLabel('Plek'), 'Westerpark (Amsterdam)');
    await typTijd(page, page.getByLabel('Begintijd'), '13:00');
    await typTijd(page, page.getByLabel('Eindtijd'), '15:00');
    await klik(page, page.getByRole('button', { name: 'Stop toevoegen' }));
    await expect(page.getByText('Burger Beast staat al op Dam van 11:00 tot 14:00. Een truck kan niet op twee plekken tegelijk staan.')).toBeVisible();
    await bekijk(page, page.getByText('de stop is niet opgeslagen'), 2800);

    // Unhappy flow 2: de Grote Markt sluit om 18:00
    await kies(page, page.getByLabel('Foodtruck'), 'Pizza Wheels');
    await kies(page, page.getByLabel('Plek'), 'Grote Markt (Haarlem)');
    await typTijd(page, page.getByLabel('Begintijd'), '19:00');
    await typTijd(page, page.getByLabel('Eindtijd'), '20:00');
    await klik(page, page.getByRole('button', { name: 'Stop toevoegen' }));
    await expect(page.getByText('alleen open van 10:00 tot 18:00.')).toBeVisible();
    await bekijk(page, page.getByText('de stop is niet opgeslagen'), 2800);

    // Happy flow: een goede stop wordt opgeslagen; de route heeft nu meerdere stops
    await kies(page, page.getByLabel('Foodtruck'), 'Taco Truck');
    await kies(page, page.getByLabel('Plek'), 'Dam (Amsterdam)');
    await typTijd(page, page.getByLabel('Begintijd'), '15:00');
    await typTijd(page, page.getByLabel('Eindtijd'), '17:00');
    await klik(page, page.getByRole('button', { name: 'Stop toevoegen' }));
    await expect(page.getByText('Stop is toegevoegd.')).toBeVisible();
    await expect(page.locator('table.tabel tr', { hasText: 'Taco Truck' })).toContainText('15:00 - 17:00');
    await bekijk(page, page.locator('table.tabel tr', { hasText: 'Taco Truck' }), 2800);
});

test('3. Planner keurt routes goed: verlopen vergunning blokkeert, daarna zichtbaar voor bezoekers (FE-11, FE-12, FE-13)', async ({ page }) => {
    await inloggen(page, 'planner@example.com', 'demo-password');
    await klik(page, page.locator('.menu').getByRole('link', { name: 'Routes' }));

    // Unhappy flow: de route van morgen gebruikt NDSM, met een verlopen vergunning
    await klik(page, page.locator('tr', { hasText: datumTekst(1) }).getByRole('link', { name: 'Openen' }));
    await expect(page.getByText('de vergunning van NDSM is verlopen')).toBeVisible();
    await bekijk(page, page.getByText('de vergunning van NDSM is verlopen'), 2000);
    await klik(page, page.getByRole('button', { name: 'Route goedkeuren' }));
    await expect(page.getByText('De route is NIET goedgekeurd')).toBeVisible();
    await expect(page.getByText('Concept', { exact: true })).toBeVisible();
    await bekijk(page, page.getByText('De route is NIET goedgekeurd'), 2800);

    // Happy flow: de conceptroute van vandaag goedkeuren
    await klik(page, page.getByRole('link', { name: 'Alle routes' }));
    await klik(page, page.locator('tr', { hasText: datumTekst(0) }).filter({ hasText: 'Concept' }).getByRole('link', { name: 'Openen' }));
    await klik(page, page.getByRole('button', { name: 'Route goedkeuren' }));
    await expect(page.getByText('De route is goedgekeurd. Bezoekers kunnen hem nu zien.')).toBeVisible();
    await pauze(page, 2000);

    // Nu ziet een bezoeker Green Bites wel
    await klik(page, page.locator('.menu').getByRole('link', { name: 'Zoeken' }));
    await kies(page, page.getByLabel('Soort eten'), 'Vegetarisch');
    await klik(page, page.getByRole('button', { name: 'Zoeken' }));
    await expect(page.getByRole('heading', { name: 'Green Bites' })).toBeVisible();
    await bekijk(page, page.getByRole('heading', { name: 'Green Bites' }), 2800);
});

test('4. Planner beheert het menu: uitverkocht, wijzigen en foute prijs (FE-04, FE-14, FE-16)', async ({ page }) => {
    await inloggen(page, 'planner@example.com', 'demo-password');
    await klik(page, page.locator('.menu').getByRole('link', { name: 'Menu' }));
    await expect(page.getByRole('heading', { name: /Menu op/ })).toBeVisible();

    // Happy flow: Friet op uitverkocht zetten
    const friet = page.locator('tr', { hasText: 'Friet' });
    await klik(page, friet.getByRole('button', { name: 'Zet op uitverkocht' }));
    await expect(page.getByText('De status van het gerecht is aangepast.')).toBeVisible();
    await expect(page.locator('tr', { hasText: 'Friet' })).toContainText('Uitverkocht');
    await bekijk(page, page.locator('tr', { hasText: 'Friet' }), 2000);

    // Happy flow: prijs van een gerecht wijzigen
    await klik(page, page.locator('tr', { hasText: 'Classic burger' }).getByRole('link', { name: 'Wijzigen' }));
    await expect(page.getByRole('heading', { name: 'Gerecht wijzigen' })).toBeVisible();
    await typ(page, page.getByLabel('Prijs in euro'), '10,00');
    await klik(page, page.getByRole('button', { name: 'Opslaan' }));
    await expect(page.getByText('Classic burger is gewijzigd.')).toBeVisible();
    await expect(page.locator('tr', { hasText: 'Classic burger' })).toContainText('10,00');
    await bekijk(page, page.locator('tr', { hasText: 'Classic burger' }), 2000);

    // Unhappy flow: foute prijs
    await typ(page, page.getByLabel('Naam van het gerecht'), 'Milkshake');
    await typ(page, page.getByLabel('Prijs in euro'), 'gratis');
    await klik(page, page.getByRole('button', { name: 'Toevoegen' }));
    await expect(page.getByText('Vul een geldige prijs in, bijvoorbeeld 8,50.')).toBeVisible();
    await bekijk(page, page.getByText('Vul een geldige prijs in, bijvoorbeeld 8,50.'), 2500);

    // Verbeteren: goede prijs, het gerecht wordt toegevoegd
    await typ(page, page.getByLabel('Prijs in euro'), '4,50');
    await klik(page, page.getByRole('button', { name: 'Toevoegen' }));
    await expect(page.getByText('Milkshake is toegevoegd aan het menu.')).toBeVisible();
    await expect(page.locator('tr', { hasText: 'Milkshake' })).toContainText('4,50');
    await bekijk(page, page.locator('tr', { hasText: 'Milkshake' }), 2800);
});

test('5. Planner beheert foodtrucks en plekken met vergunning en openingstijden (FE-05, FE-06, FE-07, FE-09, FE-11)', async ({ page }) => {
    await inloggen(page, 'planner@example.com', 'demo-password');

    // FE-05: foodtruck toevoegen, met eerst een fout (leeg veld)
    await klik(page, page.locator('.menu').getByRole('link', { name: 'Foodtrucks' }));
    await typ(page, page.getByLabel('Naam'), 'Wafel Wagen');
    await klik(page, page.getByRole('button', { name: 'Opslaan' }));
    await expect(page.getByText('Dit veld is verplicht.')).toBeVisible();   // soort eten leeg
    await bekijk(page, page.getByText('Dit veld is verplicht.'), 1500);
    await typ(page, page.getByLabel('Soort eten'), 'Zoet');
    await klik(page, page.getByRole('button', { name: 'Opslaan' }));
    await expect(page.getByText('Foodtruck is toegevoegd.')).toBeVisible();
    await expect(page.locator('tr', { hasText: 'Wafel Wagen' })).toContainText('Zoet');
    await bekijk(page, page.locator('tr', { hasText: 'Wafel Wagen' }), 1500);

    // FE-05: wijzigen en verwijderen
    await klik(page, page.locator('tr', { hasText: 'Wafel Wagen' }).getByRole('link', { name: 'Wijzigen' }));
    await typ(page, page.getByLabel('Naam'), 'Wafel Wagen XL');
    await klik(page, page.getByRole('button', { name: 'Opslaan' }));
    await expect(page.getByText('Foodtruck is gewijzigd.')).toBeVisible();
    page.once('dialog', d => d.accept());   // "Weet je het zeker?" → OK
    await klik(page, page.locator('tr', { hasText: 'Wafel Wagen XL' }).getByRole('button', { name: 'Verwijderen' }));
    await expect(page.getByText('Foodtruck is verwijderd.')).toBeVisible();
    await expect(page.locator('tr', { hasText: 'Wafel Wagen XL' })).toHaveCount(0);

    // Unhappy flow: een truck die nog in een route staat, kan niet worden verwijderd
    page.once('dialog', d => d.accept());
    await klik(page, page.locator('tr', { hasText: 'Burger Beast' }).getByRole('button', { name: 'Verwijderen' }));
    await expect(page.getByText('Deze foodtruck staat nog in een route en kan niet worden verwijderd.')).toBeVisible();
    await bekijk(page, page.getByText('Deze foodtruck staat nog in een route'), 1800);

    // FE-06 + FE-09: plek met vergunning toevoegen, eerst met een ongeldige datum
    await klik(page, page.locator('.menu').getByRole('link', { name: 'Plekken' }));
    await expect(page.locator('tr', { hasText: 'NDSM' })).toContainText('Verlopen');   // FE-11
    await bekijk(page, page.locator('tr', { hasText: 'NDSM' }), 1500);
    await typ(page, page.getByLabel('Naam van de plek'), 'Vondelpark');
    await typ(page, page.getByLabel('Adres'), 'Vondelpark 1');
    await typ(page, page.getByLabel('Plaats', { exact: true }), 'Amsterdam');
    await typ(page, page.getByLabel('Vergunningnummer'), 'VG-010');
    await klik(page, page.getByRole('button', { name: 'Opslaan' }));
    await expect(page.getByText('Dit veld is verplicht.')).toBeVisible();   // einddatum leeg
    await bekijk(page, page.getByText('Dit veld is verplicht.'), 1500);
    await page.getByLabel('Vergunning geldig tot en met').fill('2027-06-30');
    await klik(page, page.getByRole('button', { name: 'Opslaan' }));
    await expect(page.getByText('Plek is toegevoegd. Vul nu de openingstijden in.')).toBeVisible();

    // FE-07: openingstijd toevoegen, eerst met sluitingstijd vóór openingstijd
    await kies(page, page.getByLabel('Dag'), 'maandag');
    await typTijd(page, page.getByLabel('Open vanaf'), '18:00');
    await typTijd(page, page.getByLabel('Dicht om'), '10:00');
    await klik(page, page.getByRole('button', { name: 'Openingstijd opslaan' }));
    await expect(page.getByText('De sluitingstijd moet na de openingstijd liggen.')).toBeVisible();
    await bekijk(page, page.getByText('De sluitingstijd moet na de openingstijd liggen.'), 1800);
    await kies(page, page.getByLabel('Dag'), 'maandag');
    await typTijd(page, page.getByLabel('Open vanaf'), '10:00');
    await typTijd(page, page.getByLabel('Dicht om'), '18:00');
    await klik(page, page.getByRole('button', { name: 'Openingstijd opslaan' }));
    await expect(page.getByText('Openingstijd voor maandag is opgeslagen.')).toBeVisible();
    await expect(page.locator('tr', { hasText: 'maandag' })).toContainText('18:00');
    await bekijk(page, page.locator('tr', { hasText: 'maandag' }), 2500);
});

test('6. Rechten: bezoeker, fout wachtwoord, planner en beheerder (FE-15, FE-16)', async ({ page }) => {
    // Unhappy flow: een bezoeker opent een beheerpagina
    await page.goto('beheer/routes.php');
    await expect(page.getByText('Log eerst in om deze pagina te bekijken.')).toBeVisible();
    await pauze(page, 2000);

    // Unhappy flow: fout wachtwoord
    await typ(page, page.getByLabel('E-mailadres'), 'planner@example.com');
    await typ(page, page.getByLabel('Wachtwoord'), 'foutwachtwoord');
    await klik(page, page.getByRole('button', { name: 'Inloggen' }));
    await expect(page.getByText('E-mailadres of wachtwoord is onjuist.')).toBeVisible();
    await pauze(page, 2000);

    // Planner: geen link naar Accounts, en de pagina zelf is geblokkeerd
    await typ(page, page.getByLabel('Wachtwoord'), 'demo-password');
    await klik(page, page.getByRole('button', { name: 'Inloggen' }));
    await expect(page.locator('.menu')).not.toContainText('Accounts');
    await pauze(page, 1200);
    await page.goto('beheer/accounts.php');
    await expect(page.getByText('Je hebt geen toegang tot deze pagina.')).toBeVisible();
    await pauze(page, 2200);

    // Beheerder: wel toegang tot Accounts
    await klik(page, page.locator('.menu').getByRole('link', { name: 'Uitloggen' }));
    await expect(page.getByText('Je bent uitgelogd.')).toBeVisible();
    await inloggen(page, 'beheerder@example.com', 'demo-password');
    await klik(page, page.locator('.menu').getByRole('link', { name: 'Accounts' }));
    await expect(page.getByRole('heading', { name: 'Alle accounts' })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'planner@example.com' })).toBeVisible();
    await bekijk(page, page.getByRole('cell', { name: 'planner@example.com' }), 2800);
});
