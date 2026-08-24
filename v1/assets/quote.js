(() => {
  const app = window.quoteApp || {};
  const $ = id => document.getElementById(id);
  const money = value => new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 0 }).format(value);
  const papers = app.papers || [];
  let selected = null;

  const returnToStore = () => {
    const storeUrl = new URL(app.storeUrl || '/', window.location.origin);
    storeUrl.searchParams.set('volver-del-cotizador', '1');
    window.location.assign(storeUrl.href);
  };

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

  const currentLayout = () => {
    if (!selected) return null;
    const size = paperSize(selected.product_name + ' ' + selected.variant_name);
    const width = +$('project-width').value;
    const height = +$('project-height').value;
    const margin = (+$('cut-margin').value || 0) / 10;
    if (!size || !(width > 0 && height > 0)) return { size, best: null };
    const layout = (pieceWidth, pieceHeight, rotated) => {
      const columns = Math.floor((size[0] + margin) / (pieceWidth + margin));
      const rows = Math.floor((size[1] + margin) / (pieceHeight + margin));
      return { columns, rows, count: columns * rows, pieceWidth, pieceHeight, rotated };
    };
    const normal = layout(width, height, false);
    const rotated = layout(height, width, true);
    return { size, margin, best: rotated.count > normal.count ? rotated : normal };
  };

  const renderPreview = () => {
    const preview = $('cut-preview');
    const project = currentLayout();
    if (!selected) {
      preview.innerHTML = '<div class="quote-step"><b>3</b><div><h2>Vista previa de cortes</h2><p>Seleccioná un papel para ver el esquema a escala.</p></div></div>';
      return;
    }
    if (!project?.size) {
      preview.innerHTML = '<div class="quote-step"><b>3</b><div><h2>Vista previa de cortes</h2><p>El papel seleccionado no indica una medida reconocible.</p></div></div>';
      return;
    }
    if (!project.best?.count) {
      preview.innerHTML = '<div class="quote-step"><b>3</b><div><h2>Vista previa de cortes</h2><p>La pieza no entra en el papel con estas medidas.</p></div></div>';
      return;
    }
    const { size, margin, best } = project;
    const pieces = Array.from({ length: best.columns * best.rows }, (_, index) => {
      const column = index % best.columns;
      const row = Math.floor(index / best.columns);
      return '<rect class="cut-piece" x="' + (column * (best.pieceWidth + margin)) + '" y="' + (row * (best.pieceHeight + margin)) + '" width="' + best.pieceWidth + '" height="' + best.pieceHeight + '"></rect>';
    }).join('');
    const verticalLines = Array.from({ length: best.columns - 1 }, (_, index) => {
      const position = ((index + 1) * (best.pieceWidth + margin)) - (margin / 2);
      return position < size[0] - 0.02 ? '<line class="cut-line" x1="' + position + '" y1="0" x2="' + position + '" y2="' + size[1] + '"></line>' : '';
    }).join('');
    const horizontalLines = Array.from({ length: best.rows - 1 }, (_, index) => {
      const position = ((index + 1) * (best.pieceHeight + margin)) - (margin / 2);
      return position < size[1] - 0.02 ? '<line class="cut-line" x1="0" y1="' + position + '" x2="' + size[0] + '" y2="' + position + '"></line>' : '';
    }).join('');
    const sheet = '<svg class="live-cut-sheet" width="' + (size[0] * 10) + '" height="' + (size[1] * 10) + '" viewBox="0 0 ' + size[0] + ' ' + size[1] + '" role="img" aria-label="Esquema de cortes sobre papel de ' + size[0] + ' por ' + size[1] + ' centímetros"><rect class="cut-paper" x="0" y="0" width="' + size[0] + '" height="' + size[1] + '"></rect>' + pieces + verticalLines + horizontalLines + '</svg>';
    preview.innerHTML = '<div class="quote-step"><b>3</b><div><h2>Vista previa de cortes</h2><p>Las franjas entre las piezas representan el margen de corte.</p></div></div><div class="live-cut-layout">' + sheet + '<div class="live-cut-copy"><strong>' + selected.product_name + '</strong><span>Papel ' + size[0] + ' × ' + size[1] + ' cm</span><span>Pieza ' + best.pieceWidth + ' × ' + best.pieceHeight + ' cm</span><span>Margen de corte: ' + (margin * 10) + ' mm</span><span>' + best.columns + ' columnas × ' + best.rows + ' filas · ' + best.count + ' piezas por hoja</span>' + (best.rotated ? '<small>La pieza se giró para aprovechar mejor el papel.</small>' : '') + '</div></div>';
  };

  const calculate = () => {
    if (!selected) {
      $('quote-result').innerHTML = '<p>Elegí un papel para ver tu cotización.</p>';
      return;
    }
    const project = currentLayout();
    if (!project?.size) {
      $('quote-result').innerHTML = '<p>Este papel no tiene un tamaño identificable. Elegí una variante que indique A4, A3, A5 o medidas.</p>';
      return;
    }
    if (!project.best?.count) {
      $('quote-result').innerHTML = '<p>La pieza no entra en la hoja seleccionada. Probá otro papel o tamaño.</p>';
      return;
    }
    const quantity = +$('project-quantity').value;
    if (!(quantity > 0)) return;
    const { size, best: bestLayout } = project;
    const perSheet = bestLayout.count;
    const sheets = Math.ceil(quantity / perSheet);
    const cutLeftovers = sheets * perSheet - quantity;
    const perPack = packSheets(selected.product_name + ' ' + selected.variant_name);
    const packs = Math.ceil(sheets / perPack);
    const sheetLeftovers = packs * perPack - sheets;
    const paperCost = (+selected.price_cents / 100) * packs;
    const sheetPrice = (+selected.price_cents / 100) / perPack;
    $('quote-result').innerHTML = '<h2 id="quote-modal-title">Costo del papel</h2><div class="result-heading"><span>Total para comprar el papel</span><strong>' + money(paperCost) + '</strong><small>' + money(paperCost / quantity) + ' de papel por unidad</small></div><div class="result-grid"><div><b>' + sheets + '</b><span>hojas a usar</span></div><div><b>' + sheetLeftovers + '</b><span>hojas que sobran</span></div><div><b>' + money(sheetPrice) + '</b><span>precio por hoja</span></div><div><b>' + perSheet + '</b><span>piezas por hoja</span></div></div><p>Con ' + selected.product_name + '. Necesitás ' + packs + ' resma(s) de ' + perPack + ' hoja(s) y quedan ' + cutLeftovers + ' espacios de corte libres en la última hoja.</p>';
  };

  const choosePaper = row => {
    selected = papers.find(paper => Number(paper.variant_id) === Number(row.dataset.id)) || {
      variant_id: Number(row.dataset.id),
      product_name: row.dataset.product,
      variant_name: row.dataset.variant,
      price_cents: Number(row.dataset.price)
    };
    if (!selected) return;
    $('paper-selected').textContent = 'Papel elegido: ' + selected.product_name + ' · ' + money(selected.price_cents / 100);
    $('paper-selected').hidden = false;
    $('paper-picker').open = false;
    document.querySelectorAll('.paper-table tbody tr').forEach(paperRow => paperRow.classList.toggle('chosen', paperRow === row));
    renderPreview();
  };

  $('paper-list').addEventListener('click', event => {
    const row = event.target.closest('tr[data-id]');
    if (!row) return;
    event.preventDefault();
    choosePaper(row);
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      event.preventDefault();
      if ($('quote-modal').open) {
        $('quote-modal').close();
        return;
      }
      returnToStore();
    }
  });
  $('calculate-quote').addEventListener('click', () => {
    calculate();
    $('quote-modal').showModal();
  });
  $('close-quote').addEventListener('click', () => $('quote-modal').close());
  $('quote-back').addEventListener('click', event => {
    event.preventDefault();
    returnToStore();
  });
  ['project-width', 'project-height', 'cut-margin'].forEach(id => $(id).addEventListener('input', renderPreview));
  renderPreview();
})();
