import './bootstrap';
import './notifications';
import './silent-refresh';
import './modal-links';
import './inactivity-logout';

const BARCODE_DIGIT_LIMIT = 13;
const PHONE_DIGIT_LIMIT = 15;

function formatStockBarcode(value) {
    const digits = String(value || '').replace(/\D/g, '').slice(0, BARCODE_DIGIT_LIMIT);
    const groups = [
        digits.slice(0, 1),
        digits.slice(1, 5),
        digits.slice(5, 7),
        digits.slice(7, 11),
        digits.slice(11, 13),
    ].filter(Boolean);

    return groups.join(' ');
}

function initBarcodeInputs(root = document) {
    root.querySelectorAll('[data-barcode-format]:not([data-barcode-ready])').forEach((input) => {
        input.dataset.barcodeReady = 'true';
        input.setAttribute('maxlength', '17');
        input.setAttribute('autocomplete', 'off');

        const syncValue = () => {
            input.value = formatStockBarcode(input.value);
        };

        input.addEventListener('beforeinput', (event) => {
            if (!event.data || !/\d/.test(event.data)) {
                return;
            }

            if (event.inputType === 'insertFromPaste') {
                return;
            }

            const selectedDigits = input.value
                .slice(input.selectionStart || 0, input.selectionEnd || 0)
                .replace(/\D/g, '').length;
            const currentDigits = input.value.replace(/\D/g, '').length;
            const incomingDigits = event.data.replace(/\D/g, '').length;

            if (currentDigits - selectedDigits + incomingDigits > BARCODE_DIGIT_LIMIT) {
                event.preventDefault();
            }
        });

        input.addEventListener('input', syncValue);
        input.addEventListener('paste', () => window.setTimeout(syncValue, 0));
        syncValue();
    });
}

function formatPhoneNumber(value) {
    const digits = String(value || '').replace(/\D/g, '').slice(0, PHONE_DIGIT_LIMIT);
    const groups = [];

    for (let index = 0; index < digits.length; index += 3) {
        groups.push(digits.slice(index, index + 3));
    }

    return groups.join(' ');
}

function initPhoneInputs(root = document) {
    root.querySelectorAll('input[name="phone"]:not([data-phone-ready]), input[name="customer_phone"]:not([data-phone-ready]), input[type="tel"]:not([data-phone-ready]), [data-phone-format]:not([data-phone-ready])').forEach((input) => {
        input.dataset.phoneReady = 'true';
        input.setAttribute('maxlength', '19');
        input.setAttribute('inputmode', 'tel');
        input.setAttribute('autocomplete', input.getAttribute('autocomplete') || 'tel');
        input.setAttribute('pattern', '[0-9 ]{11,19}');
        input.setAttribute('title', 'Saisissez 9 a 15 chiffres.');
        input.removeAttribute('oninput');

        const syncValue = () => {
            input.value = formatPhoneNumber(input.value);
            input.dispatchEvent(new CustomEvent('smartstore:phone-formatted', { bubbles: true }));
        };

        input.addEventListener('beforeinput', (event) => {
            if (!event.data || !/\d/.test(event.data) || event.inputType === 'insertFromPaste') {
                return;
            }

            const selectedDigits = input.value
                .slice(input.selectionStart || 0, input.selectionEnd || 0)
                .replace(/\D/g, '').length;
            const currentDigits = input.value.replace(/\D/g, '').length;
            const incomingDigits = event.data.replace(/\D/g, '').length;

            if (currentDigits - selectedDigits + incomingDigits > PHONE_DIGIT_LIMIT) {
                event.preventDefault();
            }
        });

        input.addEventListener('input', syncValue);
        input.addEventListener('paste', () => window.setTimeout(syncValue, 0));
        syncValue();
    });
}

function initEmailInputs(root = document) {
    root.querySelectorAll('input[type="email"]:not([data-email-ready])').forEach((input) => {
        input.dataset.emailReady = 'true';
        input.setAttribute('maxlength', '255');
        input.setAttribute('autocomplete', input.getAttribute('autocomplete') || 'email');
        input.setAttribute('pattern', '(?!.*\\.\\.)[A-Za-z0-9](?:[A-Za-z0-9._%+\\-]{0,62}[A-Za-z0-9])?@[A-Za-z0-9](?:[A-Za-z0-9\\-]{0,61}[A-Za-z0-9])?(?:\\.[A-Za-z0-9](?:[A-Za-z0-9\\-]{0,61}[A-Za-z0-9])?)+');

        const normalize = () => {
            input.value = input.value.trim().toLowerCase();
        };

        input.addEventListener('change', normalize);
        input.addEventListener('blur', normalize);
    });
}

// Auto-dismiss notifications après 5 secondes
document.addEventListener('DOMContentLoaded', function() {
    const notifications = document.querySelectorAll('[data-auto-dismiss]');
    
    notifications.forEach(notification => {
        setTimeout(() => {
            notification.style.transition = 'opacity 0.5s ease-out';
            notification.style.opacity = '0';
            
            setTimeout(() => {
                notification.remove();
            }, 500);
        }, 5000); // 5 secondes
    });

    initBarcodeInputs();
    initPhoneInputs();
    initEmailInputs();
});

window.SmartStoreBarcodeInputs = {
    init: initBarcodeInputs,
    format: formatStockBarcode,
};

window.SmartStorePhoneInputs = {
    init: initPhoneInputs,
    format: formatPhoneNumber,
};

window.SmartStoreEmailInputs = {
    init: initEmailInputs,
};
