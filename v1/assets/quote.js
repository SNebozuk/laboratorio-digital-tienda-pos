(() => {
  const app = window.quoteApp || {};
  const $ = id => document.getElementById(id);
  const money = value => new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 0 }).format(value);
  const papers = app.papers || [];
  const settings = (app.catalog || {}).quote_settings || {};
  let selected = null;

  const fold = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
  const paperSize = label => {
    const match = String(label).match(/(\d+(?:[.,]\d+)?)\s*[x×]\s*(\d+(?:[.,]\d+)?)/i);
    if (match) return [+match[1].replace(',', '.'), +match[2].replace(',', '.')];
    const name = fold(label);
    if (name.includes('a3+')) return [32.9, 48.3];
    if (name.includes('a4')) return [21, 29.7];
    if (name.includes('a3')) return [29.7, 42];
    if (name.includes('a6') || name.includes('4r')) return [10, 15];
    if (name.includes('a5')) return [14.8, 21];
    return null;
  };
  const packSheets = label => +(String(label).match(/(\d+)\s*hojas?/i)?.[1] || 1);

  const calculate = () => {
    if (!selected) {
      $('quote-result').innerHTML = '<p>Elegí un papel para ver tu cotización.</p>';
      return;
    }
    const size = paperSize(selected.product_name + ' ' + selected.variant_name);
    if (!size) {
      $('quote-result').innerHTML = '<p>Este papel no tiene un tamaño identificable. Elegí una variante que indique A4, A3, A5 o medidas.</p>';
      return;
    }
    const quantity = +$('project-quantity').value;
    const width = +$('project-width').value;
    const height = +$('project-height').value;
    const margin = (+$('cut-margin').value || 0) / 10;
    if (!(quantity > 0 && width > 0 && height > 0)) return;
    const fit = (sheetWidth, sheetHeight) => Math.floor(sheetWidth / (width + margin)) * Math.floor(sheetHeight / (height + margin));
    const perSheet = Math.max(fit(size[0], size[1]), fit(size[1], size[0]));
    if (!perSheet) {
      $('quote-result').innerHTML = '<p>La pieza no entra en la hoja seleccionada. Probá otro papel o tamaño.</p>';
      return;
    }
    const sheets = Math.ceil(quantity / perSheet);
    const cutLeftovers = sheets * perSheet - quantity;
    const perPack = packSheets(selected.product_name + ' ' + selected.variant_name);
    const packs = Math.ceil(sheets / perPack);
    const sheetLeftovers = packs * perPack - sheets;
    const paperCost = (+selected.price_cents / 100) * packs;
    const sheetPrice = (+selected.price_cents / 100) / perPack;
    const inkType = $('ink-type').value;
    const coverage = +$('ink-coverage').value;
    const sizeRatio = (size[0] * size[1]) / (21 * 29.7);
    const inkCost = (+settings[inkType + '_cost'] / +settings[inkType + '_yield']) * sheets * sizeRatio * coverage;
    const total = paperCost + inkCost;
    const marginPct = +$('profit-margin').value || 0;
    const price = total * (1 + marginPct / 100);
    $('quote-result').innerHTML = '<h2 id="quote-modal-title">Tu cotización</h2><div class="result-heading"><span>Precio sugerido</span><strong>' + money(price) + '</strong><small>para el trabajo completo</small></div><div class="result-grid"><div><b>' + sheets + '</b><span>hojas a usar</span></div><div><b>' + sheetLeftovers + '</b><span>hojas que sobran</span></div><div><b>' + money(sheetPrice) + '</b><span>precio por hoja</span></div><div><b>' + money(total / quantity) + '</b><span>costo por unidad</span></div></div><div class="result-costs"><span>Papel <b>' + money(paperCost) + '</b></span><span>Tinta estimada <b>' + money(inkCost) + '</b></span><span>Ganancia estimada <b>' + money(price - total) + '</b></span></div><p>Con ' + selected.product_name + (selected.variant_name ? ' · ' + selected.variant_name : '') + '. Rinde ' + perSheet + ' unidades por hoja; necesitás ' + packs + ' resma(s) de ' + perPack + ' hoja(s) y sobran ' + cutLeftovers + ' unidades posibles del recorte.</p>';
  };

  const choosePaper = variantId => {
    selected = papers.find(paper => Number(paper.variant_id) === Number(variantId));
    if (!selected) return;
    $('paper-selected').textContent = 'Papel elegido: ' + selected.product_name + (selected.variant_name ? ' · ' + selected.variant_name : '') + ' · ' + money(selected.price_cents / 100);
    $('paper-selected').hidden = false;
    $('paper-picker').open = false;
  };

  $('paper-list').addEventListener('change', event => {
    if (event.target.name === 'quote-paper') choosePaper(event.target.value);
  });
  $('paper-list').addEventListener('click', event => {
    const row = event.target.closest('tr[data-id]');
    if (!row) return;
    const input = row.querySelector('input[name="quote-paper"]');
    if (input) input.checked = true;
    choosePaper(row.dataset.id);
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') $('paper-picker').open = false;
  });
  $('calculate-quote').addEventListener('click', () => {
    calculate();
    $('quote-modal').showModal();
  });
  $('close-quote').addEventListener('click', () => $('quote-modal').close());
  $('profit-margin').value = settings.recommended_margin || 50;
})();
