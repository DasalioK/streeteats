// Hulpfuncties voor de end-to-end-tests.
// Ze laten de browser bewegen, scrollen en typen zoals een mens, zodat de video natuurlijk oogt.
// Let op: dit is alleen voor het beeld. De controles (expect) in de tests werken hetzelfde als zonder deze hulp.
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

// Zet de testdata terug, zodat elke test met dezelfde gegevens begint.
function resetDatabase() {
    const sql = fs.readFileSync(path.join(__dirname, '../../database/streeteats.sql'));
    execSync('"C:\\xamp\\mysql\\bin\\mysql.exe" -u root --default-character-set=utf8mb4', { input: sql });
}

// Extra testgegevens klaarzetten voor één test (bijv. 13 stops om het bladeren te testen)
function voerSqlUit(sql) {
    execSync('"C:\\xamp\\mysql\\bin\\mysql.exe" -u root --default-character-set=utf8mb4 streeteats', { input: sql });
}

const willekeurig = (min, max) => min + Math.random() * (max - min);

// ---------------------------------------------------------------------------------------------
// ZICHTBARE CURSOR
// Een video-opname laat de echte muis niet zien. Daarom tonen we de ECHTE Windows-cursors
// (uit C:\Windows\Cursors, omgezet naar plaatjes in cursors.json): pijl, handje en tekstcursor.
// ---------------------------------------------------------------------------------------------
const cursors = JSON.parse(fs.readFileSync(path.join(__dirname, 'cursors.json'), 'utf8'));
const cursorScript = `
(() => {
    const soorten = ${JSON.stringify(cursors)};
    const maak = () => {
        if (document.getElementById('test-cursor')) return;
        const c = document.createElement('img');
        c.id = 'test-cursor';
        c.style.cssText = 'position:fixed;z-index:2147483647;pointer-events:none;width:32px;height:32px;';
        document.body.appendChild(c);
        let soort = '';
        const zet = (x, y) => {
            const el = document.elementFromPoint(x, y);
            let nieuw = 'pijl';
            if (el) {
                const stijl = getComputedStyle(el).cursor;
                if (el.closest('a, button, select, .test-optie') || stijl === 'pointer') nieuw = 'hand';
                else if (el.matches('input:not([type=submit]):not([type=checkbox]), textarea') || stijl === 'text') nieuw = 'tekst';
            }
            if (nieuw !== soort) { c.src = soorten[nieuw].png; soort = nieuw; }
            c.style.left = (x - soorten[nieuw].x) + 'px';
            c.style.top = (y - soorten[nieuw].y) + 'px';
            sessionStorage.setItem('cursor', JSON.stringify({ x, y }));
        };
        const pos = JSON.parse(sessionStorage.getItem('cursor') || '{"x":640,"y":400}');
        zet(pos.x, pos.y);
        document.addEventListener('mousemove', e => zet(e.clientX, e.clientY), true);
    };
    if (document.body) maak(); else document.addEventListener('DOMContentLoaded', maak);
})();`;

// Waar de muis nu is (wordt na elke beweging bijgewerkt)
let muis = { x: 640, y: 400 };

async function start(page) {
    muis = { x: 640, y: 400 };
    await page.addInitScript(cursorScript);
    // Geen "springend" scrollen door de browser zelf; wij scrollen rustig met het muiswiel
    await page.addInitScript(() => document.addEventListener('DOMContentLoaded', () => { document.documentElement.style.scrollBehavior = 'auto'; }));
}

const pauze = (page, ms = 700) => page.waitForTimeout(ms);

// ---------------------------------------------------------------------------------------------
// MENSELIJKE MUISBEWEGING
// Een mens beweegt niet in een rechte lijn met vaste snelheid. Daarom:
// - een licht gebogen lijn (bezier-curve) met een willekeurige bocht,
// - eerst versnellen, dan afremmen (ease-in-out),
// - bij lange bewegingen een klein stukje doorschieten en corrigeren.
// ---------------------------------------------------------------------------------------------
async function muisNaar(page, x, y) {
    const van = { ...muis };
    const afstand = Math.hypot(x - van.x, y - van.y);
    if (afstand < 2) return;

    const doorschieten = afstand > 250 && Math.random() < 0.6;
    const doel = doorschieten
        ? { x: x + (x - van.x) / afstand * willekeurig(6, 14), y: y + (y - van.y) / afstand * willekeurig(4, 10) }
        : { x, y };

    // Twee hulppunten zijwaarts van de rechte lijn zorgen voor een natuurlijke bocht
    const loodrecht = { x: -(doel.y - van.y) / afstand, y: (doel.x - van.x) / afstand };
    const bocht = willekeurig(-0.18, 0.18) * afstand;
    const p1 = { x: van.x + (doel.x - van.x) * 0.3 + loodrecht.x * bocht, y: van.y + (doel.y - van.y) * 0.3 + loodrecht.y * bocht };
    const p2 = { x: van.x + (doel.x - van.x) * 0.75 + loodrecht.x * bocht * 0.5, y: van.y + (doel.y - van.y) * 0.75 + loodrecht.y * bocht * 0.5 };

    const stappen = Math.round(Math.min(70, Math.max(18, afstand / 9)));
    const duur = Math.min(1100, Math.max(260, afstand * 1.35)) * willekeurig(0.85, 1.15);
    for (let i = 1; i <= stappen; i++) {
        let t = i / stappen;
        t = t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;   // versnellen en afremmen
        const px = (1 - t) ** 3 * van.x + 3 * (1 - t) ** 2 * t * p1.x + 3 * (1 - t) * t * t * p2.x + t ** 3 * doel.x;
        const py = (1 - t) ** 3 * van.y + 3 * (1 - t) ** 2 * t * p1.y + 3 * (1 - t) * t * t * p2.y + t ** 3 * doel.y;
        await page.mouse.move(px, py);
        await page.waitForTimeout(duur / stappen);
    }
    muis = { ...doel };

    if (doorschieten) {
        await page.waitForTimeout(willekeurig(60, 140));
        const terug = 8;
        for (let i = 1; i <= terug; i++) {
            await page.mouse.move(doel.x + (x - doel.x) * i / terug, doel.y + (y - doel.y) * i / terug);
            await page.waitForTimeout(18);
        }
        muis = { x, y };
    }
}

// Rustig scrollen met het muiswiel tot het element goed in beeld is
async function scrollNaar(page, element) {
    const hoogte = page.viewportSize().height;
    for (let poging = 0; poging < 40; poging++) {
        const box = await element.boundingBox();
        if (!box) return;
        const boven = 90, onder = hoogte - 110;
        if (box.y >= boven && box.y + Math.min(box.height, 200) <= onder) return;
        const richting = box.y < boven ? -1 : 1;
        const nodig = richting < 0 ? boven - box.y : box.y + Math.min(box.height, 200) - onder;
        const tik = Math.min(nodig, willekeurig(90, 130));
        await page.mouse.wheel(0, richting * tik);
        await page.waitForTimeout(willekeurig(45, 80));
    }
}

// Beweeg de muis naar een willekeurig punt in het element (niet precies het midden, zoals een mens)
async function beweegNaar(page, element) {
    await scrollNaar(page, element);
    const box = await element.boundingBox();
    const x = box.x + box.width * willekeurig(0.35, 0.65);
    const y = box.y + box.height * willekeurig(0.38, 0.62);
    await muisNaar(page, x, y);
    await pauze(page, willekeurig(120, 260));
}

async function klik(page, element) {
    await beweegNaar(page, element);
    await page.mouse.down();
    await page.waitForTimeout(willekeurig(60, 110));
    await page.mouse.up();
    await page.waitForLoadState();
    await pauze(page, willekeurig(450, 700));
}

// Letters die op een toetsenbord naast elkaar liggen (voor een geloofwaardige tikfout)
const buren = {
    a: 'sq', b: 'vn', c: 'xv', d: 'sf', e: 'wr', f: 'dg', g: 'fh', h: 'gj', i: 'uo', j: 'hk', k: 'jl', l: 'k',
    m: 'n', n: 'bm', o: 'ip', p: 'o', q: 'w', r: 'et', s: 'ad', t: 'ry', u: 'yi', v: 'cb', w: 'qe', x: 'zc', y: 'tu', z: 'x',
};

// Typen in een rustig tempo, zodat je in de video kunt meelezen.
// Tikfouten staan standaard UIT: een automatische test moet elke keer precies hetzelfde doen (deterministisch).
async function typLetters(page, tekst, tikfouten = false) {
    let fouten = 0;
    for (let i = 0; i < tekst.length; i++) {
        const letter = tekst[i];
        const klein = letter.toLowerCase();
        const magFout = tikfouten && fouten < 2 && i > 0 && i < tekst.length - 1 && buren[klein];
        if (magFout && Math.random() < 0.09) {
            const verkeerd = buren[klein][Math.floor(Math.random() * buren[klein].length)];
            await page.keyboard.type(letter === klein ? verkeerd : verkeerd.toUpperCase());
            await page.waitForTimeout(willekeurig(250, 450));   // even "zien" dat het fout is
            await page.keyboard.press('Backspace');
            await page.waitForTimeout(willekeurig(180, 320));
            fouten++;
        }
        await page.keyboard.type(letter);
        await page.waitForTimeout(letter === ' ' ? willekeurig(220, 380) : willekeurig(120, 260));
    }
}

// Klik in een veld, maak het leeg en typ de nieuwe tekst
async function typ(page, element, tekst) {
    await klik(page, element);
    await page.keyboard.press('Control+A');
    await page.waitForTimeout(willekeurig(80, 150));
    if (tekst === '') {
        await page.keyboard.press('Backspace');
    } else {
        await typLetters(page, tekst);
    }
    await pauze(page, willekeurig(250, 450));
}

// Tijdveld (uu:mm): klik op het uur-deel en typ alleen de cijfers, bijv. "1300"
async function typTijd(page, element, tijd) {
    await scrollNaar(page, element);
    const box = await element.boundingBox();
    await muisNaar(page, box.x + willekeurig(14, 24), box.y + box.height * willekeurig(0.4, 0.6));
    await pauze(page, willekeurig(120, 220));
    await page.mouse.down(); await page.waitForTimeout(80); await page.mouse.up();
    await page.waitForTimeout(willekeurig(150, 250));
    await typLetters(page, tijd.replace(':', ''), false);   // geen tikfouten in een tijdveld
    await pauze(page, willekeurig(250, 400));
}

// ---------------------------------------------------------------------------------------------
// UITKLAPMENU
// Het echte Windows-uitklapmenu komt niet in een video-opname. Daarom tekenen we een lijst die
// er hetzelfde uitziet, onder het keuzeveld. De muis beweegt naar de optie (die oplicht) en klikt.
// Daarna wordt de echte keuze in het veld gezet, precies zoals bij een gewone klik.
// ---------------------------------------------------------------------------------------------
// Rustig verder scrollen tot er onder het veld genoeg ruimte is voor de hele lijst
async function ruimteVoorLijst(page, element, lijstHoogte) {
    const hoogte = page.viewportSize().height;
    for (let poging = 0; poging < 30; poging++) {
        const box = await element.boundingBox();
        const tekort = box.y + box.height + lijstHoogte + 20 - hoogte;
        if (tekort <= 0 || box.y < 110) return;   // past, of het veld staat al bovenaan
        await page.mouse.wheel(0, Math.min(tekort, willekeurig(90, 130)));
        await page.waitForTimeout(willekeurig(45, 80));
    }
}

async function kies(page, element, tekst) {
    await scrollNaar(page, element);
    const lijstHoogte = await element.evaluate(s => s.options.length * 24 + 8);
    await ruimteVoorLijst(page, element, lijstHoogte);

    // Het echte (onzichtbare) menu niet openen, anders vangt dat de klik op
    await element.evaluate(s => s.addEventListener('mousedown', e => e.preventDefault(), { once: true }));
    await klik(page, element);
    await element.evaluate(select => {
        const box = select.getBoundingClientRect();
        const lijst = document.createElement('div');
        lijst.id = 'test-dropdown';
        lijst.style.cssText = `position:fixed;z-index:2147483646;left:${box.left}px;top:${box.bottom}px;width:${box.width}px;` +
            'background:#fff;border:1px solid #767676;box-shadow:0 4px 10px rgba(0,0,0,.25);font:16px Arial,sans-serif;padding:2px 0;';
        const stijl = document.createElement('style');
        stijl.textContent = '#test-dropdown .test-optie{padding:3px 8px;white-space:nowrap}#test-dropdown .test-optie:hover{background:#1e90ff;color:#fff}#test-dropdown .test-optie.gekozen{background:#cfe3ff}';
        lijst.appendChild(stijl);
        [...select.options].forEach(o => {
            const r = document.createElement('div');
            r.className = 'test-optie' + (o.selected ? ' gekozen' : '');
            r.textContent = o.text;
            lijst.appendChild(r);
        });
        document.body.appendChild(lijst);
        // Past de lijst niet onder het veld? Dan klapt hij naar boven open, net als in Windows
        if (box.bottom + lijst.offsetHeight > window.innerHeight - 4) {
            lijst.style.top = Math.max(4, box.top - lijst.offsetHeight) + 'px';
        }
    });
    await pauze(page, willekeurig(350, 600));

    const optie = page.locator('#test-dropdown .test-optie', { hasText: tekst }).filter({ hasText: new RegExp('^' + tekst.replace(/[()]/g, '\\$&') + '$') });
    const box = await optie.boundingBox();
    await muisNaar(page, box.x + box.width * willekeurig(0.2, 0.5), box.y + box.height * willekeurig(0.4, 0.6));
    await pauze(page, willekeurig(200, 350));
    await page.mouse.down(); await page.waitForTimeout(70); await page.mouse.up();

    await page.evaluate(() => document.getElementById('test-dropdown')?.remove());
    await element.selectOption({ label: tekst });
    await pauze(page, willekeurig(350, 550));
}

// Even rondkijken: de muis rustig naar een plek bewegen en wachten, zodat de kijker kan lezen
async function bekijk(page, element, ms = 2000) {
    await scrollNaar(page, element);
    const box = await element.boundingBox();
    await muisNaar(page, box.x + box.width * willekeurig(0.55, 0.85), box.y + box.height + willekeurig(25, 60));
    await pauze(page, ms);
}

async function inloggen(page, email, wachtwoord) {
    await page.goto('login.php');
    await pauze(page, 800);
    await typ(page, page.getByLabel('E-mailadres'), email);
    await typ(page, page.getByLabel('Wachtwoord'), wachtwoord);
    await klik(page, page.getByRole('button', { name: 'Inloggen' }));
}

// Screenshot als bewijs, bijv. bewijs(page, 'E2E-02-dubbele-stop-geweigerd') -> tests/e2e-bewijs/E2E-02-dubbele-stop-geweigerd.png
async function bewijs(page, naam) {
    await page.screenshot({ path: path.join(__dirname, '../e2e-bewijs', naam + '.png'), fullPage: true });
}

module.exports = { resetDatabase, voerSqlUit, start, pauze, muisNaar, scrollNaar, beweegNaar, klik, typ, typTijd, kies, bekijk, inloggen, bewijs };
