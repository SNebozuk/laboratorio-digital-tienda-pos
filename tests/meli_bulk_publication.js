const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

async function main() {
    const elements = new Map();
    const element = id => {
        if (!elements.has(id)) elements.set(id, {
            textContent: '', disabled: false, hidden: false, listeners: {},
            classList: {toggle() {}, contains() { return false; }},
            setAttribute() {}, removeAttribute() {}, querySelectorAll() { return []; },
            addEventListener(type, listener) { this.listeners[type] = listener; }
        });
        return elements.get(id);
    };
    element('admin-app-data').textContent = JSON.stringify({api_url: '/v1/api.php', csrf_token: 'test'});
    const product = (id, changes = {}) => ({id, name: `Producto ${id}`, active: true,
        meli_publication: {state: 'unpublished', items: []},
        variants: [{active: true, stock_on_hand: 1}], ...changes});
    const catalog = [product(1), product(2, {incomplete: true}),
        product(3, {meli_publication: {state: 'published'}}),
        product(4, {meli_publication: {state: 'failed'}}), product(5, {active: false}),
        product(6, {variants: [{active: true, stock_on_hand: 0}]}), product(7), product(8)];
    const calls = [];
    let requests = 0;
    let maximum = 0;
    let refreshes = 0;
    let catalogReads = 0;
    const window = {location: {href: 'https://example.test/v1/admin/index.php'}, setInterval() {},
        MeliProductEditor: {pending: p => p.incomplete ? ['Campo pendiente'] : []},
        dispatchEvent() { refreshes++; }};
    const context = {window, URL, CustomEvent: class {},
        document: {getElementById: element, querySelectorAll() { return []; }},
        fetch: async (url, options = {}) => {
            requests++; maximum = Math.max(maximum, requests);
            await new Promise(resolve => setTimeout(resolve, 1));
            let data;
            const target = new URL(url);
            if (target.pathname.endsWith('api.php')) { catalogReads++; data = {ok: true, products: catalog}; }
            else if (options.method === 'POST') {
                const body = JSON.parse(options.body); calls.push(body);
                if (body.action === 'publication_variants') data = {ok: true, variants: body.product_id === 1 ? [{id: 101, name: 'Talle 1'}, {id: 102, name: 'Talle 2'}] : [{id: body.product_id * 100, name: 'Única'}]};
                else if (body.action === 'validate_publication') data = {ok: true, valid: body.product_id !== 7, validation: {cause: [{type: 'error', message: 'Dato inválido'}]}};
                else data = {ok: true, message: 'Publicado'};
            } else if (target.searchParams.get('action') === 'published_products') data = {ok: true, products: [], offset: 0, total: 0};
            else data = {ok: true, connected: false, configured: false};
            requests--;
            return {ok: true, json: async () => data};
        }};
    vm.runInNewContext(fs.readFileSync('v1/admin/assets/meli.js', 'utf8'), context);
    await new Promise(resolve => setTimeout(resolve, 10));
    const button = element('meli-publish-all');
    const run = button.listeners.click();
    assert.equal(button.disabled, true);
    await button.listeners.click(); // A second click cannot launch a parallel queue.
    await run;
    assert.equal(catalogReads, 1);
    assert.equal(maximum, 1, 'Requests are sequential');
    assert.deepEqual([...new Set(calls.map(c => c.product_id))], [1, 7, 8]);
    assert.deepEqual(calls.filter(c => c.product_id === 1).map(c => c.action),
        ['publication_variants', 'validate_publication', 'validate_publication', 'publish_product', 'publish_product', 'finish_publication']);
    assert.equal(calls.some(c => c.product_id === 7 && c.action === 'publish_product'), false);
    assert.equal(calls.filter(c => c.product_id === 8 && c.action === 'publish_product').length, 1);
    assert.equal(refreshes, 3);
    assert.match(element('meli-progress-text').textContent, /2 productos publicados, 1 con error/);
    assert.equal(button.disabled, false);
    assert.equal(button.textContent, 'PUBLICAR TODO');
    console.log('MeLi bulk publication tests passed');
}
main().catch(error => { console.error(error); process.exitCode = 1; });
