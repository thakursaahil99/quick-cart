/**
 * Demo reviews and product relations (related / up-sell / cross-sell) for the
 * QuickCart catalogue. Deterministic: every SKU seeds its own PRNG, so rebuilding
 * the fixtures produces identical data.
 */

const NICKNAMES = [
    'Aarav S.', 'Priya M.', 'Rohan K.', 'Ananya R.', 'Vikram P.', 'Sneha D.', 'Karan J.', 'Meera N.',
    'Arjun T.', 'Isha G.', 'Kabir L.', 'Diya B.', 'Rahul V.', 'Neha C.', 'Aditya H.', 'Pooja S.',
];

const TITLES = {
    5: ['Absolutely worth it', 'Exceeded my expectations', 'Best purchase this year', 'Premium in every way', 'Highly recommended'],
    4: ['Very good overall', 'Solid choice', 'Great value for money', 'Happy with it', 'Does the job really well'],
    3: ['Good, but not perfect', 'Decent for the price', 'Mixed feelings'],
};

const OPENERS = {
    5: [
        'I have been using it every day for a few weeks and it has been flawless.',
        'Delivery was quick and the build quality feels excellent.',
        'Upgraded from an older model and the difference is huge.',
    ],
    4: [
        'Really happy with this purchase overall.',
        'Does everything I need with very few compromises.',
        'Arrived well packed and set-up took a couple of minutes.',
    ],
    3: [
        'It does the job, though I expected a little more.',
        'Works fine for everyday use.',
    ],
};

const CLOSERS = {
    5: ['Would definitely buy again.', 'Five stars from me.', 'Totally recommend it.'],
    4: ['Minor nitpicks aside, recommended.', 'Would recommend it to friends.', 'Good buy at this price.'],
    3: ['Okay for the price.', 'Better suited to casual users.'],
};

/** Accessories that complete a purchase, by leaf category (cart cross-sells). */
const CROSS_SELLS = {
    smartphones: ['QC-EB-BUDSPRO2', 'QC-AC-POWER20K', 'QC-AC-MAG10K', 'QC-SW-PULSE3'],
    tablets: ['QC-AC-MECH75', 'QC-EB-AIRDOTS', 'QC-AC-POWER20K'],
    notebooks: ['QC-AC-GLIDEPRO', 'QC-AC-MECH75', 'QC-MN-UV27'],
    monitors: ['QC-AC-STRIKE', 'QC-AC-GLIDEPRO'],
    headphones: ['QC-AC-POWER20K', 'QC-SP-PEBBLE'],
    earbuds: ['QC-AC-MAG10K', 'QC-FB-BAND8'],
    speakers: ['QC-AC-POWER20K', 'QC-EB-AIRDOTS'],
    smartwatches: ['QC-FB-BAND8', 'QC-AC-MAG10K'],
    'fitness-bands': ['QC-EB-FITBUDS', 'QC-SW-LITE'],
    'digital-cameras': ['QC-DR-SKYLITE', 'QC-AC-POWER20K'],
    drones: ['QC-CM-ACTION4K', 'QC-AC-POWER20K'],
    consoles: ['QC-GM-WIRELESS', 'QC-GM-ELITE', 'QC-MN-ARENA32'],
    controllers: ['QC-GM-NEXUSS', 'QC-AC-POWER20K'],
    'keyboards-mice': ['QC-AC-GLIDEPRO', 'QC-AC-MECH75', 'QC-MN-STUDIO24'],
    'power-charging': ['QC-EB-AIRDOTS', 'QC-AC-SILENT'],
};

function seededRandom(seed) {
    let h = 2166136261;
    for (const ch of seed) {
        h = Math.imul(h ^ ch.charCodeAt(0), 16777619);
    }
    return () => {
        h += 0x6D2B79F5;
        let t = h;
        t = Math.imul(t ^ (t >>> 15), t | 1);
        t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
}

const pick = (rand, list) => list[Math.floor(rand() * list.length)];

function reviewsFor(product) {
    const rand = seededRandom(product.sku);
    const count = 2 + Math.floor(rand() * 3);
    const people = [...NICKNAMES].sort(() => rand() - 0.5);
    const reviews = [];
    const offset = Math.floor(rand() * 3);
    for (let i = 0; i < count; i++) {
        const rating = [5, 5, 4, 5, 4, 4, 5, 3][Math.floor(rand() * 8)];
        const highlight = product.highlights[i % product.highlights.length].split(/[:;]/)[0].trim();
        // Keep acronyms (IP68, AI, LTPO) intact; only lower-case ordinary leading words.
        const loved = /^[A-Z][a-z]/.test(highlight) ? highlight.charAt(0).toLowerCase() + highlight.slice(1) : highlight;
        reviews.push({
            nickname: people[i],
            title: pick(rand, TITLES[rating]),
            detail: `${OPENERS[rating][(i + offset) % OPENERS[rating].length]} Loved the ${loved}. ${CLOSERS[rating][(i + offset) % CLOSERS[rating].length]}`,
            rating,
        });
    }
    return reviews;
}

/**
 * @param {object[]} products catalogue products (catalog.mjs)
 * @param {object[]} categories catalogue categories (catalog.mjs)
 * @returns {Map<string, {reviews: object[], links: {related: string[], upsell: string[], crosssell: string[]}}>}
 */
export function buildMerchandising(products, categories) {
    const topOf = {};
    for (const top of categories) {
        for (const child of top.children) {
            topOf[child.key] = top.key;
        }
    }
    const result = new Map();
    for (const product of products) {
        const sameLeaf = products.filter((p) => p.sku !== product.sku && p.category === product.category);
        const sameTop = products.filter((p) => p.sku !== product.sku && topOf[p.category] === topOf[product.category]);
        const related = [...new Set([...sameLeaf, ...sameTop].map((p) => p.sku))].slice(0, 4);
        const upsell = sameLeaf
            .filter((p) => p.price > product.price)
            .sort((a, b) => a.price - b.price)
            .slice(0, 2)
            .map((p) => p.sku);
        const crosssell = (CROSS_SELLS[product.category] || []).filter((sku) => sku !== product.sku);
        result.set(product.sku, { reviews: reviewsFor(product), links: { related, upsell, crosssell } });
    }
    return result;
}
