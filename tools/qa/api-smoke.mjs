#!/usr/bin/env node
// QuickCart REST API smoke test (Node 18+, no dependencies).
//
//   node tools/qa/api-smoke.mjs
//
// Environment:
//   QC_BASE_URL        default http://127.0.0.1:8080   (where nginx listens)
//   QC_HOST            default quickcart.localhost:8080 (Host header Magento expects)
//   QC_STORE           default default                  (store code in /rest/{store}/V1)
//   QC_ADMIN_USER / QC_ADMIN_PASSWORD   optional: also checks the admin-only endpoints
//
// Read-only except for one failed guest-tracking lookup (counts towards the per-IP limit).
// Exits 1 when any check fails.

const base = `${process.env.QC_BASE_URL || 'http://127.0.0.1:8080'}/rest/${process.env.QC_STORE || 'default'}/V1`;
const headers = { 'Content-Type': 'application/json', Host: process.env.QC_HOST || 'quickcart.localhost:8080' };
let failures = 0;
let checks = 0;

async function call(method, path, body, token) {
    const res = await fetch(base + path, {
        method,
        headers: token ? { ...headers, Authorization: `Bearer ${token}` } : headers,
        body: body ? JSON.stringify(body) : undefined,
    });
    const text = await res.text();
    let json;
    try { json = JSON.parse(text); } catch { json = text; }
    return { status: res.status, json };
}

function check(name, condition, detail = '') {
    checks++;
    if (condition) {
        console.log(`  ok    ${name}`);
    } else {
        failures++;
        console.log(`  FAIL  ${name}${detail ? ` -> ${detail}` : ''}`);
    }
}

const CARD_FIELDS = ['id', 'sku', 'name', 'url', 'price', 'final_price', 'discount_percent', 'currency', 'is_in_stock', 'labels'];

console.log(`QuickCart API smoke test against ${base}\n`);

console.log('Products');
let r = await call('GET', '/quickcart/products?pageSize=5&sortBy=price&sortDir=asc');
check('list returns 200 with items', r.status === 200 && r.json.items?.length > 0, `status ${r.status}`);
check('cards have the documented fields', CARD_FIELDS.every((f) => f in (r.json.items?.[0] || {})));
const prices = (r.json.items || []).map((p) => p.final_price);
check('sorted by price ascending', prices.every((p, i) => i === 0 || prices[i - 1] <= p), prices.join(','));
check('page size respected', (r.json.items || []).length <= 5 && r.json.page_size === 5);
const sku = r.json.items?.[0]?.sku;

r = await call('GET', '/quickcart/products?search=%25&pageSize=5');
check('search wildcards are escaped', r.status === 200 && r.json.total_count === 0, `total ${r.json.total_count}`);
r = await call('GET', '/quickcart/products?sortBy=bogus');
check('invalid sort -> 400', r.status === 400, `status ${r.status}`);
r = await call('GET', '/quickcart/products?priceFrom=900&priceTo=10');
check('reversed price range -> 400', r.status === 400, `status ${r.status}`);
r = await call('GET', '/quickcart/products?page=999');
check('page past the end -> empty items', r.status === 200 && r.json.items.length === 0);

if (sku) {
    r = await call('GET', `/quickcart/products/${encodeURIComponent(sku)}`);
    check('detail returns the product', r.status === 200 && r.json.product?.sku === sku, `status ${r.status}`);
    check('detail has specifications and images', Array.isArray(r.json.specifications) && Array.isArray(r.json.images));
    r = await call('GET', `/quickcart/products/${encodeURIComponent(sku)}/recommendations`);
    check('recommendations returns both lists', r.status === 200 && Array.isArray(r.json.bought_together) && Array.isArray(r.json.similar));
}
r = await call('GET', '/quickcart/products/DOES-NOT-EXIST');
check('unknown SKU -> 404', r.status === 404, `status ${r.status}`);

console.log('\nCategories');
r = await call('GET', '/quickcart/categories?depth=2');
check('tree returns top-level categories', r.status === 200 && Array.isArray(r.json) && r.json.length > 0, `status ${r.status}`);
const category = r.json?.[0];
check('nodes carry counts and children', category && typeof category.product_count === 'number' && Array.isArray(category.children));
if (category) {
    r = await call('GET', `/quickcart/categories/${category.id}/products?pageSize=3`);
    check('category products', r.status === 200 && r.json.total_count >= 0, `status ${r.status}`);
}
r = await call('GET', '/quickcart/categories/999999/products');
check('unknown category -> 404', r.status === 404, `status ${r.status}`);

console.log('\nOrders & customers');
r = await call('POST', '/quickcart/orders/track', { orderNumber: '000000001', contact: 'smoke-test@example.com' });
check('guest tracking with wrong contact -> 404', r.status === 404, `status ${r.status}`);
r = await call('GET', '/quickcart/orders/stats');
check('admin stats without a token -> 401', r.status === 401, `status ${r.status}`);
r = await call('GET', '/quickcart/customers/me');
check('own profile without a token -> 401', r.status === 401, `status ${r.status}`);

if (process.env.QC_ADMIN_USER && process.env.QC_ADMIN_PASSWORD) {
    const token = (await call('POST', '/integration/admin/token', {
        username: process.env.QC_ADMIN_USER, password: process.env.QC_ADMIN_PASSWORD,
    })).json;
    check('admin token issued', typeof token === 'string' && token.length > 20);
    r = await call('GET', '/quickcart/orders/stats', null, token);
    check('admin stats', r.status === 200 && typeof r.json.revenue === 'number' && Array.isArray(r.json.statuses), `status ${r.status}`);
    r = await call('GET', '/quickcart/orders/stats?from=2026-12-01&to=2026-11-01', null, token);
    check('reversed stats range -> 400', r.status === 400, `status ${r.status}`);
} else {
    console.log('  skip  admin endpoints (set QC_ADMIN_USER and QC_ADMIN_PASSWORD)');
}

console.log(`\n${checks - failures}/${checks} checks passed`);
process.exit(failures ? 1 : 0);
