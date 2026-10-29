const MESSAGES = {
    name: {
        required: 'El nombre es obligatorio.',
    },
    email: {
        required: 'El correo es obligatorio.',
        email: 'Ingresa un correo válido.',
    },
    password: {
        required: 'La contraseña es obligatoria.',
        min: 'La contraseña debe tener al menos 8 caracteres.',
        mixed: 'La contraseña debe incluir al menos una letra mayúscula y una minúscula.',
        numbers: 'La contraseña debe incluir al menos un número.',
    },
    password_confirmation: {
        required: 'Confirma tu contraseña.',
        confirmation: 'Las contraseñas no coinciden.',
    },
};

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[A-Za-z]{2,}$/;

const FIELD_SELECTOR = 'input[data-rules], textarea[data-rules]';

function errorElement(field) {
    return document.querySelector(`[data-error-for="${field.name}"]`);
}

function setError(field, message) {
    const error = errorElement(field);

    if (error) {
        error.textContent = message;
        error.classList.remove('hidden');
    }

    field.setAttribute('aria-invalid', 'true');
    field.classList.add('!border-primario');
}

function clearError(field) {
    const error = errorElement(field);

    if (error) {
        error.textContent = '';
        error.classList.add('hidden');
    }

    field.setAttribute('aria-invalid', 'false');
    field.classList.remove('!border-primario');
}

function validateField(field) {
    const rules = (field.dataset.rules || '').split(/\s+/).filter(Boolean);
    const messages = MESSAGES[field.name] || {};
    const raw = field.value;
    const value = raw.trim();

    for (const rule of rules) {
        if (rule === 'required' && value === '') {
            return messages.required || 'Este campo es obligatorio.';
        }

        if (rule === 'email' && value !== '' && !EMAIL_PATTERN.test(value)) {
            return messages.email;
        }

        if (rule === 'password' && value !== '') {
            if (raw.length < 8) {
                return messages.min;
            }

            if (!/[a-z]/.test(raw) || !/[A-Z]/.test(raw)) {
                return messages.mixed;
            }

            if (!/\d/.test(raw)) {
                return messages.numbers;
            }
        }

        if (rule === 'confirmation' && value !== '') {
            const target = document.querySelector(`[name="${field.dataset.match}"]`);

            if (target && target.value !== raw) {
                return messages.confirmation;
            }
        }
    }

    return '';
}

function refreshField(field) {
    const message = validateField(field);

    if (message) {
        setError(field, message);

        return false;
    }

    clearError(field);

    return true;
}

function setupForm(form) {
    const fields = [...form.querySelectorAll(FIELD_SELECTOR)];

    form.noValidate = true;

    fields.forEach((field) => {
        const error = errorElement(field);
        const hasServerError = error && !error.classList.contains('hidden');

        if (hasServerError) {
            field.dataset.serverError = error.textContent.trim();
        }

        field.dataset.touched = hasServerError ? 'true' : 'false';

        field.addEventListener('blur', () => {
            field.dataset.touched = 'true';

            if (field.dataset.serverError) {
                setError(field, field.dataset.serverError);

                return;
            }

            refreshField(field);
        });

        field.addEventListener('input', () => {
            delete field.dataset.serverError;

            if (field.dataset.touched === 'true') {
                refreshField(field);
            }

            if (field.name === 'password') {
                const confirmation = form.querySelector('[name="password_confirmation"]');

                if (confirmation && confirmation.dataset.touched === 'true') {
                    refreshField(confirmation);
                }
            }
        });
    });

    form.addEventListener('submit', (event) => {
        let firstInvalid = null;

        fields.forEach((field) => {
            field.dataset.touched = 'true';
            delete field.dataset.serverError;

            if (!refreshField(field) && firstInvalid === null) {
                firstInvalid = field;
            }
        });

        if (firstInvalid) {
            event.preventDefault();
            firstInvalid.focus();
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-validation]').forEach(setupForm);
});
