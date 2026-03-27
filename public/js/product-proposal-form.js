/**
 * Mise en page des propositions produit (groupes RMB / g, sous-titre, glisser-déposer).
 */
(function () {
    function parseProductTypeFromOptionText(text) {
        if (!text || !text.trim()) {
            return '';
        }
        var m = text.trim().match(/^#\d+\s*-\s*(.+?)\s*-\s+/);
        return m ? m[1].trim() : '';
    }

    function enhancePriceAndWeight(root) {
        root = root || document;
        root.querySelectorAll('.product-proposal-field-price').forEach(function (wrap) {
            if (wrap.querySelector('.input-group')) {
                return;
            }
            var input = wrap.querySelector('input.form-control, input[type="text"], input');
            if (!input || input.closest('.input-group')) {
                return;
            }
            var ig = document.createElement('div');
            ig.className = 'input-group';
            var pre = document.createElement('span');
            pre.className = 'input-group-text';
            pre.textContent = 'RMB';
            var parent = input.parentNode;
            parent.insertBefore(ig, input);
            ig.appendChild(pre);
            ig.appendChild(input);
        });
        root.querySelectorAll('.product-proposal-field-weight').forEach(function (wrap) {
            if (wrap.querySelector('.input-group')) {
                return;
            }
            var input = wrap.querySelector('input.form-control, input');
            if (!input || input.closest('.input-group')) {
                return;
            }
            var ig = document.createElement('div');
            ig.className = 'input-group';
            var suf = document.createElement('span');
            suf.className = 'input-group-text';
            suf.textContent = 'g';
            var parent = input.parentNode;
            parent.insertBefore(ig, input);
            ig.appendChild(input);
            ig.appendChild(suf);
        });
    }

    function syncSubtitle(selectEl) {
        var item = selectEl.closest('.product-proposal-item');
        if (!item) {
            return;
        }
        var sub = item.querySelector('.product-proposal-subtitle');
        if (!sub) {
            return;
        }
        var opt = selectEl.options[selectEl.selectedIndex];
        if (!opt || !opt.value) {
            sub.textContent = '';
            return;
        }
        sub.textContent = parseProductTypeFromOptionText(opt.textContent);
    }

    function bindQuoteItemSubtitles(root) {
        root = root || document;
        root.querySelectorAll('select.product-proposal-quote-item, select[id$="_quoteItem"]').forEach(function (sel) {
            syncSubtitle(sel);
        });
    }

    if (!window.__productProposalFormDelegated) {
        window.__productProposalFormDelegated = true;
        document.addEventListener('change', function (e) {
            var t = e.target;
            if (t && (t.matches && (t.matches('select.product-proposal-quote-item') || t.matches('select[id$="_quoteItem"]')))) {
                syncSubtitle(t);
            }
        });
        document.addEventListener(
            'dragover',
            function (e) {
                var dz = e.target.closest && e.target.closest('.product-proposal-dropzone');
                if (dz) {
                    e.preventDefault();
                }
            },
            false
        );
        document.addEventListener(
            'drop',
            function (e) {
                var dz = e.target.closest && e.target.closest('.product-proposal-dropzone');
                if (!dz) {
                    return;
                }
                e.preventDefault();
                var input = dz.querySelector('.image-upload-input, input[type="file"]');
                if (!input || !e.dataTransfer || !e.dataTransfer.files.length) {
                    return;
                }
                var merged = new DataTransfer();
                if (input.files) {
                    Array.from(input.files).forEach(function (f) {
                        merged.items.add(f);
                    });
                }
                for (var i = 0; i < e.dataTransfer.files.length; i++) {
                    if (e.dataTransfer.files[i].type.indexOf('image/') === 0) {
                        merged.items.add(e.dataTransfer.files[i]);
                    }
                }
                input.files = merged.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            },
            false
        );
    }

    window.initProductProposalBlocks = function (root) {
        root = root || document;
        enhancePriceAndWeight(root);
        bindQuoteItemSubtitles(root);
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    };
})();
