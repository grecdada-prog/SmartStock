const POS_STORAGE_PREFIXES = [
    'smartstore:pos-cart:',
    'smartstore:pos-sale-token:',
];

function clearPosStorage() {
    try {
        Object.keys(window.localStorage || {}).forEach((key) => {
            if (POS_STORAGE_PREFIXES.some((prefix) => key.startsWith(prefix))) {
                window.localStorage.removeItem(key);
            }
        });
    } catch (error) {
        // localStorage can be unavailable in private or restricted browser contexts.
    }
}

function isLogoutUrl(url) {
    if (!url) {
        return false;
    }

    try {
        return new URL(url, window.location.origin).pathname === '/logout';
    } catch (error) {
        return false;
    }
}

document.addEventListener('click', (event) => {
    const logoutLink = event.target.closest?.('a[href]');

    if (logoutLink && isLogoutUrl(logoutLink.getAttribute('href'))) {
        clearPosStorage();
    }
}, true);

document.addEventListener('submit', (event) => {
    if (isLogoutUrl(event.target?.getAttribute?.('action'))) {
        clearPosStorage();
    }
}, true);

window.SmartStorePosStorage = {
    clear: clearPosStorage,
};
