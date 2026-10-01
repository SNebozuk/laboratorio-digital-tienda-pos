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

    function preset(product) {
        const variant = product?.variants?.[0];
        const isTest = /art[-‑\s]?jet/i.test(product?.name || '') && /\b200\s*g\b/i.test(product?.name || '')
            && /\ba4\b/i.test(product?.name || '') && /\b20\s*hojas\b/i.test(product?.name || '');
        return {
            category_id: isTest ? 'MLA416632' : '', family_name: isTest ? 'Papel fotográfico brillante Art-Jet A4 200g 20 hojas' : product?.name || '',
            description: product?.description || '', variant_id: Number(variant?.id || 0),
            condition: 'new', listing_type_id: isTest ? 'gold_special' : '', shipping_mode: isTest ? 'not_specified' : '', logistic_type: isTest ? 'not_specified' : '', catalog_product_id: isTest ? 'MLA28818853' : '',
            local_pick_up: false, free_shipping: false, package_confirmed: false,
            pictures: product?.image_path ? [new URL(product.image_path, window.location.origin).href] : [],
            attributes: isTest ? { BRAND: 'Art-Jet', PAPER_SIZE: 'A4', PAPER_TYPE: 'Fotográfico', COLOR: 'Blanco',
                MAIN_COLOR: 'Blanco', SHEETS_NUMBER: '20', GRAMMAGE: '200 g', SALE_FORMAT: 'Unidad', UNITS_PER_PACK: '1',
                FINISH: 'Brillante', MODEL: 'A4 200 g 20 hojas', GTIN: variant?.barcode || '', SELLER_SKU: variant?.sku || '',
                LENGTH: '29.7 cm', WIDTH: '21 cm', WEIGHT: '249.48 g', SELLER_PACKAGE_LENGTH: '32 cm',
                SELLER_PACKAGE_WIDTH: '23 cm', SELLER_PACKAGE_HEIGHT: '1 cm', SELLER_PACKAGE_WEIGHT: '280 g' } : {},
            sale_terms: isTest ? { INVOICE: 'Factura C' } : {},
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
        editor.attributes = attributes;
        const primary = attributes.filter(a => !a.tags?.hidden || visibleHidden.has(a.id) || a.tags?.required || a.tags?.conditional_required);
        const optional = attributes.filter(a => !primary.includes(a));
        editor.root.querySelector('[data-meli-technical]').innerHTML = `<div class="meli-product-grid">${primary.map(a => attributeField(a, editor.draft.attributes, 'attribute')).join('')}</div>
            ${optional.length ? `<details><summary>Otros atributos de la categoría</summary><div class="meli-product-grid">${optional.map(a => attributeField(a, editor.draft.attributes, 'attribute')).join('')}</div></details>` : ''}
            <details><summary>Garantía, facturación y condiciones de venta</summary><div class="meli-product-grid">${terms.map(a => attributeField(a, editor.draft.sale_terms, 'term')).join('')}</div></details>`;
    }
    function collect(editor) {
        const draft = editor.draft;
        editor.root.querySelectorAll('[data-meli-field]').forEach(el => {
            draft[el.dataset.meliField] = el.type === 'checkbox' ? el.checked : el.value.trim();
        });
        draft.variant_id = Number(draft.variant_id || 0);
        ['attribute', 'term'].forEach(group => editor.root.querySelectorAll(`[data-meli-${group}]`).forEach(el => {
            draft[group === 'attribute' ? 'attributes' : 'sale_terms'][el.dataset[group === 'attribute' ? 'meliAttribute' : 'meliTerm']] = el.value.trim();
        }));
        draft.pictures = editor.root.querySelector('[data-meli-pictures]').value.split('\n').map(s => s.trim()).filter(Boolean);
        updatePricing(editor);
        return draft;
    }
    function priceInputs(editor) {
        const variant = editor.form.querySelector(`[data-variant-row][data-variant-id="${editor.draft.variant_id}"]`) || editor.form.querySelector('[data-variant-row]');
        const costs = {};
        editor.root.querySelectorAll('[data-meli-cost]').forEach(input => {
            costs[input.dataset.meliCost] = Number(input.value.replace(',', '.'));
        });
        return { base_price_cents: Math.round(Number(variant?.querySelector('.variant-price').value || 0) * 100),
            packaging_cents: Math.round(costs.packaging * 100), shipping_cents: Math.round(costs.shipping * 100),
            other_fixed_cents: Math.round(costs.other_fixed * 100), other_percentage: costs.other_percentage,
            billable_weight: costs.billable_weight, rounding_pesos: costs.rounding_pesos };
    }
    function priceContext(editor, inputs) {
        const draft = editor.draft;
        return JSON.stringify({ ...inputs, category_id: draft.category_id, catalog_product_id: draft.catalog_product_id,
            listing_type_id: draft.listing_type_id, shipping_mode: draft.shipping_mode, logistic_type: draft.logistic_type,
            free_shipping: draft.free_shipping, variant_id: draft.variant_id, package_confirmed: draft.package_confirmed,
            package: ['SELLER_PACKAGE_LENGTH', 'SELLER_PACKAGE_WIDTH', 'SELLER_PACKAGE_HEIGHT', 'SELLER_PACKAGE_WEIGHT'].map(id => draft.attributes[id] || '') });
    }
    const money = cents => (cents / 100).toLocaleString('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 2 });
    function updatePricing(editor) {
        const inputs = priceInputs(editor);
        const key = priceContext(editor, inputs);
        const old = editor.draft.pricing;
        const valid = old?.context_key === key && old?.price_cents > 0;
        editor.draft.pricing = inputs.base_price_cents > 0 ? { ...inputs, ...(valid ? old : {}), context_key: valid ? key : '' } : null;
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
        if (!draft.package_confirmed) missing.push('Medir y confirmar el paquete');
        if (!draft.pricing?.price_cents) missing.push('Calcular precio Meli con comisiones');
        const variant = editor.form.querySelector(`[data-variant-row][data-variant-id="${draft.variant_id}"]`) || editor.form.querySelector('[data-variant-row]');
        if (!variant || Number(variant.querySelector('.variant-price').value) <= 0) missing.push('Precio');
        if (!variant || Number(variant.querySelector('.variant-stock').value) <= 0) missing.push('Stock');
        editor.root.querySelector('[data-meli-review]').textContent = missing.length
            ? `Pendiente: ${missing.join(', ')}. Los campos condicionales dependen de la validación de Mercado Libre.`
            : 'Ficha completada para revisión. Falta validar las condiciones y el catálogo con Mercado Libre antes de publicar.';
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
            editor.root.querySelector('[data-meli-category-name]').textContent = data.category.path_from_root.map(c => c.name).join(' / ');
            renderAttributes(editor, data.attributes, data.sale_terms);
            const setOptions = (name, options) => {
                const el = editor.root.querySelector(`[data-meli-field="${name}"]`);
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
        const mode = editor.root.querySelector('[data-meli-field="shipping_mode"]').value;
        const options = (editor.logistics || []).filter(option => option.mode === mode).flatMap(option => option.types || []).filter(type => type.status === 'active');
        const select = editor.root.querySelector('[data-meli-field="logistic_type"]');
        const previous = select.value;
        select.innerHTML = '<option value="">Elegir</option>' + options.map(type => `<option value="${esc(type.type)}">${esc(({not_specified: 'A convenir', custom: 'Envío propio', drop_off: 'Despacho en sucursal', cross_docking: 'Colecta', fulfillment: 'Full', self_service: 'Flex'})[type.type] || type.type)}</option>`).join('');
        select.value = options.some(type => type.type === previous) ? previous : options.length === 1 ? options[0].type : '';
    }
    function mount(form, product) {
        if (!form) return;
        const draft = product?.meli ? structuredClone(product.meli) : preset(product);
        draft.attributes ||= {};
        draft.sale_terms ||= {};
        draft.logistic_type ||= ['custom', 'not_specified'].includes(draft.shipping_mode) ? draft.shipping_mode : '';
        const pricing = draft.pricing || {};
        const costField = (name, label, value, step = '0.01') => `<label>${esc(label)}<input data-meli-cost="${name}" type="number" min="0" step="${step}" value="${esc(value)}"></label>`;
        const packageWeight = String(draft.attributes.SELLER_PACKAGE_WEIGHT || '').trim().replace(',', '.').split(/\s+/);
        const packageGrams = Number(packageWeight[0]) * (packageWeight[1] === 'kg' ? 1000 : 1);
        const root = document.createElement('details');
        root.className = 'meli-product-editor';
        root.open = !!draft.category_id;
        root.innerHTML = `<summary>MERCADO LIBRE · FICHA DE PUBLICACIÓN</summary>
            <p>Guardá esta ficha con GUARDAR CAMBIOS. No se publica ni se modifica el precio o stock en Mercado Libre.</p>
            <div class="meli-product-grid">${field('category_id', 'Categoría de Mercado Libre', draft.category_id, 'placeholder="MLA…"')}
            ${field('catalog_product_id', 'Producto del catálogo · opcional', draft.catalog_product_id, 'placeholder="MLA…"')}
            ${field('family_name', 'Nombre de familia / título de publicación', draft.family_name, 'maxlength="120"')}
            ${select('variant_id', 'Variante · usa su precio, stock, SKU y código', draft.variant_id, (product?.variants || []).map(v => [v.id, v.name]))}
            ${select('condition', 'Condición', draft.condition, [['new', 'Nuevo'], ['used', 'Usado'], ['not_specified', 'Sin especificar']])}
            ${select('listing_type_id', 'Tipo de publicación', draft.listing_type_id, draft.listing_type_id ? [[draft.listing_type_id, draft.listing_type_id]] : [])}
            ${select('shipping_mode', 'Modalidad de envío', draft.shipping_mode, draft.shipping_mode ? [[draft.shipping_mode, draft.shipping_mode]] : [])}
            ${select('logistic_type', 'Logística para calcular comisiones', draft.logistic_type, draft.logistic_type ? [[draft.logistic_type, draft.logistic_type]] : [])}</div>
            <button class="primary-button fit-button" type="button" data-meli-load>CONSULTAR REQUISITOS</button>
            <p data-meli-category-name></p>
            <p>Moneda: ARS · Compra inmediata. El stock se toma de la variante seleccionada. Su precio es el neto objetivo que querés conservar al vender en Meli.</p>
            <h3>PRECIO MELI · COMISIONES Y GASTOS</h3>
            <div class="meli-product-grid">${costField('packaging', 'Embalaje por unidad · $', (pricing.packaging_cents || 0) / 100)}
            ${costField('shipping', 'Envío a tu cargo por unidad · $', (pricing.shipping_cents || 0) / 100)}
            ${costField('other_fixed', 'Otros gastos por unidad · $', (pricing.other_fixed_cents || 0) / 100)}
            ${costField('other_percentage', 'Otros gastos sobre la venta · %', pricing.other_percentage || 0)}
            ${costField('billable_weight', 'Peso facturable para Meli · gramos', pricing.billable_weight || packageGrams || '', '1')}
            <label>Redondear precio hacia arriba<select data-meli-cost="rounding_pesos">${[1,10,100].map(value => `<option value="${value}" ${Number(pricing.rounding_pesos || 100) === value ? 'selected' : ''}>Cada $${value}</option>`).join('')}</select></label></div>
            <p>El cargo fijo de Meli ya está incluido en su comisión total. Los gastos adicionales se cargan por separado; el envío ingresado no se cotiza automáticamente. Confirmá el peso facturable según tu logística.</p>
            <button class="primary-button fit-button" type="button" data-meli-price-calculate>CALCULAR PRECIO CON COMISIONES</button>
            <p data-meli-price-summary role="status"></p>
            <div class="meli-product-checks"><label><input type="checkbox" data-meli-field="local_pick_up" ${draft.local_pick_up ? 'checked' : ''}> Retiro en persona</label>
            <label><input type="checkbox" data-meli-field="free_shipping" ${draft.free_shipping ? 'checked' : ''}> Envío gratis a cargo del vendedor</label></div>
            <label>Descripción en texto plano<textarea data-meli-field="description" rows="4">${esc(draft.description)}</textarea></label>
            <label>Fotos · una URL HTTPS por línea<textarea data-meli-pictures rows="3">${esc((draft.pictures || []).join('\n'))}</textarea></label>
            <h3>FICHA TÉCNICA Y PAQUETE</h3><div data-meli-technical></div>
            <button class="primary-button fit-button" type="button" data-meli-calculate>CALCULAR PESO NETO DEL PAPEL</button>
            ${draft.category_id === 'MLA416632' && draft.attributes.SHEETS_NUMBER === '20' && draft.attributes.PAPER_SIZE === 'A4' && draft.attributes.GRAMMAGE === '200 g' ? `<p>Para A4 de 200 g/m² × 20 hojas: 0,21 × 0,297 × 200 × 20 = <strong>249,48 g de papel</strong>. Las hojas miden 21 × 29,7 cm. Un paquete contiene 20 hojas; no son 20 paquetes.</p>
            <p>Propuesta de envío para el producto de prueba: <strong>32 × 23 × 1 cm y 280 g</strong>, con 30,52 g de margen para embalaje. Son estimaciones; el espesor y el peso final requieren medición.</p>` : '<p>Ingresá las medidas y el peso del paquete completo, incluyendo el embalaje.</p>'}
            <label class="meli-product-checks"><input type="checkbox" data-meli-field="package_confirmed" ${draft.package_confirmed ? 'checked' : ''}> Medí el paquete completo y confirmé las medidas y el peso cargados</label>
            <p data-meli-review role="status"></p>
            <footer class="meli-progress-footer" role="status" aria-live="polite"><span data-meli-progress>Mercado Libre: borrador sin publicar.</span><span class="meli-progress-dots" aria-hidden="true"><i></i><i></i><i></i></span></footer>`;
        form.querySelector('.product-save-actions').before(root);
        const editor = { form, root, draft, sequence: 0, loadedCategory: draft.category_id, attributes: [],
            footer: root.querySelector('footer'), progress: root.querySelector('[data-meli-progress]') };
        editors.set(form, editor);
        renderAttributes(editor, draft.category_id === 'MLA416632' ? seedAttributes : [], []);
        root.addEventListener('input', event => {
            if (event.target.matches('[data-meli-attribute^="SELLER_PACKAGE_"], [data-meli-attribute="LENGTH"], [data-meli-attribute="WIDTH"], [data-meli-attribute="GRAMMAGE"], [data-meli-attribute="SHEETS_NUMBER"]')) {
                root.querySelector('[data-meli-field="package_confirmed"]').checked = false;
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
        root.querySelector('[data-meli-calculate]').addEventListener('click', () => calculatePaperWeight(editor));
        root.querySelector('[data-meli-price-calculate]').addEventListener('click', () => calculatePrice(editor));
        review(editor);
        if (draft.category_id) requirements(editor);
    }
    window.MeliProductEditor = { mount, read(form) {
        const editor = editors.get(form);
        if (!editor) return null;
        const draft = collect(editor);
        return draft.category_id ? draft : null;
    } };
})();
