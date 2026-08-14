let returningToPos = false;

function returnToPosMenu() {
    if (returningToPos) {
        return;
    }
    returningToPos = true;

    if (window.opener && !window.opener.closed) {
        window.opener.focus();
    }

    window.close();

    window.setTimeout(() => {
        if (!window.closed) {
            window.location.replace('pos.php');
        }
    }, 150);
}

window.addEventListener('load', () => {
    window.print();
});

window.addEventListener('afterprint', () => {
    window.setTimeout(returnToPosMenu, 0);
});

window.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
        event.preventDefault();
        returnToPosMenu();
    }
});
