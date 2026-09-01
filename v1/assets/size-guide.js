(() => {
    'use strict';

    const search = document.getElementById('size-guide-search');
    const empty = document.getElementById('size-guide-search-empty');
    const cards = Array.from(document.querySelectorAll('.size-guide-card'));

    if (!search || !empty || cards.length === 0) return;

    const normalize = value => String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase('es');

    search.addEventListener('input', () => {
        const query = normalize(search.value.trim());
        const terms = query.split(/\s+/).filter(Boolean);
        let visibleCards = 0;

        cards.forEach(card => {
            const group = normalize(card.querySelector('h2')?.textContent);
            const groupMatches = terms.every(term => group.includes(term));
            const rows = Array.from(card.querySelectorAll('tbody tr'));
            let visibleRows = 0;

            rows.forEach(row => {
                const rowText = `${group} talle ${normalize(row.textContent)}`;
                const matches = terms.length === 0 || groupMatches || terms.every(term => rowText.includes(term));
                row.hidden = !matches;
                if (matches) visibleRows += 1;
            });

            card.hidden = visibleRows === 0;
            if (!card.hidden) visibleCards += 1;
        });

        empty.hidden = query === '' || visibleCards > 0;
    });
})();
