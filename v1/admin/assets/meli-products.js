(() => {
    'use strict';
    const editors = new WeakMap();
    const esc = value => String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char]));
    const seedAttributes = [
        ['BRAND', 'Marca', true], ['PAPER_SIZE', 'Tamaño del papel', true], ['PAPER_TYPE', 'Tipo de papel', true],
        ['COLOR', 'Color'], ['SHEETS_NUMBER', 'Cantidad de hojas'], ['GRAMMAGE', 'Gramaje'],
        ['SALE_FORMAT', 'Formato de venta'], ['UNITS_PER_PACK', 'Unidades por pack'], ['FINISH', 'Acabado'],
        ['MODEL', 'Modelo'], ['GTIN', 'Código universal (GTIN)'], ['SELLER_SKU', 'SKU'],
        ['LENGTH', 'Largo de la hoja'], ['WIDTH', 'Ancho de la hoja'], ['WEIGHT', 'Peso neto del papel'],
        ['SELLER_PACKAGE_LENGTH', 'Largo del paquete'], ['SELLER_PACKAGE_WIDTH', 'Ancho del paquete'],
        ['SELLER_PACKAGE_HEIGHT', 'Alto del paquete'], ['SELLER_PACKAGE_WEIGHT', 'Peso del paquete'],
        ['VALUE_ADDED_TAX', 'IVA'], ['IMPORT_DUTY', 'Impuesto interno'], ['EMPTY_GTIN_REASON', 'Motivo de GTIN vacío'],
    ].map(([id, name, required]) => ({ id, name, tags: { required: !!required } }));
    const visibleHidden = new Set(seedAttributes.map(a => a.id));
    const shirtAttributes = [
        ['BRAND', 'Marca'], ['MODEL', 'Modelo'], ['GENDER', 'Género'], ['CLOTHING_TYPE', 'Tipo de prenda'],
        ['MAIN_MATERIAL', 'Material principal'], ['COLOR', 'Color'], ['MAIN_COLOR', 'Color principal'],
        ['SLEEVE_TYPE', 'Tipo de manga'], ['NECKLINE', 'Cuello'], ['FABRIC_DESIGN', 'Diseño de tela'],
        ['SALE_FORMAT', 'Formato de venta'], ['UNITS_PER_PACK', 'Unidades'], ['SIZE_GRID_ID', 'ID de guía de talles en MeLi'],
        ['EMPTY_GTIN_REASON', 'Motivo de código universal vacío'],
        ...seedAttributes.filter(a => a.id.startsWith('SELLER_PACKAGE_')).map(a => [a.id, a.name])
    ].map(([id, name]) => ({id, name, tags: {}}));
    shirtAttributes.forEach(a => visibleHidden.add(a.id));

    const commonAttributes = ['BRAND', 'MODEL', 'COLOR', 'SALE_FORMAT', 'UNITS_PER_PACK', 'GTIN', 'EMPTY_GTIN_REASON']
        .map(id => ({...seedAttributes.find(attribute => attribute.id === id), tags: {}}));
    const packageAttributes = seedAttributes.filter(attribute => attribute.id.startsWith('SELLER_PACKAGE_'));
    function preset(product) {
        return {
            category_id: '', catalog_product_id: '', family_name: product?.name || '',
            description: product?.description || '', variant_id: Number(product?.variants?.[0]?.id || 0),
            condition: '', listing_type_id: '', shipping_mode: '', logistic_type: '',
            local_pick_up: false, free_shipping: false, package_confirmed: false,
            publish_all_variants: true, size_grid_rows: {}, size_equivalences: {},
            pictures: product?.image_path ? [new URL(product.image_path, window.location.origin).href] : [],
            attributes: {}, sale_terms: {}, pricing: null,
        };
    }
    function field(name, label, value, extra = '') {
        return `<label>${esc(label)}<input data-meli-field="${name}" value="${esc(value)}" ${extra}></label>`;
    }
    function select(name, label, value, options) {
        return `<label>${esc(label)}<select data-meli-field="${name}"><option value="">Elegir</option>${options.map(([id, text]) => `<option value="${esc(id)}" ${String(id) === String(value) ? 'selected' : ''}>${esc(text)}</option>`).join('')}</select></label>`;
    }
    function attributeField(attribute, values, group) {
        const { id, name, tags = {} } = attribute;
        const listId = `meli-${group}-${id}`;
        const flag = tags.required ? ' · obligatorio' : tags.conditional_required ? ' · condicional' : '';
        const options = attribute.values || [];
        return `<label>${esc(name + flag)}<input data-meli-${group}="${esc(id)}" value="${esc(values[id] || '')}" maxlength="255" ${options.length ? `list="${listId}"` : ''} placeholder="${esc(attribute.default_unit ? `Valor en ${attribute.default_unit}` : '')}">
            ${options.length ? `<datalist id="${listId}">${options.map(v => `<option value="${esc(v.name)}"></option>`).join('')}</datalist>` : ''}
            ${attribute.hint ? `<small>${esc(attribute.hint)}</small>` : ''}</label>`;
    }
    function renderAttributes(editor, attributes, terms) {
        attributes = [...commonAttributes.filter(base => !attributes.some(attribute => attribute.id === base.id)), ...attributes];
        const packages = packageAttributes.map(base => attributes.find(attribute => attribute.id === base.id) || base);
        editor.root.querySelector('[data-meli-package]').innerHTML = packages.map(attribute => attributeField(attribute, editor.draft.attributes, 'attribute')).join('');
        editor.attributes = attributes;
        const editable = attributes.filter(a => !a.id.startsWith('SELLER_PACKAGE_') && (!editor.root.querySelector('[data-meli-size-chart]') || !['SIZE_GRID_ID', 'SIZE_GRID_ROW_ID'].includes(a.id)));
        const primary = editable.filter(a => !a.tags?.hidden || visibleHidden.has(a.id) || a.tags?.required || a.tags?.conditional_required);
        const optional = editable.filter(a => !primary.includes(a));
        editor.root.querySelector('[data-meli-technical]').innerHTML = `<div class="meli-product-grid">${primary.map(a => attributeField(a, editor.draft.attributes, 'attribute')).join('')}</div>
            ${optional.length ? `<details><summary>Otros atributos de la categoría</summary><div class="meli-product-grid">${optional.map(a => attributeField(a, editor.draft.attributes, 'attribute')).join('')}</div></details>` : ''}
            <details><summary>Garantía, facturación y condiciones de venta</summary><div class="meli-product-grid">${terms.length ? terms.map(a => attributeField(a, editor.draft.sale_terms, 'term')).join('') : '<p>Las condiciones disponibles se incorporan al consultar la categoría de MeLi.</p>'}</div></details>`;
    }
    function applyDefaults(draft) {
        const defaults = window.MeliWorkspace?.defaults();
        if (!defaults?.configured) return;
        for (const key of ['condition', 'listing_type_id', 'shipping_mode', 'logistic_type', 'local_pick_up', 'free_shipping', 'publish_all_variants', 'installments', 'financing_max_percent']) draft[key] = defaults[key];
    }
    function collect(editor) {
        const draft = editor.draft;
        editor.root.querySelectorAll('[data-meli-field]').forEach(el => {
            draft[el.dataset.meliField] = el.type === 'checkbox' ? el.checked : el.value.trim();
        });
        draft.variant_id = Number(draft.variant_id || 0);
        draft.size_grid_rows = {};
        draft.size_equivalences = {};
        editor.root.querySelectorAll('[data-meli-size-equivalence]').forEach(input => {
            if (input.value) draft.size_equivalences[input.dataset.meliSizeEquivalence] = input.value;
        });
        editor.root.querySelectorAll('[data-meli-size-row]').forEach(input => {
            if (input.value.trim()) draft.size_grid_rows[input.dataset.meliSizeRow] = input.value.trim();
        });
        ['attribute', 'term'].forEach(group => editor.root.querySelectorAll(`[data-meli-${group}]`).forEach(el => {
            draft[group === 'attribute' ? 'attributes' : 'sale_terms'][el.dataset[group === 'attribute' ? 'meliAttribute' : 'meliTerm']] = el.value.trim();
        }));
        draft.pictures = editor.root.querySelector('[data-meli-pictures]').value.split('\n').map(s => s.trim()).filter(Boolean);
        applyDefaults(draft);
        updatePricing(editor);
        return draft;
    }
    function priceInputs(editor) {
        const variant = editor.form.querySelector(`[data-variant-row][data-variant-id="${editor.draft.variant_id}"]`) || editor.form.querySelector('[data-variant-row]');
        const costs = {};
        editor.root.querySelectorAll('[data-meli-cost]').forEach(input => {
            costs[input.dataset.meliCost] = Number(input.value.replace(',', '.'));
        });
        const defaults = window.MeliWorkspace?.defaults();
        const common = defaults?.configured ? defaults : editor.draft.pricing || {};
        return { base_price_cents: Math.round(Number(variant?.querySelector('.variant-price').value || 0) * 100),
            packaging_cents: common.packaging_cents || 0, shipping_cents: common.shipping_cents || 0,
            other_fixed_cents: common.other_fixed_cents || 0, other_percentage: common.other_percentage || 0,
            billable_weight: costs.billable_weight, rounding_pesos: common.rounding_pesos || 100 };
    }
    function priceContext(editor, inputs) {
        const draft = editor.draft;
        return JSON.stringify({ ...inputs, category_id: draft.category_id, catalog_product_id: draft.catalog_product_id,
            listing_type_id: draft.listing_type_id, shipping_mode: draft.shipping_mode, logistic_type: draft.logistic_type,
            free_shipping: draft.free_shipping, installments: draft.installments, financing_max_percent: draft.financing_max_percent,
            variant_id: draft.variant_id, package_confirmed: draft.package_confirmed,
            package: ['SELLER_PACKAGE_LENGTH', 'SELLER_PACKAGE_WIDTH', 'SELLER_PACKAGE_HEIGHT', 'SELLER_PACKAGE_WEIGHT'].map(id => draft.attributes[id] || '') });
    }
    const money = cents => (cents / 100).toLocaleString('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 2 });
    function updatePricing(editor) {
        const inputs = priceInputs(editor);
        const key = priceContext(editor, inputs);
        const old = editor.draft.pricing;
        const valid = old?.context_key === key && old?.price_cents > 0;
        editor.draft.pricing = inputs.base_price_cents > 0 && inputs.billable_weight > 0 ? { ...inputs, ...(valid ? old : {}), context_key: valid ? key : '' } : null;
        const summary = editor.root.querySelector('[data-meli-price-summary]');
        const pricing = editor.draft.pricing;
        summary.textContent = valid ? `Precio Meli: ${money(pricing.price_cents)} · cargos por vender: ${money(pricing.sale_fee_cents)} (incluye cargo fijo ${money(pricing.fixed_fee_cents)}) · cargo por publicar: ${money(pricing.listing_fee_cents)} · otros porcentajes: ${money(pricing.other_percentage_cents)} · gastos fijos ingresados: ${money(inputs.packaging_cents + inputs.shipping_cents + inputs.other_fixed_cents)} · neto estimado: ${money(pricing.net_cents)} · objetivo: ${money(inputs.base_price_cents)}. Consulta: ${new Date(pricing.queried_at).toLocaleString('es-AR', { timeZone: 'America/Buenos_Aires' })}.${editor.draft.package_confirmed ? '' : ' El peso del paquete está pendiente de confirmar.'}`
            : `Neto a conservar: ${money(inputs.base_price_cents)}. Consultá las comisiones para calcular el precio de Meli. Los gastos no cargados no están incluidos.`;
    }
    async function calculatePrice(editor) {
        collect(editor);
        const inputs = priceInputs(editor);
        const key = priceContext(editor, inputs);
        const button = editor.root.querySelector('[data-meli-price-calculate]');
        button.disabled = true;
        editor.footer.classList.add('is-active');
        editor.progress.textContent = 'Mercado Libre: consultando cargos y verificando el neto al precio final…';
        try {
            const app = JSON.parse(document.getElementById('admin-app-data').textContent);
            const response = await fetch(new URL('meli.php', window.location.href), { method: 'POST', credentials: 'same-origin', cache: 'no-store',
                headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ csrf_token: app.csrf_token,
                    action: 'calculate_price', pricing: inputs, category_id: editor.draft.category_id,
                    catalog_product_id: editor.draft.catalog_product_id, listing_type_id: editor.draft.listing_type_id,
                    shipping_mode: editor.draft.shipping_mode, logistic_type: editor.draft.logistic_type }) });
            const data = await response.json();
            if (!response.ok || !data.ok) throw new Error(data.message || 'No se pudo calcular el precio.');
            if (!editor.root.isConnected) return;
            collect(editor);
            if (key !== priceContext(editor, priceInputs(editor))) throw new Error('Los datos cambiaron durante la consulta. Volvé a calcular.');
            editor.draft.pricing = { ...data.pricing, context_key: key };
            review(editor);
            editor.progress.textContent = 'Mercado Libre: precio calculado y neto verificado para los gastos ingresados. Guardá el borrador para conservarlo.';
        } catch (error) {
            editor.draft.pricing = { ...inputs, context_key: '' };
            if (editor.root.isConnected) {
                updatePricing(editor);
                editor.progress.textContent = `Mercado Libre: ${error.message}`;
            }
        } finally {
            button.disabled = false;
            editor.footer.classList.remove('is-active');
        }
    }
    function review(editor) {
        const draft = collect(editor);
        const missing = editor.attributes.filter(a => a.tags?.required && !draft.attributes[a.id]).map(a => a.name);
        if (!draft.category_id) missing.push('Categoría');
        if (!draft.family_name) missing.push('Nombre de familia');
        if (!draft.listing_type_id) missing.push('Tipo de publicación');
        if (!draft.shipping_mode) missing.push('Modalidad de envío');
        if (!draft.pictures.length) missing.push('Foto');
        if (editor.root.querySelector('[data-meli-size-chart]')) {
            if (!draft.attributes.SIZE_GRID_ID) missing.push('Guía de talles en MeLi');
            editor.root.querySelectorAll('[data-meli-size-row]').forEach(input => {
                if (!input.value.trim()) missing.push(`Fila MeLi de ${input.closest('label').firstChild.textContent.trim()}`);
            });
        }
        if (!draft.package_confirmed && !(['MLA109042', 'MLA109085'].includes(draft.category_id) && draft.package_estimated)) missing.push('Confirmar el paquete o aceptar sus valores estimados');
        if (!draft.pricing?.billable_weight) missing.push('Peso facturable');
        const variant = editor.form.querySelector(`[data-variant-row][data-variant-id="${draft.variant_id}"]`) || editor.form.querySelector('[data-variant-row]');
        if (!variant || Number(variant.querySelector('.variant-price').value) <= 0) missing.push('Precio');
        if (!variant || Number(variant.querySelector('.variant-stock').value) <= 0) missing.push('Stock');
        editor.root.querySelector('[data-meli-review]').textContent = missing.length
            ? `Pendiente: ${missing.join(', ')}. Los campos condicionales dependen de la validación de Mercado Libre.`
            : 'Ficha preparada. Guardá los cambios y presioná el botón MeLi en Productos: validará cada talle y calculará las comisiones vigentes antes de publicar.';
    }
    function calculatePaperWeight(editor) {
        collect(editor);
        const attributes = editor.draft.attributes;
        const number = text => Number(String(text || '').trim().replace(',', '.').split(/\s+/)[0]);
        const cm = text => {
            const value = number(text);
            const unit = String(text || '').trim().split(/\s+/)[1];
            return unit === 'mm' ? value / 10 : unit === 'm' ? value * 100 : unit === 'cm' ? value : NaN;
        };
        const grams = number(attributes.GRAMMAGE);
        const sheets = number(attributes.SHEETS_NUMBER);
        const length = cm(attributes.LENGTH);
        const width = cm(attributes.WIDTH);
        if (![grams, sheets, length, width].every(value => Number.isFinite(value) && value > 0) || !Number.isInteger(sheets)) {
            editor.progress.textContent = 'Completá gramaje, hojas, largo y ancho de la hoja (con unidad cm, mm o m) para calcular el peso.';
            return;
        }
        const weight = Math.round((length * width / 10000) * grams * sheets * 100) / 100;
        attributes.WEIGHT = `${weight} g`;
        const input = editor.root.querySelector('[data-meli-attribute="WEIGHT"]');
        if (input) input.value = attributes.WEIGHT;
        editor.progress.textContent = `Peso neto calculado: ${weight.toLocaleString('es-AR')} g. El peso del paquete incluye además el embalaje y se mide por separado.`;
        editor.draft.package_confirmed = false;
        editor.root.querySelector('[data-meli-field="package_confirmed"]').checked = false;
        review(editor);
    }
    async function requirements(editor) {
        collect(editor);
        const category = editor.draft.category_id;
        if (!/^MLA\d+$/.test(category)) {
            editor.progress.textContent = 'Ingresá un código de categoría MLA válido.';
            return;
        }
        const sequence = ++editor.sequence;
        const button = editor.root.querySelector('[data-meli-load]');
        button.disabled = true;
        editor.footer.classList.add('is-active');
        editor.progress.textContent = 'Mercado Libre: consultando categoría, atributos y opciones de la cuenta…';
        try {
            const endpoint = new URL('meli.php', window.location.href);
            endpoint.search = new URLSearchParams({ action: 'product_requirements', category_id: category });
            const response = await fetch(endpoint, { credentials: 'same-origin', cache: 'no-store' });
            const data = await response.json();
            if (!response.ok || !data.ok) throw new Error(data.message || 'No se pudo consultar la categoría.');
            if (!editor.root.isConnected || sequence !== editor.sequence) return;
            collect(editor);
            if (category !== editor.draft.category_id) return;
            if (editor.loadedCategory && editor.loadedCategory !== category) editor.draft.attributes = {};
            editor.loadedCategory = category;
            editor.draft.required_attributes = Object.fromEntries(data.attributes.filter(attribute => attribute.tags?.required).map(attribute => [attribute.id, attribute.name]));
            editor.root.querySelector('[data-meli-category-name]').textContent = data.category.path_from_root.map(c => c.name).join(' / ');
            renderAttributes(editor, data.attributes, data.sale_terms);
            const setOptions = (name, options) => {
                const el = editor.root.querySelector(`[data-meli-field="${name}"]`);
                if (!el) return;
                el.innerHTML = '<option value="">Elegir</option>' + options.map(([id, text]) => `<option value="${esc(id)}">${esc(text)}</option>`).join('');
                el.value = editor.draft[name];
            };
            setOptions('listing_type_id', data.listing_types.map(t => [t.id, t.name]));
            setOptions('shipping_mode', data.shipping_modes.map(id => [id, ({ me2: 'Mercado Envíos', me1: 'Mercado Envíos 1', custom: 'Envío propio', not_specified: 'A convenir' })[id] || id]));
            editor.logistics = data.shipping_logistics || [];
            updateLogistics(editor);
            editor.progress.textContent = `Mercado Libre: ficha consultada. ${data.user_product_seller ? 'La cuenta publica por nombre de familia.' : 'Revisá el título al preparar la publicación.'}${!data.shipping_modes.includes('me2') ? ' Mercado Envíos todavía no está habilitado en la cuenta.' : ''}`;
            review(editor);
        } catch (error) {
            if (editor.root.isConnected) editor.progress.textContent = `Mercado Libre: ${error.message} El borrador se conserva.`;
        } finally {
            button.disabled = false;
            editor.footer.classList.remove('is-active');
        }
    }
    function updateLogistics(editor) {
        if (!editor.root.querySelector('[data-meli-field="shipping_mode"]')) return;
        const mode = editor.root.querySelector('[data-meli-field="shipping_mode"]').value;
        const options = (editor.logistics || []).filter(option => option.mode === mode).flatMap(option => option.types || []).filter(type => type.status === 'active');
        const select = editor.root.querySelector('[data-meli-field="logistic_type"]');
        const previous = select.value;
        select.innerHTML = '<option value="">Elegir</option>' + options.map(type => `<option value="${esc(type.type)}">${esc(({not_specified: 'A convenir', custom: 'Envío propio', drop_off: 'Despacho en sucursal', cross_docking: 'Colecta', fulfillment: 'Full', self_service: 'Flex'})[type.type] || type.type)}</option>`).join('');
        select.value = options.some(type => type.type === previous) ? previous : options.length === 1 ? options[0].type : '';
    }
    const sizeKey = value => String(value || '').trim().replace(/^talle\s*/i, '').toLocaleLowerCase('es');
    async function incorporateSizes(editor, product) {
        const app = JSON.parse(document.getElementById('admin-app-data').textContent);
        const url = new URL(app.api_url, window.location.href);
        url.searchParams.set('action', 'size_guide');
        const response = await fetch(url, {credentials: 'same-origin', cache: 'no-store'});
        const data = await response.json();
        if (!response.ok || !data.size_guide) throw new Error(data.message || 'No se pudo consultar la tabla de talles.');
        const groups = [...new Set(data.size_guide.rows.map(row => row.group))];
        const select = editor.root.querySelector('[data-meli-size-group]');
        const previous = select.value;
        select.innerHTML = '<option value="">Elegir tabla</option>' + groups.map(group => `<option value="${esc(group)}">${esc(group)}</option>`).join('');
        select.value = groups.includes(previous) ? previous : groups.length === 1 ? groups[0] : '';
        if (!select.value) throw new Error('Elegí el grupo de la tabla de talles que corresponde a esta remera y volvé a incorporar las medidas.');
        const rows = (product.variants || []).map(v => {
            const matching = data.size_guide.rows.filter(row => row.group === select.value && sizeKey(row.size) === sizeKey(v.name));
            if (matching.length !== 1 || !matching[0].width || !matching[0].length) throw new Error(`Faltan medidas inequívocas para ${v.name} en ${select.value}.`);
            return matching[0];
        });
        const description = editor.root.querySelector('[data-meli-field="description"]');
        const base = description.value.split('\n\nGUÍA DE TALLES — ANCHO × LARGO')[0];
        description.value = `${base}\n\nGUÍA DE TALLES — ANCHO × LARGO\n${rows.map(row => `Talle ${sizeKey(row.size)}: ${row.width} × ${row.length}`).join('\n')}\n${data.size_guide.intro || ''}`;
        review(editor);
        editor.progress.textContent = 'Medidas incorporadas desde la tabla elegida. Guardá la ficha para conservarlas.';
    }
    async function linkSizeChart(editor, product) {
        const draft = collect(editor);
        const app = JSON.parse(document.getElementById('admin-app-data').textContent);
        const response = await fetch(new URL('meli.php', window.location.href), {method: 'POST', credentials: 'same-origin', cache: 'no-store',
            headers: {'Content-Type': 'application/json'}, body: JSON.stringify({csrf_token: app.csrf_token, action: 'size_chart', chart_id: draft.attributes.SIZE_GRID_ID || ''})});
        const data = await response.json();
        if (!response.ok || !data.ok) throw new Error(data.message || 'No se pudo consultar la guía.');
        const main = data.chart.main_attribute_id || 'SIZE';
        const assignments = (product.variants || []).map(variant => {
            const rows = data.chart.rows.filter(row => row.attributes.some(a => a.id === main && a.values?.some(value => sizeKey(value.name) === sizeKey(variant.name))));
            if (rows.length !== 1) throw new Error(`La guía no tiene una única fila correspondiente a ${variant.name}. No se asignan equivalencias automáticamente.`);
            return [variant.id, rows[0].id];
        });
        assignments.forEach(([id, row]) => { editor.root.querySelector(`[data-meli-size-row="${id}"]`).value = row; });
        review(editor);
        editor.progress.textContent = 'Guía vinculada por talle. Revisá sus medidas y guardá la ficha.';
    }
    async function createSizeChart(editor, product) {
        const group = editor.root.querySelector('[data-meli-size-group]').value;
        const app = JSON.parse(document.getElementById('admin-app-data').textContent);
        editor.progress.textContent = 'MeLi: creando la guía con las medidas de la tabla elegida…';
        const response = await fetch(new URL('meli.php', window.location.href), {method: 'POST', credentials: 'same-origin',
            headers: {'Content-Type': 'application/json'}, body: JSON.stringify({csrf_token: app.csrf_token,
                action: 'create_shirt_size_chart', product_id: product.id, group})});
        const data = await response.json();
        if (!response.ok || !data.ok) throw new Error(data.message || 'No se pudo crear la guía de talles.');
        editor.root.querySelector('[data-meli-attribute="SIZE_GRID_ID"]').value = data.chart.id;
        for (const [id, row] of Object.entries(data.size_grid_rows || {})) editor.root.querySelector(`[data-meli-size-row="${id}"]`).value = row;
        if (!data.size_grid_rows) await linkSizeChart(editor, product);
        review(editor);
        editor.progress.textContent = 'Guía creada y guardada en MeLi. Guardá los cambios de la ficha y validá los talles.';
    }
    async function validateSizes(editor, product) {
        const app = JSON.parse(document.getElementById('admin-app-data').textContent);
        const variants = (product.variants || []).filter(v => v.active && Number(v.stock_on_hand) > 0);
        if (!variants.length) throw new Error('No hay talles activos con stock.');
        for (const variant of variants) {
            editor.progress.textContent = `MeLi: validando ${variant.name} sin publicar…`;
            const response = await fetch(new URL('meli.php', window.location.href), {method: 'POST', credentials: 'same-origin',
                headers: {'Content-Type': 'application/json'}, body: JSON.stringify({csrf_token: app.csrf_token,
                    action: 'validate_publication', product_id: product.id, variant_id: variant.id})});
            const data = await response.json();
            if (!response.ok || !data.ok || !data.valid) {
                const errors = (data.validation?.cause || []).filter(c => c.type !== 'warning').map(c => c.message || c.code).join(' · ');
                throw new Error(`${variant.name}: ${data.message || errors || 'MeLi no aprobó la ficha.'}`);
            }
        }
        editor.progress.textContent = `MeLi aprobó los ${variants.length} talles con stock. Podés publicar desde el botón de Productos.`;
    }
    function mount(form, product) {
        if (!form) return;
        const draft = product?.meli ? structuredClone(product.meli) : preset(product);
        applyDefaults(draft);
        const shirt = /remera/i.test(product?.name || '');
        const garment = shirt || draft.category_id === 'MLA109085';
        draft.attributes ||= {};
        if (shirt) {
            draft.attributes.BRAND = 'Generic';
            draft.publish_all_variants ??= true;
        }
        draft.sale_terms ||= {};
        draft.logistic_type ||= ['custom', 'not_specified'].includes(draft.shipping_mode) ? draft.shipping_mode : '';
        const pricing = draft.pricing || {};
        const costField = (name, label, value, step = '0.01') => `<label>${esc(label)}<input data-meli-cost="${name}" type="number" min="0" step="${step}" value="${esc(value)}"></label>`;
        const packageWeight = String(draft.attributes.SELLER_PACKAGE_WEIGHT || '').trim().replace(',', '.').split(/\s+/);
        const packageGrams = Number(packageWeight[0]) * (packageWeight[1] === 'kg' ? 1000 : 1);
        const root = document.createElement('details');
        root.className = 'meli-product-editor';
        root.open = true;
        root.innerHTML = `<summary>MERCADO LIBRE · FICHA DE PUBLICACIÓN</summary>
            <p>Estructura general disponible para todos los productos. Podés guardar un borrador con campos pendientes; completalos y validalos antes de publicar. Guardar esta ficha no publica en MeLi.</p>
            <h3>1. IDENTIFICACIÓN</h3>
            <div class="meli-product-grid">${field('category_id', 'Categoría de Mercado Libre', draft.category_id, 'placeholder="MLA…"')}
            ${field('catalog_product_id', 'Producto del catálogo · opcional', draft.catalog_product_id, 'placeholder="MLA…"')}
            ${field('family_name', 'Nombre de familia / título de publicación', draft.family_name, 'maxlength="120"')}</div>
            <h3>2. VARIANTES</h3><p>Se usan las variantes del producto con su precio, stock, SKU y código de barras. La selección de todas las variantes se define en el engranaje de MeLi.</p>
            <div class="meli-product-grid">${select('variant_id', 'Variante · usa su precio, stock, SKU y código', draft.variant_id, (product?.variants || []).map(v => [v.id, v.name]))}</div>
            <p>Condición, publicación, envío, cuotas y cálculo del precio se configuran para todos los productos desde el engranaje de la sección MeLi.</p>
            <button class="primary-button fit-button" type="button" data-meli-load>CONSULTAR REQUISITOS</button>
            ${garment ? `<h3>TALLES DE LA PRENDA</h3><p>Los talles sin stock no se publican. No se convierten los talles numéricos a S, M o L.</p>
            <button class="primary-button fit-button" type="button" data-meli-size-description>INCORPORAR MEDIDAS DE TABLA DE TALLES</button>
            <label>Tabla de medidas de esta prenda<select data-meli-size-group><option value="">Elegir tabla</option></select></label>
            <label>ID de guía personalizada de MeLi<input data-meli-attribute="SIZE_GRID_ID" value="${esc(draft.attributes.SIZE_GRID_ID || '')}" inputmode="numeric"></label>
            <p>Usá una guía personalizada de esta prenda creada en tu cuenta de MeLi con estas mismas medidas. Ingresá su ID en la ficha técnica y cargá sus filas.</p>
            <button class="primary-button fit-button" type="button" data-meli-size-chart>VINCULAR GUÍA DE MELI POR TALLE</button>
            <button class="primary-button fit-button" type="button" data-meli-size-create>CREAR GUÍA EN MELI CON ESTA TABLA</button>
            <p>Guardá primero la ficha. La guía utiliza las medidas existentes y conserva los talles numéricos.</p>
            <p>Elegí las equivalencias estándar reales de cada talle. Tus talles numéricos se mantienen en la publicación.</p>
            <div class="meli-size-rows">${(product?.variants || []).map(v => `<label>${esc(v.name)} · Equivalencia MeLi<select data-meli-size-equivalence="${Number(v.id)}"><option value="">Elegir</option>${['3XS','2XS','XS','S','M','L','XL','2XL','3XL','4XL','5XL','6XL','7XL','8XL','9XL','10XL'].map(size => `<option value="${size}" ${draft.size_equivalences?.[v.id] === size ? 'selected' : ''}>${size}</option>`).join('')}</select></label>`).join('')}</div>
            <div class="meli-size-rows">${(product?.variants || []).map(v => `<label>${esc(v.name)} · ID de fila MeLi<input data-meli-size-row="${Number(v.id)}" value="${esc(draft.size_grid_rows?.[v.id] || '')}" placeholder="123456:1"></label>`).join('')}</div>` : ''}
            <h3>3. DESCRIPCIÓN Y FOTOS</h3>
            <label>Descripción en texto plano<textarea data-meli-field="description" rows="4">${esc(draft.description)}</textarea></label>
            <label>Fotos · una URL HTTPS por línea<textarea data-meli-pictures rows="3">${esc((draft.pictures || []).join('\n'))}</textarea></label>
            <h3>4. CARACTERÍSTICAS</h3><p>Los campos específicos y obligatorios se adaptan a la categoría elegida al consultar requisitos.</p><div data-meli-technical></div>
            <h3>5. PAQUETE PARA ENVÍO</h3><div class="meli-product-grid" data-meli-package></div>
            ${garment ? '<p>La tabla de talles corresponde a la prenda extendida; el paquete debe incluir el embalaje.</p>' : draft.category_id === 'MLA416632' ? '<button class="primary-button fit-button" type="button" data-meli-calculate>CALCULAR PESO NETO DEL PAPEL</button>' : ''}
            ${draft.category_id === 'MLA416632' && draft.attributes.SHEETS_NUMBER === '20' && draft.attributes.PAPER_SIZE === 'A4' && draft.attributes.GRAMMAGE === '200 g' ? `<p>Para A4 de 200 g/m² × 20 hojas: 0,21 × 0,297 × 200 × 20 = <strong>249,48 g de papel</strong>. Las hojas miden 21 × 29,7 cm. Un paquete contiene 20 hojas; no son 20 paquetes.</p>
            <p>Propuesta de envío para el producto de prueba: <strong>32 × 23 × 1 cm y 280 g</strong>, con 30,52 g de margen para embalaje. Son estimaciones; el espesor y el peso final requieren medición.</p>` : '<p>Ingresá las medidas y el peso del paquete completo, incluyendo el embalaje.</p>'}
            <label class="meli-product-checks"><input type="checkbox" data-meli-field="package_confirmed" ${draft.package_confirmed ? 'checked' : ''}> Medí el paquete completo y confirmé las medidas y el peso cargados</label>
            ${garment ? `<label class="meli-product-checks"><input type="checkbox" data-meli-field="package_estimated" ${draft.package_estimated ? 'checked' : ''}> Usar para publicar los valores estimados del paquete; todavía no fueron medidos</label>
            <button class="primary-button fit-button" type="button" data-meli-size-validate>VALIDAR TALLES EN MELI SIN PUBLICAR</button><p>Guardá los cambios antes de validar. Se comprueba cada talle activo con stock.</p>` : ''}
            <p data-meli-category-name></p>
            <p>Moneda: ARS · Compra inmediata. Cada variante usa su stock y su precio como neto objetivo. El botón MeLi calcula las comisiones vigentes automáticamente al publicar.</p>
            <h3>6. PRECIO MELI · COMISIONES Y GASTOS</h3>
            <div class="meli-product-grid">${costField('billable_weight', 'Peso facturable para Meli · gramos', pricing.billable_weight || packageGrams || '', '1')}</div>
            <p>La fórmula usa el precio de cada variante, las comisiones vigentes y los gastos generales del engranaje. El envío ingresado allí es un gasto estimado, no una cotización automática. Confirmá el peso facturable de este producto.</p>
            <button class="primary-button fit-button" type="button" data-meli-price-calculate>CALCULAR PRECIO CON COMISIONES</button>
            <p data-meli-price-summary role="status"></p>
            <h3>7. REVISIÓN DE PUBLICACIÓN</h3><p data-meli-review role="status"></p>
            <footer class="meli-progress-footer" role="status" aria-live="polite"><span data-meli-progress>Mercado Libre: borrador sin publicar.</span><span class="meli-progress-dots" aria-hidden="true"><i></i><i></i><i></i></span></footer>`;
        form.querySelector('.product-save-actions').before(root);
        const editor = { form, root, draft, sequence: 0, loadedCategory: draft.category_id, attributes: [],
            footer: root.querySelector('footer'), progress: root.querySelector('[data-meli-progress]') };
        editors.set(form, editor);
        renderAttributes(editor, shirt ? shirtAttributes : draft.category_id === 'MLA416632' ? seedAttributes : [], []);
        root.addEventListener('input', event => {
            if (event.target.matches('[data-meli-attribute^="SELLER_PACKAGE_"], [data-meli-attribute="LENGTH"], [data-meli-attribute="WIDTH"], [data-meli-attribute="GRAMMAGE"], [data-meli-attribute="SHEETS_NUMBER"]')) {
                root.querySelector('[data-meli-field="package_confirmed"]').checked = false;
                const estimated = root.querySelector('[data-meli-field="package_estimated"]');
                if (estimated) estimated.checked = false;
            }
            review(editor);
        });
        root.addEventListener('change', event => {
            if (event.target.matches('[data-meli-field="shipping_mode"]')) updateLogistics(editor);
            review(editor);
        });
        form.addEventListener('input', event => {
            if (event.target.matches('.variant-price')) review(editor);
        });
        root.querySelector('[data-meli-load]').addEventListener('click', () => requirements(editor));
        root.querySelector('[data-meli-calculate]')?.addEventListener('click', () => calculatePaperWeight(editor));
        root.querySelector('[data-meli-price-calculate]').addEventListener('click', () => calculatePrice(editor));
        for (const [selector, operation] of [['[data-meli-size-description]', incorporateSizes], ['[data-meli-size-chart]', linkSizeChart], ['[data-meli-size-create]', createSizeChart], ['[data-meli-size-validate]', validateSizes]]) {
            root.querySelector(selector)?.addEventListener('click', async event => {
                const button = event.currentTarget;
                button.disabled = true;
                try { await operation(editor, product); }
                catch (error) { editor.progress.textContent = error.message; }
                finally { button.disabled = false; }
            });
        }
        review(editor);
        if (draft.category_id) requirements(editor);
    }
    function pending(product) {
        const draft = product.meli;
        if (!draft) return ['Ficha de publicación sin guardar'];
        const missing = [];
        if (!draft.category_id) missing.push('Categoría');
        if (!draft.family_name?.trim()) missing.push('Título');
        if (!draft.pictures?.length) missing.push('Fotos');
        for (const [id, label] of Object.entries(draft.required_attributes || {})) {
            if (!['SIZE', 'SIZE_GRID_ROW_ID', 'SELLER_SKU', 'GTIN'].includes(id) && !draft.attributes?.[id]?.trim()) missing.push(label);
        }
        for (const attribute of packageAttributes) if (!draft.attributes?.[attribute.id]?.trim()) missing.push(attribute.name);
        if (!draft.package_confirmed && !(['MLA109042', 'MLA109085'].includes(draft.category_id) && draft.package_estimated)) missing.push('Confirmación del paquete');
        if (!draft.pricing?.billable_weight) missing.push('Peso facturable');
        const defaults = window.MeliWorkspace?.defaults();
        if (!(defaults?.configured ? defaults.listing_type_id : draft.listing_type_id)) missing.push('Tipo de publicación');
        if (!(defaults?.configured ? defaults.shipping_mode : draft.shipping_mode)) missing.push('Modalidad de envío');
        const all = defaults?.configured ? defaults.publish_all_variants : draft.publish_all_variants;
        const variants = (product.variants || []).filter(variant => all || Number(variant.id) === Number(draft.variant_id));
        if (!variants.length) missing.push('Variante');
        if (variants.some(variant => !(Number(variant.price_cents) > 0))) missing.push('Precio de las variantes');
        if (['MLA109042', 'MLA109085'].includes(draft.category_id)) {
            if (!draft.attributes?.SIZE_GRID_ID) missing.push('Guía de talles');
            if (variants.some(variant => !draft.size_grid_rows?.[variant.id])) missing.push('Filas de la guía de talles');
        }
        return missing;
    }
    window.MeliProductEditor = { mount, pending, read(form) {
        const editor = editors.get(form);
        if (!editor) return null;
        const draft = collect(editor);
        return draft;
    } };
})();
