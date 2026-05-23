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
    wrapper.id = 'smartstore-link-modal';
    wrapper.setAttribute('aria-hidden', 'true');
    wrapper.innerHTML = `
        <div class="smartstore-link-modal__backdrop" data-smartstore-modal-close></div>
        <div class="smartstore-link-modal__panel" role="dialog" aria-modal="true" aria-labelledby="smartstore-link-modal-title">
            <div class="smartstore-link-modal__header">
                <h2 id="smartstore-link-modal-title">Modification</h2>
                <button type="button" class="smartstore-link-modal__close" data-smartstore-modal-close aria-label="Fermer">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <iframe class="smartstore-link-modal__frame" title="Contenu SmartStore"></iframe>
        </div>
    `;

    const style = document.createElement('style');
    style.textContent = `
        #smartstore-link-modal {
            position: fixed;
            inset: 0;
            z-index: 80;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        #smartstore-link-modal.is-open {
            display: flex;
        }
        .smartstore-link-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, .50);
        }
        .smartstore-link-modal__panel {
            position: relative;
            display: flex;
            width: min(768px, 100%);
            max-height: calc(100vh - 2rem);
            flex-direction: column;
            overflow: hidden;
            border-radius: .5rem;
            background: #ffffff;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .24);
        }
        .smartstore-link-modal__header {
            display: flex;
            flex: 0 0 auto;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            border-bottom: 1px solid #e5e7eb;
            background: #ffffff;
            padding: 1rem 1.25rem;
        }
        .smartstore-link-modal__header h2 {
            margin: 0;
            color: #111827;
            font-size: 1.125rem;
            font-weight: 700;
        }
        .smartstore-link-modal__close {
            display: inline-flex;
            height: 2.25rem;
            width: 2.25rem;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: .375rem;
            background: transparent;
            color: #6b7280;
            cursor: pointer;
            font-size: 1.75rem;
            line-height: 1;
        }
        .smartstore-link-modal__close:hover {
            background: #f3f4f6;
            color: #374151;
        }
        .smartstore-link-modal__frame {
            width: 100%;
            min-height: 0;
            height: calc(100vh - 7rem);
            flex: 1 1 auto;
            border: 0;
            background: #ffffff;
        }
        @media (max-width: 640px) {
            #smartstore-link-modal {
                padding: .5rem;
            }
            .smartstore-link-modal__panel {
                max-height: calc(100vh - 1rem);
            }
            .smartstore-link-modal__header {
                padding: .875rem 1rem;
            }
            .smartstore-link-modal__frame {
                height: calc(100vh - 5.75rem);
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

    if (path.includes('/edit')) {
        return 'Modifier le produit';
    }

    return 'Modification';
}

function initModalLinks() {
    const modal = document.getElementById('smartstore-link-modal') || createModal();
    const title = modal.querySelector('#smartstore-link-modal-title');
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
            window.dispatchEvent(new CustomEvent('smartstore:refresh-now', { detail: { force: true } }));
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
        modal.querySelector('[data-smartstore-modal-close]').focus();
    };

    modal.querySelectorAll('[data-smartstore-modal-close]').forEach((button) => {
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

        if (url.origin === window.location.origin && (isEligiblePath(url.pathname) || link.dataset.smartstoreModal === 'true')) {
            event.preventDefault();
            open(url.toString(), link.dataset.modalTitle || null, link.dataset.smartstoreModal === 'true');
        }
    });

    window.SmartStoreModalLinks = { open, close };

    window.addEventListener('smartstore:open-modal-url', (event) => {
        if (event.detail?.url) {
            open(event.detail.url, event.detail.title || null, Boolean(event.detail.force));
        }
    });
}

document.addEventListener('DOMContentLoaded', initModalLinks);
