(() => {
    'use strict';

    const canonical = new Map([
        ['blanca', 'blanco'], ['blancas', 'blanco'], ['blancos', 'blanco'],
        ['negra', 'negro'], ['negras', 'negro'], ['negros', 'negro'],
        ['roja', 'rojo'], ['rojas', 'rojo'], ['rojos', 'rojo'],
        ['amarilla', 'amarillo'], ['amarillas', 'amarillo'], ['amarillos', 'amarillo'],
        ['azules', 'azul'], ['verdes', 'verde'], ['grises', 'gris'], ['blancas', 'blanco'],
        ['colores', 'color'], ['talles', 'talle'], ['papeles', 'papel'],
    ]);

    function normalizeWord(word) {
        const match = word.match(/^([a-z]+)([^a-z]*)$/);
        if (!match) return word;
        const [, letters, suffix] = match;
        if (canonical.has(letters)) return canonical.get(letters) + suffix;
        return (letters.endsWith('s') && letters.length > 3 ? letters.slice(0, -1) : letters) + suffix;
    }

    window.LDSearch = window.LDSearch || {};
    window.LDSearch.normalizeQuery = value => String(value || '')
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase()
        .trim().replace(/\s+/g, ' ')
        .split(' ').map(normalizeWord).join(' ');
})();
