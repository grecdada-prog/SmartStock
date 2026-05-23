const timeoutMeta = document.querySelector('meta[name="smartstore-session-timeout"]');

if (timeoutMeta) {
    const timeoutMs = Number(timeoutMeta.content || 0) * 1000;
    const logoutUrl = document.querySelector('meta[name="smartstore-logout-url"]')?.content || '/logout';
    const loginUrl = document.querySelector('meta[name="smartstore-login-url"]')?.content || '/login';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const heartbeatUrl = '/session/heartbeat';
    let deadline = Date.now() + timeoutMs;
    let timerId = null;
    let heartbeatTimerId = null;
    let heartbeatInFlight = false;
    let loggingOut = false;

    const redirectToLogin = () => {
        const url = new URL(loginUrl, window.location.origin);
        url.searchParams.set('inactive', '1');
        window.location.assign(url.toString());
    };

    const logoutForInactivity = () => {
        if (loggingOut) {
            return;
        }

        loggingOut = true;

        fetch(logoutUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: '{}',
        }).finally(redirectToLogin);
    };

    const schedule = () => {
        window.clearTimeout(timerId);

        const delay = Math.max(0, deadline - Date.now());
        timerId = window.setTimeout(logoutForInactivity, delay);
    };

    const markActivity = () => {
        if (loggingOut) {
            return;
        }

        deadline = Date.now() + timeoutMs;
        schedule();
    };

    const heartbeat = () => {
        if (loggingOut || heartbeatInFlight) {
            return;
        }

        heartbeatInFlight = true;

        fetch(heartbeatUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: '{}',
        }).finally(() => {
            heartbeatInFlight = false;
        });
    };

    if (timeoutMs > 0) {
        ['click', 'keydown', 'mousemove', 'scroll', 'touchstart'].forEach((eventName) => {
            window.addEventListener(eventName, markActivity, { passive: true });
        });

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                if (Date.now() >= deadline) {
                    logoutForInactivity();
                } else {
                    schedule();
                    heartbeat();
                }
            }
        });

        schedule();
        heartbeat();
        heartbeatTimerId = window.setInterval(heartbeat, 15000);
    }
}
