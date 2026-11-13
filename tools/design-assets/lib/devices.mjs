/**
 * Parametric SVG illustrations of the QuickCart demo catalogue devices.
 * Every drawer receives a colour variant from palette.mjs and returns SVG markup
 * for a 1000x1000 canvas; shared gradients/filters are registered through `ctx.def()`.
 */
import { BRAND } from './palette.mjs';

const stops = (list) =>
    list.map(([offset, color, opacity = 1]) => `<stop offset="${offset}" stop-color="${color}" stop-opacity="${opacity}"/>`).join('');

export function createContext(c) {
    const defs = [];
    let uid = 0;
    const ctx = {
        def: (markup) => defs.push(markup),
        id: (prefix) => `${prefix}${++uid}`,
        defs: () => defs.join(''),
    };

    ctx.def(`<linearGradient id="body" x1="0" y1="0" x2="1" y2="1">${stops([[0, c.a], [1, c.b]])}</linearGradient>`);
    ctx.def(`<linearGradient id="bodyR" x1="1" y1="0" x2="0" y2="1">${stops([[0, c.a], [1, c.b]])}</linearGradient>`);
    ctx.def(`<linearGradient id="edge" x1="0" y1="0" x2="1" y2="0">${stops([[0, c.b], [0.18, c.edge], [0.5, c.a], [0.82, c.edge], [1, c.b]])}</linearGradient>`);
    ctx.def(`<linearGradient id="cyl" x1="0" y1="0" x2="1" y2="0">${stops([[0, c.b], [0.3, c.a], [0.48, c.edge], [0.7, c.a], [1, c.b]])}</linearGradient>`);
    ctx.def(`<linearGradient id="baseG" x1="0" y1="0" x2="0" y2="1">${stops([[0, c.edge], [0.5, c.a], [1, c.b]])}</linearGradient>`);
    ctx.def(`<linearGradient id="dark" x1="0" y1="0" x2="1" y2="1">${stops([[0, '#374151'], [1, '#030712']])}</linearGradient>`);
    ctx.def(`<linearGradient id="wall" x1="0" y1="0" x2="1" y2="1">${stops([[0, '#0B1535'], [0.55, '#1E1B4B'], [1, '#111827']])}</linearGradient>`);
    ctx.def(`<linearGradient id="shine" x1="0" y1="0" x2="0" y2="1">${stops([[0, '#FFFFFF', 0.55], [0.45, '#FFFFFF', 0.08], [1, '#FFFFFF', 0]])}</linearGradient>`);
    ctx.def(`<radialGradient id="glassR" cx="0.38" cy="0.35" r="0.7">${stops([[0, '#7DD3FC'], [0.22, '#2563EB'], [0.55, '#1E1B4B'], [1, '#020617']])}</radialGradient>`);
    ctx.def(`<radialGradient id="prop" cx="0.5" cy="0.5" r="0.5">${stops([[0, '#94A3B8', 0.05], [0.7, '#94A3B8', 0.28], [1, '#94A3B8', 0.05]])}</radialGradient>`);
    ctx.def(`<linearGradient id="tagG" x1="0" y1="0" x2="1" y2="1">${stops([[0, '#FCD34D'], [0.5, BRAND.accent], [1, '#EA580C']])}</linearGradient>`);
    ctx.def('<filter id="blurS" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="16"/></filter>');
    ctx.def('<filter id="blurL" x="-80%" y="-80%" width="260%" height="260%"><feGaussianBlur stdDeviation="38"/></filter>');
    ctx.def('<filter id="blurXL" x="-80%" y="-80%" width="260%" height="260%"><feGaussianBlur stdDeviation="70"/></filter>');
    ctx.def('<pattern id="mesh" width="14" height="14" patternUnits="userSpaceOnUse"><circle cx="7" cy="7" r="2.8" fill="#000" fill-opacity=".24"/></pattern>');
    ctx.def('<pattern id="grain" width="10" height="10" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="1.4" fill="#fff" fill-opacity=".06"/><circle cx="7" cy="6" r="1.2" fill="#000" fill-opacity=".18"/></pattern>');
    return ctx;
}

const shadow = (cx, cy, rx, ry, opacity = 0.24) =>
    `<ellipse cx="${cx}" cy="${cy}" rx="${rx}" ry="${ry}" fill="#0F172A" fill-opacity="${opacity}" filter="url(#blurS)"/>`;

/** Wallpaper + optional UI layer clipped to a screen rectangle. */
function screen(ctx, x, y, w, h, rx, ui = 'none') {
    const clip = ctx.id('clip');
    ctx.def(`<clipPath id="${clip}"><rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${rx}"/></clipPath>`);
    const m = Math.max(w, h);
    const layers = {
        none: '',
        phone: `
            <text x="${x + w / 2}" y="${y + h * 0.27}" text-anchor="middle" font-family="Segoe UI" font-weight="300" font-size="${w * 0.33}" fill="#fff">9:41</text>
            <text x="${x + w / 2}" y="${y + h * 0.33}" text-anchor="middle" font-family="Segoe UI" font-weight="600" font-size="${w * 0.06}" fill="#fff" fill-opacity=".8">Friday, 13 November</text>
            ${[0, 1, 2, 3].map((i) => `<rect x="${x + w * 0.1 + i * w * 0.21}" y="${y + h * 0.86}" width="${w * 0.17}" height="${w * 0.17}" rx="${w * 0.05}" fill="#fff" fill-opacity=".2"/>`).join('')}
            <rect x="${x + w * 0.05}" y="${y + h * 0.83}" width="${w * 0.9}" height="${w * 0.25}" rx="${w * 0.08}" fill="#fff" fill-opacity=".08"/>`,
        tablet: `
            ${[0, 1, 2].map((r) => [0, 1].map((cIdx) => `<rect x="${x + w * 0.07 + cIdx * w * 0.45}" y="${y + h * 0.12 + r * h * 0.27}" width="${w * 0.41}" height="${h * 0.23}" rx="${w * 0.04}" fill="#fff" fill-opacity="${0.1 + ((r + cIdx) % 2) * 0.06}"/>`).join('')).join('')}
            <rect x="${x + w * 0.11}" y="${y + h * 0.15}" width="${w * 0.2}" height="${h * 0.02}" rx="${h * 0.01}" fill="#fff" fill-opacity=".7"/>
            <path d="M${x + w * 0.52} ${y + h * 0.31} L${x + w * 0.62} ${y + h * 0.24} L${x + w * 0.7} ${y + h * 0.28} L${x + w * 0.84} ${y + h * 0.18}" stroke="${BRAND.accent}" stroke-width="${w * 0.012}" fill="none" stroke-linecap="round"/>`,
        laptop: `
            <rect x="${x}" y="${y}" width="${w}" height="${h * 0.06}" fill="#fff" fill-opacity=".1"/>
            <rect x="${x + w * 0.08}" y="${y + h * 0.16}" width="${w * 0.52}" height="${h * 0.66}" rx="${w * 0.015}" fill="#fff" fill-opacity=".12"/>
            <rect x="${x + w * 0.08}" y="${y + h * 0.16}" width="${w * 0.52}" height="${h * 0.07}" rx="${w * 0.015}" fill="#fff" fill-opacity=".12"/>
            ${[0, 1, 2, 3].map((i) => `<rect x="${x + w * 0.11}" y="${y + h * (0.3 + i * 0.11)}" width="${w * (0.44 - i * 0.07)}" height="${h * 0.04}" rx="${h * 0.02}" fill="#fff" fill-opacity=".28"/>`).join('')}
            <rect x="${x + w * 0.65}" y="${y + h * 0.16}" width="${w * 0.27}" height="${h * 0.3}" rx="${w * 0.015}" fill="${BRAND.primaryLight}" fill-opacity=".55"/>
            <rect x="${x + w * 0.65}" y="${y + h * 0.51}" width="${w * 0.27}" height="${h * 0.31}" rx="${w * 0.015}" fill="${BRAND.accent}" fill-opacity=".5"/>`,
        monitor: `
            <rect x="${x + w * 0.04}" y="${y + h * 0.07}" width="${w * 0.2}" height="${h * 0.86}" rx="${w * 0.012}" fill="#fff" fill-opacity=".08"/>
            ${[0, 1, 2].map((i) => `<rect x="${x + w * (0.28 + i * 0.235)}" y="${y + h * 0.07}" width="${w * 0.215}" height="${h * 0.22}" rx="${w * 0.012}" fill="#fff" fill-opacity=".12"/>`).join('')}
            <rect x="${x + w * 0.28}" y="${y + h * 0.34}" width="${w * 0.685}" height="${h * 0.59}" rx="${w * 0.012}" fill="#fff" fill-opacity=".09"/>
            ${[0, 1, 2, 3, 4, 5, 6, 7].map((i) => { const bh = h * (0.12 + ((i * 37) % 9) * 0.035); return `<rect x="${x + w * (0.32 + i * 0.075)}" y="${y + h * 0.88 - bh}" width="${w * 0.04}" height="${bh}" rx="${w * 0.008}" fill="${i % 3 === 0 ? BRAND.accent : BRAND.primaryLight}" fill-opacity=".8"/>`; }).join('')}
            <path d="M${x + w * 0.31} ${y + h * 0.6} L${x + w * 0.45} ${y + h * 0.48} L${x + w * 0.58} ${y + h * 0.55} L${x + w * 0.74} ${y + h * 0.42} L${x + w * 0.93} ${y + h * 0.45}" stroke="#fff" stroke-opacity=".85" stroke-width="${w * 0.006}" fill="none" stroke-linecap="round"/>`,
    };
    return `
        <g clip-path="url(#${clip})">
            <rect x="${x}" y="${y}" width="${w}" height="${h}" fill="url(#wall)"/>
            <circle cx="${x + w * 0.22}" cy="${y + h * 0.3}" r="${m * 0.34}" fill="${BRAND.primary}" fill-opacity=".75" filter="url(#blurL)"/>
            <circle cx="${x + w * 0.85}" cy="${y + h * 0.78}" r="${m * 0.3}" fill="${BRAND.accent}" fill-opacity=".55" filter="url(#blurL)"/>
            <circle cx="${x + w * 0.75}" cy="${y + h * 0.18}" r="${m * 0.2}" fill="#8B5CF6" fill-opacity=".6" filter="url(#blurL)"/>
            ${layers[ui]}
            <path d="M${x} ${y} L${x + w * 0.6} ${y} L${x} ${y + h * 0.6} Z" fill="#fff" fill-opacity=".06"/>
        </g>`;
}

function lens(cx, cy, r) {
    return `
        <circle cx="${cx}" cy="${cy}" r="${r}" fill="#0B1120"/>
        <circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="#9CA3AF" stroke-opacity=".55" stroke-width="${Math.max(2, r * 0.08)}"/>
        <circle cx="${cx}" cy="${cy}" r="${r * 0.74}" fill="url(#glassR)"/>
        <circle cx="${cx}" cy="${cy}" r="${r * 0.3}" fill="#020617"/>
        <circle cx="${cx - r * 0.3}" cy="${cy - r * 0.32}" r="${r * 0.16}" fill="#fff" fill-opacity=".75"/>`;
}

export const DEVICES = {
    smartphone(ctx, c) {
        return `
        ${shadow(500, 880, 250, 26)}
        <g transform="translate(598 455) rotate(12)">
            <rect x="-150" y="-305" width="300" height="610" rx="54" fill="url(#body)"/>
            <rect x="-150" y="-305" width="300" height="610" rx="54" fill="url(#shine)" opacity=".45"/>
            <rect x="-150" y="-305" width="300" height="610" rx="54" fill="none" stroke="url(#edge)" stroke-width="9"/>
            <rect x="-128" y="-283" width="152" height="164" rx="44" fill="${c.b}" fill-opacity=".55"/>
            <rect x="-128" y="-283" width="152" height="164" rx="44" fill="none" stroke="#fff" stroke-opacity=".3" stroke-width="3"/>
            ${lens(-90, -246, 29)}${lens(-90, -158, 29)}${lens(-24, -202, 26)}
            <circle cx="-22" cy="-258" r="9" fill="#FEF3C7"/>
            <circle cx="0" cy="120" r="22" fill="none" stroke="#fff" stroke-opacity=".35" stroke-width="5"/>
        </g>
        <g transform="translate(405 515) rotate(-8)">
            <rect x="-158" y="-322" width="316" height="644" rx="58" fill="url(#edge)"/>
            <rect x="-150" y="-314" width="300" height="628" rx="51" fill="#05070D"/>
            ${screen(ctx, -140, -304, 280, 608, 43, 'phone')}
            <rect x="-42" y="-289" width="84" height="25" rx="12.5" fill="#05070D"/>
            <rect x="158" y="-170" width="7" height="92" rx="3" fill="${c.b}"/>
            <rect x="-165" y="-210" width="7" height="58" rx="3" fill="${c.b}"/>
            <rect x="-165" y="-138" width="7" height="58" rx="3" fill="${c.b}"/>
        </g>`;
    },

    tablet(ctx, c) {
        return `
        ${shadow(500, 900, 300, 26)}
        <g transform="translate(465 500) rotate(-5)">
            <rect x="-282" y="-372" width="564" height="744" rx="48" fill="url(#edge)"/>
            <rect x="-274" y="-364" width="548" height="728" rx="42" fill="#05070D"/>
            ${screen(ctx, -258, -348, 516, 696, 28, 'tablet')}
            <circle cx="0" cy="-356" r="4" fill="#1F2937"/>
        </g>
        <g transform="translate(835 520) rotate(-5)">
            <rect x="-15" y="-300" width="30" height="560" rx="15" fill="url(#cyl)"/>
            <path d="M-15 252 L0 318 L15 252 Z" fill="${c.b}"/>
            <path d="M-5 300 L0 318 L5 300 Z" fill="#111827"/>
            <rect x="-15" y="-180" width="30" height="5" fill="#000" fill-opacity=".18"/>
        </g>`;
    },

    laptop(ctx, c) {
        return `
        ${shadow(500, 712, 420, 22, 0.3)}
        <rect x="175" y="212" width="650" height="436" rx="24" fill="url(#edge)"/>
        <rect x="183" y="220" width="634" height="420" rx="18" fill="#05070D"/>
        ${screen(ctx, 197, 236, 606, 390, 8, 'laptop')}
        <circle cx="500" cy="228" r="3.5" fill="#1F2937"/>
        <rect x="175" y="642" width="650" height="12" rx="4" fill="${c.b}"/>
        <path d="M92 652 H908 L890 690 Q885 702 870 702 H130 Q115 702 110 690 Z" fill="url(#baseG)"/>
        <path d="M92 652 H908 L904 660 H96 Z" fill="#fff" fill-opacity=".55"/>
        <path d="M438 652 H562 Q558 666 546 666 H454 Q442 666 438 652 Z" fill="${c.b}" fill-opacity=".7"/>`;
    },

    monitor(ctx, c) {
        return `
        ${shadow(500, 792, 230, 18)}
        <path d="M462 610 H538 L552 770 H448 Z" fill="url(#cyl)"/>
        <rect x="335" y="760" width="330" height="26" rx="13" fill="url(#edge)"/>
        <rect x="110" y="160" width="780" height="472" rx="22" fill="url(#edge)"/>
        <rect x="118" y="168" width="764" height="456" rx="16" fill="#05070D"/>
        ${screen(ctx, 130, 180, 740, 432, 6, 'monitor')}
        <circle cx="500" cy="628" r="3" fill="${c.a}"/>`;
    },

    headphones(ctx, c) {
        const cup = (cx, dir) => `
            <ellipse cx="${cx}" cy="650" rx="108" ry="146" fill="url(#body)"/>
            <ellipse cx="${cx}" cy="650" rx="108" ry="146" fill="url(#shine)" opacity=".5"/>
            <ellipse cx="${cx - dir * 22}" cy="650" rx="74" ry="112" fill="url(#bodyR)"/>
            <ellipse cx="${cx - dir * 22}" cy="650" rx="74" ry="112" fill="none" stroke="${c.edge}" stroke-opacity=".7" stroke-width="5"/>
            <ellipse cx="${cx + dir * 58}" cy="650" rx="44" ry="128" fill="${c.key}"/>
            <ellipse cx="${cx + dir * 60}" cy="650" rx="22" ry="96" fill="#000" fill-opacity=".28"/>`;
        return `
        ${shadow(500, 860, 290, 24)}
        <path d="M298 560 C298 240 702 240 702 560" fill="none" stroke="url(#body)" stroke-width="54" stroke-linecap="round"/>
        <path d="M298 548 C298 258 702 258 702 548" fill="none" stroke="#fff" stroke-opacity=".28" stroke-width="7"/>
        <path d="M322 520 C326 312 674 312 678 520" fill="none" stroke="${c.key}" stroke-opacity=".8" stroke-width="20" stroke-linecap="round"/>
        <rect x="278" y="500" width="40" height="110" rx="14" fill="url(#edge)"/>
        <rect x="682" y="500" width="40" height="110" rx="14" fill="url(#edge)"/>
        ${cup(270, 1)}${cup(730, -1)}
        <circle cx="782" cy="596" r="7" fill="${BRAND.primaryLight}"/>`;
    },

    earbuds(ctx, c) {
        const bud = (tx, ty, rot, flip) => `
            <g transform="translate(${tx} ${ty}) rotate(${rot}) scale(${flip} 1)">
                <rect x="-23" y="-6" width="46" height="196" rx="23" fill="url(#cyl)"/>
                <rect x="-23" y="168" width="46" height="22" rx="11" fill="${c.edge}"/>
                <ellipse cx="0" cy="-12" rx="72" ry="64" fill="url(#body)"/>
                <ellipse cx="0" cy="-12" rx="72" ry="64" fill="url(#shine)" opacity=".6"/>
                <ellipse cx="-42" cy="-18" rx="34" ry="42" fill="${c.key}"/>
                <ellipse cx="-46" cy="-20" rx="16" ry="22" fill="#000" fill-opacity=".3"/>
                <ellipse cx="24" cy="-34" rx="17" ry="9" fill="#000" fill-opacity=".35"/>
            </g>`;
        return `
        ${shadow(500, 800, 230, 22)}
        <rect x="318" y="500" width="364" height="276" rx="124" fill="url(#body)"/>
        <rect x="318" y="500" width="364" height="276" rx="124" fill="url(#shine)" opacity=".5"/>
        <path d="M324 592 H676" stroke="${c.b}" stroke-opacity=".55" stroke-width="5"/>
        <circle cx="500" cy="700" r="7" fill="#22C55E"/>
        ${bud(395, 300, -18, 1)}${bud(605, 300, 18, -1)}`;
    },

    speaker(ctx, c) {
        const clip = ctx.id('spk');
        ctx.def(`<clipPath id="${clip}"><rect x="352" y="214" width="296" height="596" rx="148"/></clipPath>`);
        return `
        ${shadow(500, 850, 190, 22)}
        <path d="M452 222 C452 150 548 150 548 222" fill="none" stroke="${c.key}" stroke-width="20" stroke-linecap="round"/>
        <rect x="352" y="214" width="296" height="596" rx="148" fill="url(#cyl)"/>
        <g clip-path="url(#${clip})">
            <rect x="352" y="214" width="296" height="596" fill="url(#mesh)"/>
            <rect x="352" y="752" width="296" height="70" fill="${c.key}"/>
            <rect x="352" y="214" width="296" height="50" fill="${c.key}" fill-opacity=".85"/>
        </g>
        <rect x="474" y="340" width="52" height="270" rx="26" fill="${c.b}" fill-opacity=".9"/>
        <path d="M488 380 H512 M500 368 V392 M488 570 H512" stroke="#fff" stroke-width="6" stroke-linecap="round"/>
        <circle cx="500" cy="475" r="11" fill="none" stroke="#fff" stroke-width="5"/>
        <rect x="384" y="280" width="34" height="460" rx="17" fill="#fff" fill-opacity=".2"/>
        <path d="M300 410 Q262 512 300 614" stroke="${BRAND.primaryLight}" stroke-opacity=".5" stroke-width="10" fill="none" stroke-linecap="round"/>
        <path d="M252 370 Q200 512 252 654" stroke="${BRAND.primaryLight}" stroke-opacity=".28" stroke-width="10" fill="none" stroke-linecap="round"/>
        <path d="M700 410 Q738 512 700 614" stroke="${BRAND.primaryLight}" stroke-opacity=".5" stroke-width="10" fill="none" stroke-linecap="round"/>
        <path d="M748 370 Q800 512 748 654" stroke="${BRAND.primaryLight}" stroke-opacity=".28" stroke-width="10" fill="none" stroke-linecap="round"/>`;
    },

    smartwatch(ctx, c) {
        const ring = (r, color, pct) => {
            const circ = 2 * Math.PI * r;
            return `
                <circle cx="500" cy="468" r="${r}" fill="none" stroke="${color}" stroke-opacity=".22" stroke-width="17"/>
                <circle cx="500" cy="468" r="${r}" fill="none" stroke="${color}" stroke-width="17" stroke-linecap="round" stroke-dasharray="${(circ * pct).toFixed(1)} ${circ.toFixed(1)}" transform="rotate(-90 500 468)"/>`;
        };
        return `
        ${shadow(500, 905, 170, 20)}
        <rect x="412" y="112" width="176" height="250" rx="30" fill="url(#body)"/>
        <rect x="412" y="640" width="176" height="252" rx="30" fill="url(#bodyR)"/>
        ${[730, 772, 814].map((y) => `<circle cx="500" cy="${y}" r="7" fill="#000" fill-opacity=".3"/>`).join('')}
        <rect x="338" y="312" width="324" height="376" rx="94" fill="url(#edge)"/>
        <rect x="354" y="328" width="292" height="344" rx="80" fill="#05070D"/>
        ${ring(78, '#F43F5E', 0.78)}${ring(54, '#84CC16', 0.6)}${ring(30, '#22D3EE', 0.86)}
        <text x="500" y="626" text-anchor="middle" font-family="Segoe UI" font-weight="600" font-size="52" fill="#fff">10:09</text>
        <rect x="660" y="414" width="26" height="84" rx="11" fill="url(#edge)"/>
        ${[428, 442, 456, 470, 484].map((y) => `<path d="M664 ${y} H682" stroke="${c.b}" stroke-opacity=".5" stroke-width="2"/>`).join('')}
        <rect x="662" y="528" width="14" height="64" rx="6" fill="url(#edge)"/>`;
    },

    fitnessband(ctx, c) {
        return `
        ${shadow(500, 900, 140, 18)}
        <rect x="425" y="130" width="150" height="740" rx="75" fill="url(#cyl)"/>
        ${[720, 760, 800].map((y) => `<circle cx="500" cy="${y}" r="7" fill="#000" fill-opacity=".28"/>`).join('')}
        <rect x="402" y="312" width="196" height="376" rx="98" fill="url(#dark)"/>
        <rect x="402" y="312" width="196" height="376" rx="98" fill="none" stroke="${c.edge}" stroke-opacity=".6" stroke-width="5"/>
        <rect x="418" y="330" width="164" height="340" rx="82" fill="#05070D"/>
        <path d="M500 438 C470 410 438 432 452 462 C462 484 500 506 500 506 C500 506 538 484 548 462 C562 432 530 410 500 438 Z" fill="#F43F5E"/>
        <text x="500" y="580" text-anchor="middle" font-family="Segoe UI" font-weight="700" font-size="62" fill="#fff">72</text>
        <text x="500" y="616" text-anchor="middle" font-family="Segoe UI" font-weight="600" font-size="20" fill="#94A3B8">BPM</text>`;
    },

    camera(ctx, c) {
        const clip = ctx.id('cam');
        ctx.def(`<clipPath id="${clip}"><rect x="180" y="320" width="640" height="392" rx="48"/></clipPath>`);
        return `
        ${shadow(500, 760, 340, 24, 0.3)}
        <path d="M388 322 L420 252 H580 L612 322 Z" fill="url(#body)"/>
        <rect x="440" y="262" width="120" height="30" rx="8" fill="#0B1120" fill-opacity=".7"/>
        <rect x="226" y="290" width="96" height="34" rx="10" fill="url(#edge)"/>
        <rect x="648" y="288" width="118" height="36" rx="10" fill="url(#edge)"/>
        <ellipse cx="706" cy="286" rx="32" ry="11" fill="${c.edge}"/>
        <rect x="180" y="320" width="640" height="392" rx="48" fill="url(#body)"/>
        <g clip-path="url(#${clip})">
            <rect x="180" y="430" width="640" height="200" fill="${c.key}"/>
            <rect x="180" y="430" width="640" height="200" fill="url(#grain)"/>
            <rect x="676" y="320" width="150" height="392" fill="${c.key}"/>
            <rect x="676" y="320" width="150" height="392" fill="url(#grain)"/>
            <rect x="180" y="320" width="640" height="70" fill="url(#shine)" opacity=".6"/>
        </g>
        <circle cx="468" cy="516" r="192" fill="url(#dark)"/>
        <circle cx="468" cy="516" r="176" fill="none" stroke="#4B5563" stroke-width="22" stroke-dasharray="5 7"/>
        <circle cx="468" cy="516" r="150" fill="#0B1120"/>
        <circle cx="468" cy="516" r="150" fill="none" stroke="#6B7280" stroke-opacity=".6" stroke-width="4"/>
        <circle cx="468" cy="516" r="126" fill="url(#glassR)"/>
        <circle cx="468" cy="516" r="58" fill="#020617" fill-opacity=".85"/>
        <path d="M384 456 A102 102 0 0 1 452 418" stroke="#fff" stroke-opacity=".55" stroke-width="10" fill="none" stroke-linecap="round"/>
        <circle cx="430" cy="474" r="12" fill="#fff" fill-opacity=".7"/>
        <circle cx="752" cy="372" r="10" fill="#EF4444"/>`;
    },

    drone(ctx, c) {
        const corners = [[-212, -212], [212, -212], [212, 212], [-212, 212]];
        return `
        ${shadow(500, 880, 330, 26, 0.2)}
        <g transform="translate(500 500)">
            ${[45, 135, 225, 315].map((a) => `<g transform="rotate(${a})"><rect x="40" y="-19" width="270" height="38" rx="19" fill="url(#body)"/></g>`).join('')}
            ${corners.map(([x, y], i) => `
                <circle cx="${x}" cy="${y}" r="128" fill="#fff" fill-opacity=".1" stroke="${c.b}" stroke-width="11"/>
                <circle cx="${x}" cy="${y}" r="114" fill="url(#prop)"/>
                <ellipse cx="${x}" cy="${y}" rx="108" ry="12" fill="${c.b}" fill-opacity=".35" transform="rotate(${20 + i * 35} ${x} ${y})"/>
                <circle cx="${x}" cy="${y}" r="27" fill="url(#edge)"/>
                <circle cx="${x}" cy="${y}" r="9" fill="${c.b}"/>
                <circle cx="${x * 0.62}" cy="${y * 0.62}" r="7" fill="${y < 0 ? '#22C55E' : '#EF4444'}"/>`).join('')}
            <rect x="-132" y="-112" width="264" height="224" rx="82" fill="url(#body)"/>
            <rect x="-132" y="-112" width="264" height="224" rx="82" fill="url(#shine)" opacity=".55"/>
            <rect x="-70" y="-60" width="140" height="120" rx="50" fill="${c.b}" fill-opacity=".35"/>
            <circle cx="0" cy="-130" r="42" fill="#0B1120"/>
            ${lens(0, -130, 26)}
        </g>`;
    },

    console(ctx, c) {
        return `
        ${shadow(520, 768, 360, 26, 0.3)}
        <path d="M196 368 L272 296 H862 L786 368 Z" fill="url(#baseG)"/>
        <path d="M786 368 L862 296 V628 L786 706 Z" fill="${c.b}"/>
        <rect x="196" y="368" width="590" height="338" rx="10" fill="url(#body)"/>
        <rect x="196" y="368" width="590" height="338" rx="10" fill="url(#shine)" opacity=".45"/>
        ${[0, 1, 2, 3, 4, 5].map((i) => `<path d="M236 ${402 + i * 14} H746" stroke="#000" stroke-opacity=".14" stroke-width="5" stroke-linecap="round"/>`).join('')}
        <rect x="290" y="540" width="400" height="9" rx="4.5" fill="#000" fill-opacity=".35"/>
        <rect x="236" y="640" width="510" height="12" rx="6" fill="${BRAND.primaryLight}" filter="url(#blurS)"/>
        <rect x="236" y="640" width="510" height="10" rx="5" fill="#93C5FD"/>
        <circle cx="254" cy="590" r="11" fill="none" stroke="#fff" stroke-opacity=".8" stroke-width="4"/>
        <rect x="660" y="580" width="34" height="14" rx="4" fill="#0B1120" fill-opacity=".7"/>
        <rect x="706" y="580" width="34" height="14" rx="4" fill="#0B1120" fill-opacity=".7"/>`;
    },

    controller(ctx, c) {
        return `
        ${shadow(500, 800, 300, 24)}
        <rect x="292" y="370" width="150" height="46" rx="23" fill="${c.b}"/>
        <rect x="558" y="370" width="150" height="46" rx="23" fill="${c.b}"/>
        <path d="M330 398 C400 370 600 370 670 398 C762 430 802 560 822 662 C838 738 772 774 726 724 C690 684 662 624 600 614 L400 614 C338 624 310 684 274 724 C228 774 162 738 178 662 C198 560 238 430 330 398 Z" fill="url(#body)"/>
        <path d="M330 398 C400 370 600 370 670 398 C720 416 752 460 772 520 C700 470 300 470 228 520 C248 460 280 416 330 398 Z" fill="#fff" fill-opacity=".2"/>
        <rect x="352" y="458" width="30" height="92" rx="7" fill="#0B1120" fill-opacity=".8"/>
        <rect x="321" y="489" width="92" height="30" rx="7" fill="#0B1120" fill-opacity=".8"/>
        ${[[0, -40, '#22C55E'], [40, 0, '#EF4444'], [0, 40, '#3B82F6'], [-40, 0, BRAND.accent]].map(([dx, dy, col]) => `<circle cx="${640 + dx}" cy="${504 + dy}" r="19" fill="${col}"/><circle cx="${636 + dx}" cy="${499 + dy}" r="6" fill="#fff" fill-opacity=".5"/>`).join('')}
        ${[430, 570].map((x) => `<circle cx="${x}" cy="590" r="46" fill="#0B1120"/><circle cx="${x}" cy="590" r="32" fill="url(#dark)"/><circle cx="${x}" cy="590" r="32" fill="none" stroke="#fff" stroke-opacity=".15" stroke-width="4"/>`).join('')}
        <rect x="470" y="488" width="22" height="12" rx="6" fill="#0B1120" fill-opacity=".6"/>
        <rect x="508" y="488" width="22" height="12" rx="6" fill="#0B1120" fill-opacity=".6"/>
        <circle cx="500" cy="452" r="15" fill="${BRAND.primaryLight}" fill-opacity=".9"/>`;
    },

    keyboard(ctx, c) {
        const keys = [];
        const kw = 40;
        const gap = 7;
        const x0 = 151;
        for (let r = 0; r < 4; r++) {
            for (let k = 0; k < 15; k++) {
                const x = x0 + k * (kw + gap);
                const y = 392 + r * 49;
                let fill = c.key;
                if (r === 0 && k === 0) fill = BRAND.accent;
                if (r === 0 && k === 14) {
                    keys.push(`<circle cx="${x + kw / 2}" cy="${y + kw / 2}" r="22" fill="url(#edge)"/>`);
                    continue;
                }
                if (r === 2 && k === 14) fill = BRAND.primary;
                keys.push(`<rect x="${x}" y="${y}" width="${kw}" height="${kw}" rx="8" fill="${fill}"/><rect x="${x + 4}" y="${y + 3}" width="${kw - 8}" height="${kw - 12}" rx="6" fill="#fff" fill-opacity=".12"/>`);
            }
        }
        const bottom = [[60, c.key], [47, c.key], [47, c.key], [330, c.key], [47, c.key], [47, c.key], [40, c.key], [40, c.key], [40, c.key]];
        let bx = x0;
        for (const [w, fill] of bottom) {
            keys.push(`<rect x="${bx}" y="588" width="${w}" height="${kw}" rx="8" fill="${fill}"/><rect x="${bx + 4}" y="591" width="${w - 8}" height="${kw - 12}" rx="6" fill="#fff" fill-opacity=".12"/>`);
            bx += w + gap;
        }
        return `
        ${shadow(500, 690, 400, 22, 0.28)}
        <rect x="120" y="364" width="760" height="292" rx="32" fill="url(#body)"/>
        <rect x="120" y="364" width="760" height="292" rx="32" fill="url(#shine)" opacity=".4"/>
        <rect x="140" y="382" width="720" height="256" rx="20" fill="#000" fill-opacity=".14"/>
        ${keys.join('')}`;
    },

    mouse(ctx, c) {
        return `
        ${shadow(500, 790, 170, 20)}
        <path d="M500 270 C614 270 656 376 656 520 C656 684 594 766 500 766 C406 766 344 684 344 520 C344 376 386 270 500 270 Z" fill="url(#body)"/>
        <path d="M500 270 C614 270 656 376 656 520 C656 684 594 766 500 766 C406 766 344 684 344 520 C344 376 386 270 500 270 Z" fill="url(#shine)" opacity=".55"/>
        <path d="M352 470 Q500 506 648 470" stroke="${c.b}" stroke-opacity=".45" stroke-width="4" fill="none"/>
        <path d="M500 276 V488" stroke="${c.b}" stroke-opacity=".5" stroke-width="4"/>
        <rect x="484" y="336" width="32" height="78" rx="16" fill="#0B1120"/>
        ${[350, 364, 378, 392].map((y) => `<path d="M490 ${y} H510" stroke="#6B7280" stroke-width="3"/>`).join('')}
        <path d="M352 560 C356 650 400 720 452 748" stroke="${c.key}" stroke-width="16" fill="none" stroke-linecap="round"/>
        <circle cx="500" cy="660" r="12" fill="${BRAND.primaryLight}"/>`;
    },

    powerbank(ctx, c) {
        return `
        ${shadow(520, 860, 230, 22)}
        <path d="M500 762 C500 842 640 868 700 816 C760 764 790 690 762 630" stroke="#E2E8F0" stroke-width="16" fill="none" stroke-linecap="round"/>
        <path d="M500 762 C500 842 640 868 700 816 C760 764 790 690 762 630" stroke="#fff" stroke-opacity=".6" stroke-width="5" fill="none" stroke-linecap="round"/>
        <rect x="746" y="590" width="34" height="56" rx="10" fill="#CBD5E1" transform="rotate(-12 763 618)"/>
        <rect x="345" y="180" width="310" height="586" rx="62" fill="url(#body)"/>
        <rect x="345" y="180" width="310" height="586" rx="62" fill="url(#shine)" opacity=".5"/>
        <rect x="345" y="180" width="310" height="586" rx="62" fill="none" stroke="${c.edge}" stroke-opacity=".7" stroke-width="5"/>
        <path d="M522 330 L446 488 H500 L474 618 L566 450 H510 L540 330 Z" fill="#fff" fill-opacity=".92"/>
        ${[0, 1, 2, 3].map((i) => `<circle cx="${455 + i * 30}" cy="690" r="8" fill="${i < 3 ? '#34D399' : '#000'}" fill-opacity="${i < 3 ? 1 : 0.25}"/>`).join('')}
        <rect x="470" y="756" width="60" height="14" rx="7" fill="#0B1120"/>`;
    },

    tag() {
        return `
        ${shadow(500, 840, 280, 24, 0.22)}
        <path d="M660 360 C770 300 812 196 772 120" stroke="#111827" stroke-width="7" fill="none" stroke-linecap="round"/>
        <g transform="translate(500 500) rotate(-18)">
            <path d="M-230 -160 H140 L262 0 L140 160 H-230 Q-262 160 -262 128 V-128 Q-262 -160 -230 -160 Z" fill="url(#tagG)"/>
            <path d="M-230 -160 H140 L200 -80 H-262 V-128 Q-262 -160 -230 -160 Z" fill="#fff" fill-opacity=".22"/>
            <circle cx="160" cy="0" r="28" fill="#fff"/>
            <circle cx="160" cy="0" r="28" fill="none" stroke="#C2410C" stroke-width="5"/>
            <text x="-64" y="66" text-anchor="middle" font-family="Segoe UI" font-weight="900" font-size="200" fill="#fff">%</text>
        </g>`;
    },
};

/**
 * Full SVG document for a device.
 * @param {'product'|'transparent'} mode product = studio backdrop, transparent = art for tiles/hero
 */
export function renderDeviceSvg(device, c, mode = 'product') {
    const ctx = createContext(c);
    const drawing = DEVICES[device](ctx, c);
    const backdrop = mode === 'product'
        ? `<linearGradient id="bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="${c.bg[1]}"/><stop offset="1" stop-color="${c.bg[0]}"/></linearGradient>`
        : '';
    const bgLayer = mode === 'product'
        ? `<rect width="1000" height="1000" fill="url(#bg)"/>
           <circle cx="500" cy="470" r="330" fill="${c.hex}" fill-opacity=".18" filter="url(#blurXL)"/>
           <circle cx="180" cy="140" r="220" fill="#fff" fill-opacity=".7" filter="url(#blurXL)"/>`
        : '';
    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000" width="1000" height="1000"><defs>${backdrop}${ctx.defs()}</defs>${bgLayer}${drawing}</svg>`;
}
