/**
 * Duo Import MDG - Script pour le formulaire de devis (mise en page "panier")
 */

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('#quoteForm');
    const loader = document.querySelector('#loader');
    const itemsWrapper = document.querySelector('.quote-items-wrapper');
    const addItemButton = document.querySelector('#quote-add-item-btn');
    const addHint = document.querySelector('#quoteAddHint');
    const recapLines = document.querySelector('#quoteRecapLines');
    const recapTotal = document.querySelector('#quoteRecapTotal');
    const submitButton = document.querySelector('#submitButton');
    const inlinePayment = document.querySelector('#quoteInlinePayment');
    const inlinePaymentTotal = document.querySelector('#quoteInlinePaymentTotal');
    const paymentReferenceInput = document.querySelector('#quotePaymentReferenceInput');
    const paymentReferenceError = document.querySelector('#quotePaymentReferenceError');
    const transactionReferenceInput = document.querySelector('#quote_transactionReference');

    const freeItemsLimit = form ? (parseInt(form.dataset.freeItemsLimit, 10) || 2) : 2;
    const itemPrice = form ? (parseInt(form.dataset.itemPrice, 10) || 0) : 0;

    function fmt(n) {
        return Math.max(0, Math.round(n)).toLocaleString('fr-FR') + ' Ar';
    }

    // ---- Aperçu photo ----
    function initPhotoPreview(fileInput) {
        if (!fileInput) return;
        const container = fileInput.closest('.photo-upload-container');
        if (!container) return;
        const preview = container.querySelector('.product-photo-preview');
        if (!preview) return;

        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            } else {
                preview.src = '#';
                preview.style.display = 'none';
            }
            updateRecap();
        });
    }

    document.querySelectorAll('.form-control-file').forEach(function(fileInput) {
        fileInput.required = true;
        initPhotoPreview(fileInput);
    });

    // ---- Compteur de caractères pour la description ----
    function updateDescriptionCounter(textarea) {
        if (!textarea || !textarea.classList.contains('quote-item-description')) {
            return;
        }
        const min = parseInt(textarea.getAttribute('data-min-length') || '0', 10) || 10;
        const parent = textarea.parentElement;
        if (!parent) {
            return;
        }
        let hint = parent.querySelector('.quote-description-counter');
        if (!hint) {
            hint = document.createElement('small');
            hint.className = 'form-text quote-description-counter';
            hint.setAttribute('aria-live', 'polite');
            textarea.insertAdjacentElement('afterend', hint);
        }
        const len = (textarea.value || '').length;
        hint.textContent = len + ' / ' + min + ' caractères (minimum requis)';
        hint.classList.remove('text-muted', 'text-danger', 'text-success');
        if (len === 0) {
            hint.classList.add('text-muted');
        } else if (len < min) {
            hint.classList.add('text-danger');
        } else {
            hint.classList.add('text-success');
        }
    }

    function initQuoteDescriptionCounters(scope) {
        const root = scope || document;
        root.querySelectorAll('textarea.quote-item-description').forEach(updateDescriptionCounter);
    }

    initQuoteDescriptionCounters(form);

    if (form) {
        form.addEventListener('input', function(e) {
            if (e.target && e.target.classList && e.target.classList.contains('quote-item-description')) {
                updateDescriptionCounter(e.target);
            }
        });
    }

    // ---- Récapitulatif live (prix, étiquettes, bouton d'envoi, paiement) ----
    function updateRecap() {
        const items = document.querySelectorAll('.quote-item');
        const n = items.length;
        const extra = Math.max(0, n - freeItemsLimit);
        const total = extra * itemPrice;

        const lines = [];
        items.forEach(function(item, i) {
            const typeSelect = item.querySelector('.product-type-select');
            const qtyInput = item.querySelector('.quote-item-qty');
            const typeText = (typeSelect && typeSelect.selectedIndex > 0)
                ? typeSelect.options[typeSelect.selectedIndex].text
                : 'Article sans type';
            const qty = qtyInput ? qtyInput.value.trim() : '';
            const isFree = i < freeItemsLimit;

            const label = document.createElement('span');
            label.className = 'quote-recap-line-label';
            label.textContent = (i + 1) + '. ' + typeText + (qty ? ' × ' + qty : '');

            const price = document.createElement('span');
            price.className = 'quote-recap-line-price' + (isFree ? ' is-free' : '');
            price.textContent = isFree ? 'gratuit' : fmt(itemPrice);

            const line = document.createElement('div');
            line.className = 'quote-recap-line';
            line.appendChild(label);
            line.appendChild(price);
            lines.push(line);

            const tag = item.querySelector('.quote-item-tag');
            if (tag) {
                tag.textContent = isFree ? 'Gratuit' : ('+' + fmt(itemPrice));
                tag.classList.toggle('is-paid', !isFree);
            }
        });

        if (recapLines) {
            recapLines.innerHTML = '';
            lines.forEach(function(line) { recapLines.appendChild(line); });
        }
        if (recapTotal) {
            recapTotal.textContent = total > 0 ? fmt(total) : 'Gratuit';
        }
        if (addHint) {
            addHint.textContent = n < freeItemsLimit ? 'gratuit' : ('+' + fmt(itemPrice));
            addHint.classList.toggle('is-paid', n >= freeItemsLimit);
        }
        if (submitButton) {
            submitButton.textContent = extra > 0
                ? ('Envoyer ma demande · ' + fmt(total))
                : 'Envoyer ma demande · gratuit';
        }
        if (inlinePayment) {
            if (extra > 0) {
                inlinePayment.classList.remove('d-none');
                if (inlinePaymentTotal) {
                    inlinePaymentTotal.textContent = fmt(total);
                }
            } else {
                inlinePayment.classList.add('d-none');
                if (paymentReferenceInput) {
                    paymentReferenceInput.value = '';
                }
                if (transactionReferenceInput) {
                    transactionReferenceInput.value = '';
                }
                if (paymentReferenceError) {
                    paymentReferenceError.classList.add('d-none');
                }
            }
        }
    }

    if (paymentReferenceInput) {
        paymentReferenceInput.addEventListener('input', function() {
            if (transactionReferenceInput) {
                transactionReferenceInput.value = this.value.trim();
            }
            if (this.value.trim() && paymentReferenceError) {
                paymentReferenceError.classList.add('d-none');
            }
        });
    }

    if (itemsWrapper) {
        itemsWrapper.addEventListener('change', function(e) {
            if (e.target && (e.target.classList.contains('product-type-select') || e.target.classList.contains('quote-item-qty'))) {
                updateRecap();
            }
        });
        itemsWrapper.addEventListener('input', function(e) {
            if (e.target && e.target.classList.contains('quote-item-qty')) {
                updateRecap();
            }
        });
    }

    // ---- Ajout d'un article (clone du premier article existant) ----
    function performAddQuoteItem() {
        if (!itemsWrapper) {
            return;
        }
        const existingItems = document.querySelectorAll('.quote-item');
        if (existingItems.length === 0) {
            return;
        }

        const clone = existingItems[0].cloneNode(true);
        const index = parseInt(itemsWrapper.dataset.index, 10);

        const inputs = clone.querySelectorAll('input, select, textarea');
        inputs.forEach(function(input) {
            const oldId = input.id;
            const oldName = input.name;

            if (oldId) {
                const newId = oldId.replace(/\d+/, index);
                input.id = newId;
                const labels = clone.querySelectorAll(`label[for="${oldId}"]`);
                labels.forEach(function(label) {
                    label.setAttribute('for', newId);
                });
            }

            if (oldName) {
                const newName = oldName.replace(/\[\d+\]/, `[${index}]`);
                input.name = newName;
            }

            if (input.type === 'file') {
                input.required = true;
                input.value = '';
            } else if (input.type === 'select-one') {
                input.selectedIndex = 0;
            } else {
                input.value = '';
            }
        });

        const photoPreview = clone.querySelector('.product-photo-preview');
        if (photoPreview) {
            photoPreview.src = '#';
            photoPreview.style.display = 'none';
        }

        const descriptionCounter = clone.querySelector('.quote-description-counter');
        if (descriptionCounter) {
            descriptionCounter.remove();
        }

        if (addItemButton && addItemButton.parentElement === itemsWrapper) {
            itemsWrapper.insertBefore(clone, addItemButton);
        } else {
            itemsWrapper.appendChild(clone);
        }

        const fileInput = clone.querySelector('.form-control-file');
        if (fileInput) {
            initPhotoPreview(fileInput);
        }

        initQuoteDescriptionCounters(clone);

        itemsWrapper.dataset.index = index + 1;
        updateRemoveButtonsVisibility();
        updateRecap();
    }

    if (addItemButton) {
        addItemButton.addEventListener('click', function() {
            performAddQuoteItem();
        });
    }

    // ---- Suppression d'un article ----
    document.addEventListener('click', function(e) {
        if (e.target && (e.target.classList.contains('remove-item') || e.target.closest('.remove-item'))) {
            const items = document.querySelectorAll('.quote-item');

            if (items.length <= 1) {
                alert('Vous ne pouvez pas supprimer ce produit car au moins un produit est requis.');
                return;
            }

            const item = e.target.closest('.quote-item');
            if (item) {
                item.remove();
                reindexItems();
                updateRecap();
            }
        }
    });

    function reindexItems() {
        const items = document.querySelectorAll('.quote-item');
        items.forEach(function(item, index) {
            const inputs = item.querySelectorAll('input, select, textarea');
            inputs.forEach(function(input) {
                const oldId = input.id;
                const oldName = input.name;

                if (oldId) {
                    const newId = oldId.replace(/\d+/, index);
                    input.id = newId;
                    const labels = item.querySelectorAll(`label[for="${oldId}"]`);
                    labels.forEach(function(label) {
                        label.setAttribute('for', newId);
                    });
                }

                if (oldName) {
                    const newName = oldName.replace(/\[\d+\]/, `[${index}]`);
                    input.name = newName;
                }
            });
        });

        if (itemsWrapper) {
            itemsWrapper.dataset.index = items.length;
        }

        updateRemoveButtonsVisibility();
    }

    function updateRemoveButtonsVisibility() {
        const items = document.querySelectorAll('.quote-item');
        const removeButtons = document.querySelectorAll('.remove-item');

        removeButtons.forEach(function(button) {
            button.style.display = items.length <= 1 ? 'none' : 'flex';
        });
    }

    updateRemoveButtonsVisibility();
    updateRecap();

    // ---- Soumission du formulaire ----
    if (form) {
        form.addEventListener('submit', function(event) {
            const currentItemsCount = document.querySelectorAll('.quote-item').length;
            const extra = Math.max(0, currentItemsCount - freeItemsLimit);
            const currentReference = transactionReferenceInput ? transactionReferenceInput.value.trim() : '';

            if (extra > 0 && !currentReference) {
                event.preventDefault();
                if (paymentReferenceError) {
                    paymentReferenceError.classList.remove('d-none');
                }
                if (inlinePayment) {
                    inlinePayment.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                if (paymentReferenceInput) {
                    paymentReferenceInput.focus();
                }
                return false;
            }

            const shippingMethodSelected = document.querySelectorAll('input[name="quote[shippingMethod][]"]:checked').length > 0;
            if (!shippingMethodSelected) {
                event.preventDefault();
                alert('Veuillez sélectionner au moins une méthode d\'envoi.');
                return false;
            }

            // Photo obligatoire sur chaque ligne produit (input masqué : validation native peu fiable)
            const photoInputs = document.querySelectorAll('.quote-item input[type="file"].form-control-file');
            for (const photoInput of photoInputs) {
                if (!photoInput.files || photoInput.files.length === 0) {
                    event.preventDefault();
                    alert('Veuillez ajouter une photo pour chaque produit.');
                    const label = photoInput.closest('.photo-upload-container')?.querySelector('.upload-label');
                    if (label) {
                        label.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    return false;
                }
            }

            if (submitButton) {
                submitButton.disabled = true;
            }

            if (loader) {
                loader.style.display = 'flex';
            }
        });
    }
});
