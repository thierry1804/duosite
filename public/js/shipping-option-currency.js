/**
 * Modes d'expédition : préfixe tarif (maritime → USD, sinon MGA),
 * badge, limite 3 options, préremplissage description + délai.
 */
(function () {
    var MAX_OPTIONS = 3;

    /** Aligné sur App\Shipping\ShippingOptionChoices::defaultsByName() */
    var DEFAULTS = {
        'Aérien Express': {
            description: "Les départs sont effectués chaque lundi et jeudi matin. Après la validation de votre commande, la réception des articles à l'entrepôt peut prendre 2 à 7 jours, selon le fournisseur et sa province.\nLe délai de transport express (3 à 5 jours) commence à être compté à partir du jour du départ du vol.\nLe poids minimum facturé est de 250 g. Tout article de moins de 250 g sera donc facturé à 250 g.\nLes frais d'expédition sont calculés au kilo.",
            estimatedDeliveryDays: 7
        },
        'Aérien Standard': {
            description: "Le départ est effectué tout les vendredis matin. Après la validation de votre commande, la réception des articles à l'entrepôt peut prendre 2 à 7 jours, selon le fournisseur et sa province.\nLe délai de transport normal (10 à 15 jours) commence à être compté à partir du jour du départ du vol.\nLe poids minimum facturé est de 250 g. Tout article de moins de 250 g sera donc facturé à 250 g.\nLes frais d'expédition sont calculés au kilo.",
            estimatedDeliveryDays: 15
        },
        'Maritime': {
            description: "Il y a deux départs chaque semaine (les jours exacts peuvent varier selon le planning des navires). Après la validation de votre commande, la réception des articles à l'entrepôt peut prendre 2 à 7 jours, selon le fournisseur et sa province.\nLe délai de transport maritime est estimé entre 55 et 75 jours, à compter du départ du bateau.\nLes frais d'expédition sont calculés au CBM (mètre cube). Pour les volumes inférieurs à 0,25 CBM, le tarif appliqué est plus élevé que pour les volumes supérieurs à 0,25 CBM.",
            estimatedDeliveryDays: 45
        }
    };

    if (window.SHIPPING_OPTION_DEFAULTS && typeof window.SHIPPING_OPTION_DEFAULTS === 'object') {
        DEFAULTS = window.SHIPPING_OPTION_DEFAULTS;
    }

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
        if (!select.value) {
            badge.classList.add('d-none');
            return;
        }
        var b = badgeForMode(select.value);
        badge.textContent = b.text;
        badge.className = b.className;
        badge.classList.remove('d-none');
    }

    function fillDefaultsFromSelect(select) {
        var card = select.closest('.shipping-option-card');
        if (!card || !select.value) {
            return;
        }
        var defaults = DEFAULTS[select.value];
        if (!defaults) {
            return;
        }
        var desc = card.querySelector('.shipping-option-desc-input, textarea[id$="_description"], textarea[name$="[description]"]');
        var days = card.querySelector('input[id$="_estimatedDeliveryDays"], input[name$="[estimatedDeliveryDays]"]');
        if (desc) {
            desc.value = defaults.description || '';
        }
        if (days && defaults.estimatedDeliveryDays != null) {
            days.value = String(defaults.estimatedDeliveryDays);
        }
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

    function syncRow(select, options) {
        options = options || {};
        updatePricePrefixFromName(select);
        updateBadgeFromSelect(select);
        if (options.fillDefaults) {
            fillDefaultsFromSelect(select);
        }
    }

    document.addEventListener('change', function (e) {
        var t = e.target;
        if (!t.classList || !t.classList.contains('shipping-option-name-input')) {
            return;
        }
        syncRow(t, { fillDefaults: true });
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.shipping-option-name-input').forEach(function (select) {
            // Préremplir si mode déjà choisi mais champs vides (rechargement / prototype)
            var card = select.closest('.shipping-option-card');
            if (select.value && card) {
                var desc = card.querySelector('.shipping-option-desc-input, textarea[id$="_description"]');
                var days = card.querySelector('input[id$="_estimatedDeliveryDays"]');
                var needsFill = (desc && !desc.value.trim()) || (days && !days.value);
                syncRow(select, { fillDefaults: !!needsFill });
            } else {
                syncRow(select, { fillDefaults: false });
            }
        });
        refreshShippingAddButton();
    });

    window.refreshShippingAddButton = refreshShippingAddButton;
})();
