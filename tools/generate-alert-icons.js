/**
 * Generates the notification images in assets/icons/alerts/: one Feather
 * icon per alert, in white on a small tile colored by the alert's status
 * (success, info, warning, error), centered on a transparent canvas.
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
// The tile only fills part of the canvas so it reads smaller in the notification
const TILE = Math.round(SIZE * 0.62);
const BRAND = '#6d5cf0';
const INFO = '#2f7de1';
const WARNING = '#e0901a';
const SUCCESS = '#22a35a';
const DANGER = '#d93a30';

// file => [Feather icon, color]; names match the alert keys in includes/push.php
const ICONS = {
  'processing-new': ['user-plus', INFO],
  'sales-signing': ['edit-3', WARNING],
  'sales-signed': ['check-circle', SUCCESS],
  'compliance-checking': ['search', INFO],
  'compliance-completed': ['shield', SUCCESS],
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
  await page.setContent('<html><body style="margin:0;background:transparent">'
    + `<div id="canvas" style="width:${SIZE}px;height:${SIZE}px;display:grid;place-items:center"><div id="tile"></div></div>`
    + '</body></html>');
  await page.addScriptTag({ path: path.join(root, 'assets/js/vendor/feather.min.js') });
  fs.mkdirSync(out, { recursive: true });

  for (const [file, [icon, color]] of Object.entries(ICONS)) {
    await page.evaluate((icon, color, size) => {
      const tile = document.getElementById('tile');
      tile.style.cssText = `width:${size}px;height:${size}px;display:grid;place-items:center;`
        + `border-radius:${size * 0.28}px;background:${color};`;
      tile.innerHTML = window.feather.icons[icon].toSvg({ width: size * 0.5, height: size * 0.5, color: '#fff', 'stroke-width': 2.25 });
    }, icon, color, TILE);
    const canvas = await page.$('#canvas');
    await canvas.screenshot({ path: path.join(out, file + '.png'), omitBackground: true });
    console.log('ok ', file + '.png', `(${icon})`);
  }
  await browser.close();
})();
