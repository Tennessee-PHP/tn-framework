/**
 * Fetch with X-CSRF-Token header when the page has a staff csrf-token meta tag.
 * Use for staff mutation requests that use fetch() instead of axios.
 */
export function fetchWithCsrf(url: string | URL | Request, init?: RequestInit): Promise<Response> {
    const options: RequestInit = { ...init };
    const method = (options.method ?? 'GET').toUpperCase();
    if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (token) {
            const headers = new Headers(options.headers);
            headers.set('X-CSRF-Token', token);
            options.headers = headers;
        }
    }
    return fetch(url, options);
}
