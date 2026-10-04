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

    function addGenerateControl(root, targets) {
        if (!targets.length || root.querySelector('[data-password-generate]')) {
            return;
        }

        var last = targets[targets.length - 1];
        var row = last.closest('.row');
        var block = last.closest('.mb-3, .col-md-6, .form-floating') || last.parentElement;
        var anchor = row || block;
        var host = anchor && anchor.parentElement ? anchor.parentElement : root;

        var wrap = document.createElement('div');
        wrap.className = 'password-generate-wrap mb-3';
        wrap.innerHTML =
            '<button type="button" class="btn btn-outline-secondary btn-sm" data-password-generate>' +
            '<i class="fas fa-key me-1" aria-hidden="true"></i>Générer un mot de passe fort' +
            '</button>' +
            '<div class="form-text password-generate-hint d-none" role="status"></div>';

        if (anchor && anchor.parentNode === host) {
            host.insertBefore(wrap, anchor.nextSibling);
        } else {
            host.appendChild(wrap);
        }

        var button = wrap.querySelector('[data-password-generate]');
        var hint = wrap.querySelector('.password-generate-hint');

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
            if (hint) {
                hint.textContent = 'Mot de passe généré et affiché dans les deux champs. Copiez-le avant de continuer.';
                hint.classList.remove('d-none');
            }
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
