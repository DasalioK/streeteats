// Instellingen voor de end-to-end-tests (KT1-W4).
// Uitleg: Playwright opent Chrome, doet wat een gebruiker doet en neemt alles op als video.
const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
    testDir: './tests/e2e',
    workers: 1,               // tests na elkaar, want ze gebruiken dezelfde database
    timeout: 180000,          // ruim, omdat er rustig getypt wordt voor de video
    reporter: [['list'], ['html', { outputFolder: 'tests/e2e-rapport', open: 'never' }]],
    outputDir: 'tests/e2e-resultaten',
    use: {
        baseURL: 'http://localhost/streeteats/',
        channel: 'chrome',    // de Google Chrome die al op de laptop staat
        viewport: { width: 1280, height: 720 },
        video: { mode: 'on', size: { width: 1280, height: 720 } },
        locale: 'nl-NL',
    },
});
