/**
 * Modes d'expédition : préfixe tarif (maritime → USD, sinon MGA) + badge + limite 3 options.
 */
(function () {
    var MAX_OPTIONS = 3;

    function badgeForMode(value) {
        var v = (value || '').toLowerCase();
        if (v.indexOf('express') !== -1) {
            return { text: 'Express', className: 'shipping-option-badge shipping-option-badge--express' };
        }
        if (v.indexOf('standard') !== -1 || v.indexOf('normal') !== -1) {
            return { text: 'Standard', className: 'shipping-option-badge shipping-option-badge--standard' };
        }
        if (v.indexOf('maritime') !== -1) {
            return { text: 'Maritime', className: 'shipping-option-badge shipping-option-badge--neutral' };
        }
        return { text: '—', className: 'shipping-option-badge shipping-option-badge--neutral' };
    }

    function updatePricePrefixFromName(nameInput) {
        var card = nameInput.closest('.shipping-option-card');
        if (!card) {
            return;
        }
        var prefix = card.querySelector('.shipping-option-price-prefix');
        if (!prefix) {
            return;
        }
        var isMaritime = (nameInput.value || '').toLowerCase().indexOf('maritime') !== -1;
        prefix.textContent = isMaritime ? 'USD' : 'MGA';
    }

    function updateBadgeFromSelect(select) {
        var card = select.closest('.shipping-option-card');
        if (!card) {
            return;
        }
        var badge = card.querySelector('[data-shipping-badge]');
        if (!badge) {
            return;
        }
        var b = badgeForMode(select.value);
        badge.textContent = b.text;
        badge.className = b.className;
    }

    function refreshShippingAddButton() {
        var container = document.querySelector('.shipping-options-container');
        var btn = document.getElementById('add-shipping-option');
        if (!container || !btn) {
            return;
        }
        var n = container.querySelectorAll('.shipping-option-item').length;
        var hide = n >= MAX_OPTIONS;
        btn.style.display = hide ? 'none' : '';
        btn.setAttribute('aria-disabled', hide ? 'true' : 'false');
        btn.toggleAttribute('disabled', hide);
    }

    function syncRow(select) {
        updatePricePrefixFromName(select);
        updateBadgeFromSelect(select);
    }

    document.addEventListener('change', function (e) {
        var t = e.target;
        if (!t.classList || !t.classList.contains('shipping-option-name-input')) {
            return;
        }
        syncRow(t);
    });

    document.addEventListener('input', function (e) {
        var t = e.target;
        if (!t.classList || !t.classList.contains('shipping-option-name-input')) {
            return;
        }
        syncRow(t);
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.shipping-option-name-input').forEach(syncRow);
        refreshShippingAddButton();
    });

    window.refreshShippingAddButton = refreshShippingAddButton;
})();
