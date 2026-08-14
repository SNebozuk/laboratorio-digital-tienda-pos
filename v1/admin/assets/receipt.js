function returnToPosMenu() {
    if (window.opener && !window.opener.closed) {
        window.opener.focus();
    }
    window.close();
    window.setTimeout(() => {
        if (!window.closed && window.history.length > 1) {
            window.history.back();
        }
    }, 100);
}

window.addEventListener('load', () => {
    window.print();
});

window.addEventListener('afterprint', returnToPosMenu);

window.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
        event.preventDefault();
        returnToPosMenu();
    }
});
