import './validation';
import './tasks';

window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        window.location.reload();
    }
});

const panel = document.querySelector('#task-app[data-session-timeout]');

if (panel) {
    const timeoutMs = Math.max(1, Number(panel.dataset.sessionTimeout) || 1800) * 1000;
    const warningLeadMs = Math.min(120000, Math.floor(timeoutMs / 2));
    const warningDelayMs = Math.max(1, timeoutMs - warningLeadMs);
    const warning = document.querySelector('#session-warning');
    const warningTime = document.querySelector('#session-warning-time');
    const continueButton = document.querySelector('#session-warning-continue');
    let idleTimer = null;
    let warningTimer = null;

    const hideWarning = () => {
        warning?.classList.add('hidden');
    };

    const showWarning = () => {
        if (!warning) {
            return;
        }

        if (warningTime) {
            if (warningLeadMs >= 60000) {
                const minutes = Math.round(warningLeadMs / 60000);
                warningTime.textContent = `${minutes} ${minutes === 1 ? 'minuto' : 'minutos'}`;
            } else {
                const seconds = Math.max(1, Math.round(warningLeadMs / 1000));
                warningTime.textContent = `${seconds} ${seconds === 1 ? 'segundo' : 'segundos'}`;
            }
        }

        warning.classList.remove('hidden');
    };

    const expireSession = async () => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        hideWarning();

        try {
            await fetch('/logout', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'text/html',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });
        } catch {
            // Aunque falle el logout remoto, se fuerza la salida local.
        }

        window.location.href = '/login?expired=1';
    };

    const resetIdleTimer = () => {
        if (idleTimer !== null) {
            window.clearTimeout(idleTimer);
        }

        if (warningTimer !== null) {
            window.clearTimeout(warningTimer);
        }

        hideWarning();

        warningTimer = window.setTimeout(showWarning, warningDelayMs);
        idleTimer = window.setTimeout(() => {
            void expireSession();
        }, timeoutMs);
    };

    ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart'].forEach((eventName) => {
        document.addEventListener(eventName, resetIdleTimer, { passive: true });
    });

    continueButton?.addEventListener('click', resetIdleTimer);

    resetIdleTimer();
}
