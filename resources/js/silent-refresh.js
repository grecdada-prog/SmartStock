const interactiveSelector = [
    'input',
    'select',
    'textarea',
    'button',
    'a',
    '[contenteditable="true"]',
    '[role="button"]',
].join(',');

const isUserBusy = () => {
    const active = document.activeElement;

    if (active && active !== document.body && active.matches(interactiveSelector)) {
        return true;
    }

    if (window.getSelection?.().toString()) {
        return true;
    }

    return document.querySelector('[data-refresh-blocker]') !== null;
};

const prepareFilterForm = (form) => {
    let submitting = false;

    const submit = () => {
        if (submitting) {
            return;
        }

        submitting = true;
        form.setAttribute('aria-busy', 'true');
        form.dataset.filtering = 'true';
        form.classList.add('opacity-60');

        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    };

    form.querySelectorAll('input, select, textarea').forEach((field) => {
        if (field.tagName === 'SELECT' || field.type === 'date') {
            field.addEventListener('change', submit);
            return;
        }

        field.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' || event.isComposing) {
                return;
            }

            event.preventDefault();
            submit();
        });
    });
};

const initAutoFilters = (root = document) => {
    root.querySelectorAll('form[data-auto-filter]:not([data-auto-filter-ready])').forEach((form) => {
        form.dataset.autoFilterReady = 'true';
        prepareFilterForm(form);
    });
};

const refreshScope = async (scope, options = {}) => {
    if (!options.force && isUserBusy()) {
        return;
    }

    const selector = scope.id ? `#${CSS.escape(scope.id)}` : '[data-silent-refresh]';

    try {
        const response = await fetch(window.location.href, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'text/html',
            },
            cache: 'no-store',
        });

        if (!response.ok) {
            return;
        }

        const html = await response.text();
        const nextDocument = new DOMParser().parseFromString(html, 'text/html');
        const nextScope = nextDocument.querySelector(selector);

        if (!nextScope || scope.innerHTML === nextScope.innerHTML || (!options.force && isUserBusy())) {
            return;
        }

        const scrollX = window.scrollX;
        const scrollY = window.scrollY;
        scope.innerHTML = nextScope.innerHTML;
        window.Alpine?.initTree?.(scope);
        initAutoFilters(scope);
        window.SmartStoreBarcodeInputs?.init?.(scope);
        window.SmartStorePhoneInputs?.init?.(scope);
        window.SmartStoreEmailInputs?.init?.(scope);
        window.SmartStoreSubmitGuards?.init?.(scope);
        window.scrollTo(scrollX, scrollY);
    } catch (error) {
        // Keep polling silent; transient network/server errors should not disturb the user.
    }
};

const initSilentRefresh = (root = document) => {
    root.querySelectorAll('[data-silent-refresh]:not([data-silent-refresh-ready])').forEach((scope) => {
        scope.dataset.silentRefreshReady = 'true';
    });
};

document.addEventListener('DOMContentLoaded', () => {
    initAutoFilters();
    initSilentRefresh();

    window.addEventListener('smartstore:refresh-now', (event) => {
        document.querySelectorAll('[data-silent-refresh]').forEach((scope) => refreshScope(scope, {
            force: event.detail?.force === true,
        }));
    });
});
