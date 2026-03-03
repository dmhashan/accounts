import '../../bootstrap';

let refreshRequest = null;
let logoutTriggered = false;

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function buildRequestConfig(url, options = {}) {
    return {
        url,
        method: options.method || 'get',
        data: options.data || undefined,
        params: options.params || undefined,
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            ...(options.headers || {}),
        },
        withCredentials: true,
    };
}

async function tryRefreshToken() {
    if (refreshRequest) {
        return refreshRequest;
    }

    refreshRequest = window.axios(buildRequestConfig('/api/auth/refresh', { method: 'post' }));

    try {
        await refreshRequest;
        return true;
    } catch {
        return false;
    } finally {
        refreshRequest = null;
    }
}

async function logoutAndRedirectToLogin() {
    if (logoutTriggered) {
        return;
    }

    logoutTriggered = true;

    try {
        await window.axios(buildRequestConfig('/api/auth/logout', { method: 'post' }));
    } catch {
        // no-op: continue redirect even if logout call fails
    } finally {
        window.location.assign('/login');
    }
}

export async function apiRequest(url, options = {}) {
    try {
        const response = await window.axios(buildRequestConfig(url, options));
        return response.data;
    } catch (error) {
        const status = error?.response?.status;
        const alreadyRetried = Boolean(options.__retriedAfterRefresh);
        const isRefreshCall = url === '/api/auth/refresh';
        const isLogoutCall = url === '/api/auth/logout';

        if (status === 401 && !alreadyRetried && !isRefreshCall && !isLogoutCall) {
            const refreshed = await tryRefreshToken();

            if (refreshed) {
                return apiRequest(url, {
                    ...options,
                    __retriedAfterRefresh: true,
                });
            }

            await logoutAndRedirectToLogin();
        }

        throw error;
    }
}
