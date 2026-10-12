/**
 * Generates the notification images in assets/icons/alerts/: one Feather
 * icon per alert, in white on the brand gradient (same tile as the app icon).
 * Feather comes from the app's own copy (assets/js/vendor/feather.min.js).
 *
 * Usage (needs Google Chrome and puppeteer-core):
 *   npm i puppeteer-core && node tools/generate-alert-icons.js
 * Set CHROME_PATH if Chrome is not in the default macOS location.
 */
const fs = require('fs');
const path = require('path');
const puppeteer = require('puppeteer-core');

const root = path.resolve(__dirname, '..');
const out = path.join(root, 'assets/icons/alerts');
const SIZE = 192;
const BRAND = ['#a48cff', '#4c3be0'];
const DANGER = ['#ff8a80', '#d93025'];

// file => [Feather icon, gradient]; names match the alert keys in includes/push.php
const ICONS = {
  'processing-new': ['user-plus', BRAND],
  'sales-signing': ['edit-3', BRAND],
  'sales-signed': ['check-circle', BRAND],
  'compliance-checking': ['search', BRAND],
  'compliance-completed': ['shield', BRAND],
  'compliance-cancelled': ['x-circle', DANGER],
  'test': ['bell', BRAND],
};

(async () => {
  const browser = await puppeteer.launch({
    executablePath: process.env.CHROME_PATH || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    headless: 'new',
  });
  const page = await browser.newPage();
  await page.setViewport({ width: SIZE, height: SIZE });
  await page.setContent('<html><body style="margin:0;background:transparent"><div id="tile"></div></body></html>');
  await page.addScriptTag({ path: path.join(root, 'assets/js/vendor/feather.min.js') });
  fs.mkdirSync(out, { recursive: true });

  for (const [file, [icon, [from, to]]] of Object.entries(ICONS)) {
    await page.evaluate((icon, from, to, size) => {
      const tile = document.getElementById('tile');
      tile.style.cssText = `width:${size}px;height:${size}px;display:grid;place-items:center;`
        + `border-radius:${size * 0.225}px;background:linear-gradient(135deg, ${from}, ${to});`;
      tile.innerHTML = window.feather.icons[icon].toSvg({ width: size * 0.5, height: size * 0.5, color: '#fff', 'stroke-width': 2 });
    }, icon, from, to, SIZE);
    const tile = await page.$('#tile');
    await tile.screenshot({ path: path.join(out, file + '.png'), omitBackground: true });
    console.log('ok ', file + '.png', `(${icon})`);
  }
  await browser.close();
})();
