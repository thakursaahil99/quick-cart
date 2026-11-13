// Dev helper: renders every device in a few colours to <out>/sheet-*.png (not part of the build).
import { Resvg } from '@resvg/resvg-js';
import { writeFileSync } from 'node:fs';
import { DEVICES, renderDeviceSvg } from './lib/devices.mjs';
import { COLORS } from './lib/palette.mjs';
const out = process.argv[2];
const colors = ['midnight', 'silver', 'blue', 'rose'];
let i = 0;
for (const d of Object.keys(DEVICES)) {
  const c = COLORS[colors[i++ % colors.length]];
  const png = new Resvg(renderDeviceSvg(d, c, 'product'), { fitTo: { mode: 'width', value: 300 }, font: { loadSystemFonts: true, defaultFontFamily: 'Segoe UI' } }).render().asPng();
  writeFileSync(`${out}/${String(i).padStart(2, '0')}-${d}.png`, png);
}
console.log('rendered', i);
