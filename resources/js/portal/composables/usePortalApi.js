import axios from 'axios';

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function buildRequestConfig(url, options = {}) {
    const cleanUrl = url.startsWith('/') ? url : `/${url}`;
    const prefixedUrl = `/api/portal${cleanUrl}`;

    return {
        url: prefixedUrl,
        method: options.method || 'get',
        data: options.data || undefined,
        params: options.params || undefined,
        responseType: options.responseType || undefined,
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            ...(options.headers || {}),
        },
        withCredentials: true,
    };
}

export async function apiRequest(url, options = {}) {
    const response = await axios(buildRequestConfig(url, options));
    return response.data;
}
