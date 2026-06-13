const timeoutMeta = document.querySelector('meta[name="smartstore-session-timeout"]');

if (timeoutMeta) {
    const timeoutMs = Number(timeoutMeta.content || 0) * 1000;
    const logoutUrl = document.querySelector('meta[name="smartstore-logout-url"]')?.content || '/logout';
    const loginUrl = document.querySelector('meta[name="smartstore-login-url"]')?.content || '/login';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const heartbeatUrl = '/session/heartbeat';
    let deadline = Date.now() + timeoutMs;
    let timerId = null;
    let heartbeatInFlight = false;
    let lastHeartbeatAt = 0;
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
        window.SmartStorePosStorage?.clear?.();

        window.setTimeout(redirectToLogin, 2500);

        const controller = window.AbortController ? new AbortController() : null;
        const abortTimer = controller ? window.setTimeout(() => controller.abort(), 2000) : null;

        fetch(logoutUrl, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            signal: controller?.signal,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: '{}',
        }).finally(() => {
            if (abortTimer) {
                window.clearTimeout(abortTimer);
            }

            redirectToLogin();
        });
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
        heartbeat();
    };

    const heartbeat = (force = false) => {
        if (loggingOut || heartbeatInFlight) {
            return;
        }

        if (!force && Date.now() - lastHeartbeatAt < 30000) {
            return;
        }

        lastHeartbeatAt = Date.now();
        heartbeatInFlight = true;

        fetch(heartbeatUrl, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
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
                    markActivity();
                }
            }
        });

        schedule();
        heartbeat(true);
    }
}
