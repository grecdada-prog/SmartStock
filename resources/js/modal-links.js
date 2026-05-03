const modalPathMarkers = ['/edit', '/receipt', '/invoice', '/facture', '/recu'];

function isEligiblePath(pathname) {
    return modalPathMarkers.some((marker) => pathname.toLowerCase().includes(marker));
}

function buildModalUrl(url) {
    const modalUrl = new URL(url, window.location.origin);
    modalUrl.searchParams.set('modal', '1');

    return modalUrl.toString();
}

function createModal() {
    const wrapper = document.createElement('div');
    wrapper.id = 'smartstock-link-modal';
    wrapper.setAttribute('aria-hidden', 'true');
    wrapper.innerHTML = `
        <div class="smartstock-link-modal__backdrop" data-smartstock-modal-close></div>
        <div class="smartstock-link-modal__panel" role="dialog" aria-modal="true" aria-labelledby="smartstock-link-modal-title">
            <div class="smartstock-link-modal__header">
                <h2 id="smartstock-link-modal-title">Modification</h2>
                <button type="button" class="smartstock-link-modal__close" data-smartstock-modal-close aria-label="Fermer">&times;</button>
            </div>
            <iframe class="smartstock-link-modal__frame" title="Contenu SmartStock"></iframe>
        </div>
    `;

    const style = document.createElement('style');
    style.textContent = `
        #smartstock-link-modal {
            position: fixed;
            inset: 0;
            z-index: 80;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        #smartstock-link-modal.is-open {
            display: flex;
        }
        .smartstock-link-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, .48);
        }
        .smartstock-link-modal__panel {
            position: relative;
            display: flex;
            width: min(920px, 100%);
            max-height: min(86vh, 860px);
            min-height: 560px;
            flex-direction: column;
            overflow: hidden;
            border-radius: .75rem;
            background: #f8fafc;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .25);
        }
        .smartstock-link-modal__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            border-bottom: 1px solid #d9e8df;
            background: #eef8f1;
            padding: .85rem 1rem;
        }
        .smartstock-link-modal__header h2 {
            margin: 0;
            color: #163725;
            font-size: 1rem;
            font-weight: 700;
        }
        .smartstock-link-modal__close {
            display: inline-flex;
            height: 2rem;
            width: 2rem;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: .375rem;
            background: transparent;
            color: #334155;
            cursor: pointer;
            font-size: 1.5rem;
            line-height: 1;
        }
        .smartstock-link-modal__close:hover {
            background: rgba(22, 101, 52, .1);
        }
        .smartstock-link-modal__frame {
            width: 100%;
            flex: 1;
            border: 0;
            background: #f8fafc;
        }
        @media (max-width: 640px) {
            #smartstock-link-modal {
                padding: .5rem;
            }
            .smartstock-link-modal__panel {
                max-height: 94vh;
                min-height: 72vh;
            }
        }
    `;

    document.head.appendChild(style);
    document.body.appendChild(wrapper);

    return wrapper;
}

function modalTitleFor(url) {
    const path = url.pathname.toLowerCase();

    if (path.includes('/receipt') || path.includes('/invoice') || path.includes('/facture') || path.includes('/recu')) {
        return 'Facture';
    }

    return 'Modification';
}

function initModalLinks() {
    const modal = document.getElementById('smartstock-link-modal') || createModal();
    const title = modal.querySelector('#smartstock-link-modal-title');
    const frame = modal.querySelector('iframe');
    let expectedModalPath = '';
    let initialLoadDone = false;

    const close = (shouldRefresh = false) => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        frame.removeAttribute('src');
        expectedModalPath = '';
        initialLoadDone = false;

        if (shouldRefresh) {
            window.dispatchEvent(new CustomEvent('smartstock:refresh-now', { detail: { force: true } }));
            window.location.reload();
        }
    };

    const open = (href, customTitle = null, force = false) => {
        const url = new URL(href, window.location.origin);

        if (url.origin !== window.location.origin || (!force && !isEligiblePath(url.pathname))) {
            window.location.assign(url.toString());
            return;
        }

        expectedModalPath = url.pathname;
        initialLoadDone = false;
        title.textContent = customTitle || modalTitleFor(url);
        frame.src = buildModalUrl(url);
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        modal.querySelector('[data-smartstock-modal-close]').focus();
    };

    modal.querySelectorAll('[data-smartstock-modal-close]').forEach((button) => {
        button.addEventListener('click', () => close(false));
    });

    frame.addEventListener('load', () => {
        let currentPath = '';

        try {
            currentPath = frame.contentWindow.location.pathname;
        } catch (error) {
            return;
        }

        if (!initialLoadDone) {
            initialLoadDone = true;
            return;
        }

        if (expectedModalPath && currentPath !== expectedModalPath && !isEligiblePath(currentPath)) {
            close(true);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            close(false);
        }
    });

    document.addEventListener('click', (event) => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        const link = event.target.closest('a[href]');

        if (!link || link.target === '_blank' || link.hasAttribute('download')) {
            return;
        }

        const url = new URL(link.href, window.location.origin);

        if (url.origin === window.location.origin && (isEligiblePath(url.pathname) || link.dataset.smartstockModal === 'true')) {
            event.preventDefault();
            open(url.toString(), link.dataset.modalTitle || null, link.dataset.smartstockModal === 'true');
        }
    });

    window.SmartStockModalLinks = { open, close };

    window.addEventListener('smartstock:open-modal-url', (event) => {
        if (event.detail?.url) {
            open(event.detail.url, event.detail.title || null, Boolean(event.detail.force));
        }
    });
}

document.addEventListener('DOMContentLoaded', initModalLinks);
