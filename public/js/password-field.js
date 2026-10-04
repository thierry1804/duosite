(function () {
    'use strict';

    var UPPER = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    var LOWER = 'abcdefghijkmnopqrstuvwxyz';
    var DIGITS = '23456789';
    var SYMBOLS = '!@#$%&*-_=+?';
    var ALL = UPPER + LOWER + DIGITS + SYMBOLS;

    function randomInt(max) {
        var values = new Uint32Array(1);
        window.crypto.getRandomValues(values);
        return values[0] % max;
    }

    function pick(charset) {
        return charset.charAt(randomInt(charset.length));
    }

    function generateStrongPassword(length) {
        length = Math.max(length || 16, 12);
        var chars = [pick(UPPER), pick(LOWER), pick(DIGITS), pick(SYMBOLS)];
        while (chars.length < length) {
            chars.push(pick(ALL));
        }
        for (var i = chars.length - 1; i > 0; i--) {
            var j = randomInt(i + 1);
            var tmp = chars[i];
            chars[i] = chars[j];
            chars[j] = tmp;
        }
        return chars.join('');
    }

    function setToggleState(input, button, visible) {
        input.type = visible ? 'text' : 'password';
        var icon = button.querySelector('i');
        if (icon) {
            icon.className = visible ? 'fas fa-eye-slash' : 'fas fa-eye';
        }
        button.setAttribute('aria-label', visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
        button.setAttribute('title', visible ? 'Masquer' : 'Afficher');
    }

    function enhanceInput(input) {
        if (input.dataset.passwordEnhanced === '1') {
            return;
        }
        input.dataset.passwordEnhanced = '1';
        input.classList.add('password-field-input');

        var parent = input.parentElement;
        if (!parent) {
            return;
        }

        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-link password-toggle-btn';
        button.setAttribute('aria-label', 'Afficher le mot de passe');
        button.setAttribute('title', 'Afficher');
        button.innerHTML = '<i class="fas fa-eye" aria-hidden="true"></i>';
        button.addEventListener('click', function () {
            setToggleState(input, button, input.type === 'password');
        });

        if (parent.classList.contains('form-floating')) {
            parent.classList.add('password-field-parent');
            parent.appendChild(button);
            return;
        }

        var wrap = document.createElement('div');
        wrap.className = 'password-field-parent';
        parent.insertBefore(wrap, input);
        wrap.appendChild(input);
        wrap.appendChild(button);
    }

    function findPasswordPair(root) {
        var inputs = Array.prototype.slice.call(root.querySelectorAll('input[type="password"], input.password-field-input'));
        var passwords = inputs.filter(function (el) {
            return el.type === 'password' || el.classList.contains('password-field-input');
        });
        // Prefer first/second naming from Symfony RepeatedType
        var first = passwords.find(function (el) { return /_first$/.test(el.id) || /\[first\]/.test(el.name); });
        var second = passwords.find(function (el) { return /_second$/.test(el.id) || /\[second\]/.test(el.name); });
        if (first && second) {
            return [first, second];
        }
        return passwords;
    }

    function scorePassword(value) {
        if (!value) {
            return { score: 0, percent: 0, label: '', level: 'empty' };
        }

        var score = 0;
        if (value.length >= 8) score += 1;
        if (value.length >= 12) score += 1;
        if (value.length >= 16) score += 1;
        if (/[a-z]/.test(value)) score += 1;
        if (/[A-Z]/.test(value)) score += 1;
        if (/[0-9]/.test(value)) score += 1;
        if (/[^A-Za-z0-9]/.test(value)) score += 1;

        if (score <= 2) {
            return { score: score, percent: 25, label: 'Faible', level: 'weak' };
        }
        if (score <= 4) {
            return { score: score, percent: 50, label: 'Moyen', level: 'fair' };
        }
        if (score <= 6) {
            return { score: score, percent: 75, label: 'Fort', level: 'good' };
        }
        return { score: score, percent: 100, label: 'Très fort', level: 'strong' };
    }

    function insertAfter(reference, node) {
        if (!reference || !reference.parentNode) {
            return;
        }
        reference.parentNode.insertBefore(node, reference.nextSibling);
    }

    function createStrengthMeter() {
        var meter = document.createElement('div');
        meter.className = 'password-strength d-none';
        meter.setAttribute('aria-live', 'polite');
        meter.innerHTML =
            '<div class="password-strength-track">' +
            '<div class="password-strength-bar"></div>' +
            '</div>' +
            '<div class="password-strength-label"></div>';
        return meter;
    }

    function updateStrengthMeter(meter, value) {
        var result = scorePassword(value);
        var bar = meter.querySelector('.password-strength-bar');
        var label = meter.querySelector('.password-strength-label');

        if (!value) {
            meter.classList.add('d-none');
            meter.classList.remove('is-weak', 'is-fair', 'is-good', 'is-strong');
            return;
        }

        meter.classList.remove('d-none', 'is-weak', 'is-fair', 'is-good', 'is-strong');
        meter.classList.add('is-' + result.level);
        bar.style.width = result.percent + '%';
        label.textContent = 'Force : ' + result.label;
    }

    function addGenerateControl(root, targets) {
        if (!targets.length || root.querySelector('[data-password-generate]')) {
            return;
        }

        var first = targets[0];
        var fieldParent = first.closest('.password-field-parent') || first.parentElement;
        var fieldBlock = first.closest('.mb-3, .col-md-6, .form-floating') || fieldParent;
        var label = first.id ? root.querySelector('label[for="' + first.id + '"]') : null;

        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-link btn-sm password-generate-btn';
        button.setAttribute('data-password-generate', '1');
        button.innerHTML = '<i class="fas fa-key me-1" aria-hidden="true"></i>Générer';
        button.setAttribute('title', 'Générer un mot de passe fort');
        button.setAttribute('aria-label', 'Générer un mot de passe fort');

        var hint = document.createElement('div');
        hint.className = 'form-text password-generate-hint d-none';
        hint.setAttribute('role', 'status');

        var meter = createStrengthMeter();

        if (label && !label.closest('.form-floating')) {
            var labelRow = document.createElement('div');
            labelRow.className = 'password-field-label-row d-flex justify-content-between align-items-center gap-2';
            label.parentNode.insertBefore(labelRow, label);
            labelRow.appendChild(label);
            labelRow.appendChild(button);
            if (fieldParent) {
                insertAfter(fieldParent, meter);
                insertAfter(meter, hint);
            }
        } else if (fieldBlock && fieldBlock.classList.contains('form-floating')) {
            var floatingHeader = document.createElement('div');
            floatingHeader.className = 'password-field-label-row d-flex justify-content-end mb-1';
            floatingHeader.appendChild(button);
            fieldBlock.parentNode.insertBefore(floatingHeader, fieldBlock);
            insertAfter(fieldBlock, meter);
            insertAfter(meter, hint);
        } else if (fieldParent && fieldParent.parentNode) {
            fieldParent.parentNode.insertBefore(button, fieldParent);
            insertAfter(fieldParent, meter);
            insertAfter(meter, hint);
        }

        first.addEventListener('input', function () {
            updateStrengthMeter(meter, first.value);
        });
        updateStrengthMeter(meter, first.value);

        button.addEventListener('click', function () {
            var password = generateStrongPassword(16);
            targets.forEach(function (input) {
                input.value = password;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
                var toggle = input.parentElement && input.parentElement.querySelector('.password-toggle-btn');
                if (toggle) {
                    setToggleState(input, toggle, true);
                } else {
                    input.type = 'text';
                }
            });
            updateStrengthMeter(meter, password);
            hint.textContent = 'Mot de passe généré et affiché. Copiez-le avant de continuer.';
            hint.classList.remove('d-none');
        });
    }

    function enhance(root, options) {
        root = root || document;
        options = options || {};
        var inputs = root.querySelectorAll('input[type="password"]');
        Array.prototype.forEach.call(inputs, enhanceInput);

        if (options.generate) {
            addGenerateControl(root, findPasswordPair(root));
        }
    }

    window.PasswordField = {
        enhance: enhance,
        generateStrongPassword: generateStrongPassword
    };
})();
