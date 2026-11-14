/**
 * QuickCart design asset build.
 *
 *   npm run build -- [--php=E:/quickcart-stack/php/php.exe]
 *
 * Outputs (all committed, so Magento installs never need Node):
 *  - src/app/code/QuickCart/DemoData/fixtures/catalog.json + media/{product,category}
 *  - theme: web/images/{logo,logo-light,favicon}.svg, web/images/hero/*.webp, media/preview.jpg
 *  - theme: web/js/vendor/motion.js (Motion, MIT) and web/fonts/* (Inter, Plus Jakarta Sans - OFL)
 */
import { Resvg } from '@resvg/resvg-js';
import { execFileSync } from 'node:child_process';
import { copyFileSync, existsSync, mkdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import * as opentypeModule from 'opentype.js';
import { BRANDS, CATEGORIES, CLOSINGS, CONNECTIVITY, PRODUCTS, RAM, STORAGE, WARRANTY } from './catalog.mjs';
import { renderDeviceSvg } from './lib/devices.mjs';
import { buildMerchandising } from './lib/merchandising.mjs';
import { BRAND, COLORS } from './lib/palette.mjs';

const here = dirname(fileURLToPath(import.meta.url));
const repo = resolve(here, '../..');
const fixtures = join(repo, 'src/app/code/QuickCart/DemoData/fixtures');
const theme = join(repo, 'src/app/design/frontend/QuickCart/default');
const cache = join(here, '.cache');
const phpArg = process.argv.find((a) => a.startsWith('--php='));
const php = phpArg ? phpArg.slice(6) : 'E:/quickcart-stack/php/php.exe';

const ensureDir = (dir) => mkdirSync(dir, { recursive: true });
const slug = (s) => s.toLowerCase().replace(/["']/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

rmSync(cache, { recursive: true, force: true });
ensureDir(cache);
const conversions = [];

function render(svg, width, name) {
    const png = new Resvg(svg, {
        fitTo: { mode: 'width', value: width },
        font: { loadSystemFonts: true, defaultFontFamily: 'Segoe UI' },
    }).render().asPng();
    const file = join(cache, `${name}.png`);
    writeFileSync(file, png);
    return file;
}

function queue(svg, width, dest, quality) {
    const src = render(svg, width, slug(dest));
    conversions.push({ src, dest, quality });
}

// ------------------------------------------------------------------ catalogue fixture + images
const productImage = (sku) => `product/${slug(sku)}.jpg`;
const fixtureProducts = PRODUCTS.map((p) => {
    const html = [
        `<p>${esc(p.short)}</p>`,
        '<h3>Highlights</h3>',
        `<ul>${p.highlights.map((h) => `<li>${esc(h)}</li>`).join('')}</ul>`,
        `<p>${esc(CLOSINGS[p.category])}</p>`,
    ].join('\n');

    const base = {
        sku: p.sku,
        name: p.name,
        type: p.type,
        url_key: slug(p.name),
        category: p.category,
        brand: p.brand,
        price: p.price,
        special_price: p.special ?? null,
        ram: p.ram ?? null,
        storage: p.storage ?? null,
        display: p.display ?? null,
        battery: p.battery ?? null,
        connectivity: p.connectivity ?? [],
        warranty: p.warranty,
        trending: Boolean(p.trending),
        is_new: Boolean(p.isNew),
        weight: p.weight,
        short_description: `<p>${esc(p.short)}</p>`,
        description: html,
        meta_description: p.short.slice(0, 160),
    };

    if (p.type === 'configurable') {
        base.children = p.colors.map((color) => {
            const sku = `${p.sku}-${color.toUpperCase()}`;
            queue(renderDeviceSvg(p.device, COLORS[color], 'product'), 1000, join(fixtures, 'media', productImage(sku)), 86);
            return { sku, name: `${p.name} - ${COLORS[color].label}`, color, image: productImage(sku) };
        });
        base.color = null;
        base.image = base.children[0].image;
    } else {
        queue(renderDeviceSvg(p.device, COLORS[p.color], 'product'), 1000, join(fixtures, 'media', productImage(p.sku)), 86);
        base.color = p.color;
        base.image = productImage(p.sku);
    }
    return base;
});

const merchandising = buildMerchandising(PRODUCTS, CATEGORIES);
for (const product of fixtureProducts) {
    const extra = merchandising.get(product.sku);
    product.reviews = extra.reviews;
    product.links = extra.links;
}

const CATEGORY_ART_COLOR = { smartphone: 'blue', laptop: 'silver', headphones: 'midnight', smartwatch: 'rose', camera: 'silver', controller: 'midnight', keyboard: 'silver', tag: 'gold' };
const fixtureCategories = CATEGORIES.map((c) => {
    const image = `category/${c.key}.webp`;
    queue(renderDeviceSvg(c.art, COLORS[CATEGORY_ART_COLOR[c.art]], 'transparent'), 640, join(fixtures, 'media', image), 88);
    return {
        key: c.key,
        name: c.name,
        url_key: c.key,
        description: `<p>${esc(c.description)}</p>`,
        image,
        children: c.children.map((child) => ({ key: child.key, name: child.name, url_key: child.key })),
    };
});

ensureDir(fixtures);
writeFileSync(join(fixtures, 'catalog.json'), JSON.stringify({
    brands: BRANDS,
    colors: Object.fromEntries(Object.entries(COLORS).map(([k, v]) => [k, { label: v.label, hex: v.hex }])),
    ram: RAM,
    storage: STORAGE,
    connectivity: CONNECTIVITY,
    warranty: WARRANTY,
    categories: fixtureCategories,
    products: fixtureProducts,
}, null, 2) + '\n');

// ------------------------------------------------------------------ hero art
const HERO = [['phone', 'smartphone', 'blue'], ['laptop', 'laptop', 'silver'], ['audio', 'headphones', 'white']];
for (const [name, device, color] of HERO) {
    queue(renderDeviceSvg(device, COLORS[color], 'transparent'), 900, join(theme, 'web/images/hero', `${name}.webp`), 88);
}

// ------------------------------------------------------------------ logo (wordmark converted to outlines)
const opentype = opentypeModule.parse ? opentypeModule : opentypeModule.default;
const fontFile = join(here, 'node_modules/@fontsource/plus-jakarta-sans/files/plus-jakarta-sans-latin-800-normal.woff');
const fontBuf = readFileSync(fontFile);
const font = opentype.parse(fontBuf.buffer.slice(fontBuf.byteOffset, fontBuf.byteOffset + fontBuf.byteLength));
const size = 38;
// Manual glyph layout (advance + kerning): opentype.js' shaper rejects this font's GSUB tables,
// and a wordmark needs no ligatures.
function layout(text, x, y) {
    const scale = size / font.unitsPerEm;
    let cursor = x;
    let previous = null;
    const parts = [];
    for (const char of text) {
        const glyph = font.charToGlyph(char);
        if (previous) cursor += font.getKerningValue(previous, glyph) * scale;
        parts.push(glyph.getPath(cursor, y, size).toPathData(2));
        cursor += glyph.advanceWidth * scale;
        previous = glyph;
    }
    return { d: parts.join(''), width: cursor - x };
}
const textX = 78;
const quick = layout('Quick', textX, 46);
const cart = layout('Cart', textX + quick.width, 46);
const quickW = quick.width;
const cartW = cart.width;
const quickPath = quick.d;
const cartPath = cart.d;
const logoWidth = Math.ceil(textX + quickW + cartW + 2);

const mark = `
    <defs><linearGradient id="qcMark" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="${BRAND.primaryLight}"/><stop offset="1" stop-color="${BRAND.primaryDark}"/></linearGradient></defs>
    <rect width="64" height="64" rx="18" fill="url(#qcMark)"/>
    <circle cx="29" cy="29" r="14" fill="none" stroke="#fff" stroke-width="7.5"/>
    <path d="M42 29 L31 45 H38.5 L34.5 57 L49 38.5 H41.5 L45.5 29 Z" fill="${BRAND.accent}" stroke="${BRAND.primaryDark}" stroke-width="3" stroke-linejoin="round"/>`;
const logo = (quickColor) => `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${logoWidth} 64" width="${logoWidth}" height="64" role="img" aria-label="QuickCart">${mark}<path d="${quickPath}" fill="${quickColor}"/><path d="${cartPath}" fill="${BRAND.primary}"/></svg>\n`;
ensureDir(join(theme, 'web/images'));
writeFileSync(join(theme, 'web/images/logo.svg'), logo(BRAND.secondary));
writeFileSync(join(theme, 'web/images/logo-light.svg'), logo('#FFFFFF'));
writeFileSync(join(theme, 'web/images/favicon.svg'), `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">${mark}</svg>\n`);

// ------------------------------------------------------------------ theme preview (admin > Content > Design > Themes)
const preview = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800" width="800" height="800">
    <defs><linearGradient id="pv" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#0B1120"/><stop offset="1" stop-color="#1E3A8A"/></linearGradient></defs>
    <rect width="800" height="800" fill="url(#pv)"/>
    <circle cx="640" cy="160" r="220" fill="${BRAND.primary}" fill-opacity=".35"/>
    <circle cx="140" cy="700" r="200" fill="${BRAND.accent}" fill-opacity=".18"/>
    <g transform="translate(${(800 - logoWidth * 2.4) / 2} 300) scale(2.4)">${mark}<path d="${quickPath}" fill="#fff"/><path d="${cartPath}" fill="#93C5FD"/></g>
    <text x="400" y="520" text-anchor="middle" font-family="Segoe UI" font-weight="600" font-size="34" fill="#CBD5E1">Shop Smarter. Shop Faster.</text>
</svg>`;
queue(preview, 800, join(theme, 'media/preview.jpg'), 88);

// ------------------------------------------------------------------ convert PNG -> JPG/WebP with PHP GD
const manifest = join(cache, 'manifest.json');
writeFileSync(manifest, JSON.stringify(conversions));
execFileSync(php, [join(here, 'lib/convert.php'), manifest], { stdio: 'inherit' });

// ------------------------------------------------------------------ vendor Motion + fonts into the theme
const vendorJs = join(theme, 'web/js/vendor');
ensureDir(vendorJs);
const motionPkg = JSON.parse(readFileSync(join(here, 'node_modules/motion/package.json'), 'utf8'));
writeFileSync(join(vendorJs, 'motion.js'), `/*! Motion v${motionPkg.version} | MIT License | https://motion.dev */\n` + readFileSync(join(here, 'node_modules/motion/dist/motion.js'), 'utf8'));
copyFileSync(join(here, 'node_modules/motion/LICENSE.md'), join(vendorJs, 'motion.LICENSE.md'));

const fonts = join(theme, 'web/fonts');
ensureDir(fonts);
copyFileSync(join(here, 'node_modules/@fontsource-variable/inter/files/inter-latin-wght-normal.woff2'), join(fonts, 'inter-var-latin.woff2'));
for (const weight of [700, 800]) {
    copyFileSync(join(here, `node_modules/@fontsource/plus-jakarta-sans/files/plus-jakarta-sans-latin-${weight}-normal.woff2`), join(fonts, `plus-jakarta-sans-${weight}-latin.woff2`));
}
copyFileSync(join(here, 'node_modules/@fontsource-variable/inter/LICENSE'), join(fonts, 'LICENSE-Inter.txt'));
copyFileSync(join(here, 'node_modules/@fontsource/plus-jakarta-sans/LICENSE'), join(fonts, 'LICENSE-PlusJakartaSans.txt'));

rmSync(cache, { recursive: true, force: true });
console.log(`Built ${fixtureProducts.length} products, ${fixtureCategories.length} categories, ${conversions.length} images.`);
if (!existsSync(join(fixtures, 'catalog.json'))) process.exit(1);
