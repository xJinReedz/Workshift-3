/**
 * WorkShift Application JavaScript — Claymorphism Edition
 * Handles Theme Toggling (Light/Dark), Off-Canvas Drawer, Task Slide-Over,
 * Custom Clay Modals, and Micro-interactions.
 */

// Subdirectory Base URL Auto-Detection & Fetch Interceptor
(function initBasePath() {
    if (typeof window.__WS_BASE__ === 'undefined') {
        const scriptTag = document.querySelector('script[src*="/assets/js/app.js"]');
        if (scriptTag) {
            const src = scriptTag.getAttribute('src');
            window.__WS_BASE__ = src.substring(0, src.indexOf('/assets/js/app.js'));
        } else {
            window.__WS_BASE__ = '';
        }
    }

    if (window.__WS_BASE__) {
        const _origFetch = window.fetch;
        window.fetch = function(input, init) {
            if (typeof input === 'string') {
                if (input.startsWith('/') && !input.startsWith('//') && !input.startsWith(window.__WS_BASE__)) {
                    input = window.__WS_BASE__ + input;
                }
            } else if (input instanceof Request) {
                const url = new URL(input.url);
                if (url.origin === window.location.origin && url.pathname.startsWith('/') && !url.pathname.startsWith(window.__WS_BASE__)) {
                    input = new Request(window.__WS_BASE__ + url.pathname + url.search, input);
                }
            }
            return _origFetch.call(this, input, init);
        };
    }
})();

// Global CSRF Token Auto-getter
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
           document.querySelector('input[name="_csrf_token"]')?.value ||
           window.__CSRF_TOKEN__ ||
           '';
}
const csrfToken = getCsrfToken();

/**
 * Shared API Fetch Helper (Contract: { ok: bool, data?, error?: { code, message, fields? } })
 */
async function apiFetch(url, options = {}) {
    const token = getCsrfToken();
    const defaultHeaders = {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': token
    };

    if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData)) {
        defaultHeaders['Content-Type'] = 'application/json';
        options.body = JSON.stringify(options.body);
    }

    options.headers = Object.assign({}, defaultHeaders, options.headers || {});
    options.credentials = options.credentials || 'same-origin';

    const isLocal = window.__APP_ENV__ === 'local' || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

    if (isLocal) {
        console.log(`[apiFetch OUT] ${options.method || 'GET'} ${url}`, options);
    }

    let response;
    try {
        response = await fetch(url, options);
    } catch (networkErr) {
        if (isLocal) console.error('[apiFetch NET_ERR]', networkErr);
        if (window.WS && window.WS.modal) {
            window.WS.modal.error({
                title: 'Network Error',
                message: 'Unable to communicate with the server. Please check your internet connection.'
            });
        }
        throw networkErr;
    }

    const contentType = response.headers.get('content-type') || '';
    let data = null;
    let isJson = contentType.includes('application/json');

    if (isJson) {
        try {
            data = await response.json();
        } catch (jsonErr) {
            isJson = false;
        }
    }

    if (isLocal) {
        console.log(`[apiFetch IN] ${response.status} ${url}`, data || '[Non-JSON]');
    }

    if (!isJson) {
        const errorMsg = `The server returned an unexpected response (Status ${response.status}).`;
        if (response.status === 401) {
            window.WS.modal.custom({
                variant: 'warning',
                title: 'Session Expired',
                contentHTML: '<p class="text-xs text-muted mb-4">Your login session has timed out. Please sign in again to continue.</p>',
                actions: [{ text: 'Log In Again', className: 'btn-primary', onClick: () => { window.location.href = '/login'; } }]
            });
        } else if (window.WS && window.WS.modal) {
            window.WS.modal.error({
                title: 'Unexpected Server Response',
                message: errorMsg
            });
        }
        throw new Error(errorMsg);
    }

    if (!response.ok || data.ok === false || data.success === false) {
        const status = response.status;
        const msg = data.error?.message || data.message || data.error || 'An error occurred processing your request.';

        if (window.WS && window.WS.modal) {
            if (status === 401) {
                window.WS.modal.custom({
                    variant: 'warning',
                    title: 'Session Expired',
                    contentHTML: '<p class="text-xs text-muted mb-4">Your login session has timed out. Please sign in again.</p>',
                    actions: [{ text: 'Log In Again', className: 'btn-primary', onClick: () => { window.location.href = '/login'; } }]
                });
            } else if (status === 403) {
                window.WS.modal.error({ title: 'Access Forbidden', message: msg });
            } else if (status === 404) {
                window.WS.modal.error({ title: 'Not Found', message: msg });
            } else if (status === 419 || data.error?.code === 'CSRF_FAILED') {
                window.WS.modal.custom({
                    variant: 'warning',
                    title: 'Page Expired',
                    contentHTML: `<p class="text-xs text-muted mb-4">${msg}</p>`,
                    actions: [{ text: 'Refresh Page', className: 'btn-primary', onClick: () => { window.location.reload(); } }]
                });
            } else if (status === 422) {
                let fieldsHtml = '';
                if (data.error?.fields && Array.isArray(data.error.fields)) {
                    fieldsHtml = '<ul class="text-xs text-danger mt-2 pl-4 list-disc">' + data.error.fields.map(f => `<li>${f}</li>`).join('') + '</ul>';
                }
                window.WS.modal.error({ title: 'Validation Error', message: msg + fieldsHtml });
            } else if (status >= 500) {
                window.WS.modal.error({ title: 'Something Went Wrong', message: msg, buttonText: 'Try Again' });
            }
        }
    }

    return data;
}

window.apiFetch = apiFetch;
window.getCsrfToken = getCsrfToken;


// =========================================================================
// Theme Management (Light / Dark Mode with LocalStorage & OS Auto-detect)
// =========================================================================
(function initTheme() {
    const savedTheme = localStorage.getItem('workshift-theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const initialTheme = savedTheme || (prefersDark ? 'dark' : 'light');

    document.documentElement.setAttribute('data-theme', initialTheme);
})();

function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('workshift-theme', next);

    // Update icons if present
    document.querySelectorAll('.theme-toggle-icon').forEach(icon => {
        icon.classList.toggle('hidden', icon.dataset.theme !== next);
    });
}

// =========================================================================
// Mobile Off-Canvas Drawer (Sidebar)
// =========================================================================
function toggleSidebar() {
    document.body.classList.toggle('sidebar-open');
}

function closeSidebar() {
    document.body.classList.remove('sidebar-open');
}

// Close sidebar on backdrop click or link click inside sidebar on mobile
document.addEventListener('DOMContentLoaded', () => {
    const backdrop = document.querySelector('.sidebar-backdrop');
    if (backdrop) {
        backdrop.addEventListener('click', closeSidebar);
    }

    const sidebarLinks = document.querySelectorAll('.app-sidebar .nav-item');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 1024) {
                closeSidebar();
            }
        });
    });
});

// =========================================================================
// Slide-Over Task Inspector (Desktop Slideover & Mobile Sheet)
// =========================================================================
function openTaskSlideover(taskId, portalToken = '') {
    const panel = document.getElementById('task-slideover');
    const backdrop = document.getElementById('slideover-backdrop');
    const body = document.getElementById('slideover-task-body');

    if (!panel || !body) {
        // Fallback to legacy modal if slideover doesn't exist
        return openTaskModal(taskId, portalToken);
    }

    panel.classList.add('open');
    if (backdrop) backdrop.classList.add('open');
    body.innerHTML = `
        <div class="p-8 text-center">
            <div class="empty-icon-bubble mx-auto mb-3 animate-spin">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a10 10 0 0 1 10 10"/></svg>
            </div>
            <p class="text-muted font-bold text-sm">Loading task details...</p>
        </div>
    `;

    let url = `/tasks/${taskId}/modal`;
    if (portalToken) {
        url += `?portal_token=${encodeURIComponent(portalToken)}`;
    }

    fetch(url)
        .then(res => {
            if (!res.ok) throw new Error('Failed to load task details');
            return res.text();
        })
        .then(html => {
            body.innerHTML = html;
        })
        .catch(err => {
            body.innerHTML = `<div class="p-6 text-center text-danger font-bold">${err.message}</div>`;
        });
}

function closeTaskSlideover() {
    const panel = document.getElementById('task-slideover');
    const backdrop = document.getElementById('slideover-backdrop');
    if (panel) panel.classList.remove('open');
    if (backdrop) backdrop.classList.remove('open');
}

// Legacy modal compatibility
function openTaskModal(taskId, portalToken = '') {
    const modal = document.getElementById('task-modal');
    const modalBody = document.getElementById('modal-task-body');
    if (!modal) return;

    modal.classList.add('open');
    modal.classList.remove('hidden');
    if (modalBody) {
        modalBody.innerHTML = '<div class="p-6 text-center text-muted font-bold">Loading task details...</div>';
    }

    let url = `/tasks/${taskId}/modal`;
    if (portalToken) {
        url += `?portal_token=${encodeURIComponent(portalToken)}`;
    }

    fetch(url)
        .then(res => {
            if (!res.ok) throw new Error('Failed to load task');
            return res.text();
        })
        .then(html => {
            if (modalBody) modalBody.innerHTML = html;
        })
        .catch(err => {
            if (modalBody) modalBody.innerHTML = `<div class="p-6 text-center text-danger font-bold">${err.message}</div>`;
        });
}

function closeTaskModal() {
    const modal = document.getElementById('task-modal');
    if (modal) {
        modal.classList.remove('open');
        modal.classList.add('hidden');
    }
}

// Global click & key listeners for modals & drawers
document.addEventListener('click', function (e) {
    // Backdrop click on slideover
    if (e.target && e.target.id === 'slideover-backdrop') {
        closeTaskSlideover();
    }
    // Backdrop click on modal
    const modal = document.getElementById('task-modal');
    if (modal && e.target === modal) {
        closeTaskModal();
    }
    // Generic modal backdrops
    if (e.target && e.target.classList.contains('clay-modal-backdrop')) {
        e.target.classList.remove('open');
    }
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeTaskSlideover();
        closeTaskModal();
        closeSidebar();
        document.querySelectorAll('.clay-modal-backdrop.open').forEach(m => m.classList.remove('open'));
    }
});

// =========================================================================
// Copy to Clipboard Utility
// =========================================================================
function copyPortalLink(url, btn) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(url).then(() => {
            showCopyFeedback(btn);
        });
    } else {
        const input = document.createElement('input');
        input.value = url;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        showCopyFeedback(btn);
    }
}

function showCopyFeedback(btn) {
    if (!btn) return;
    const origHtml = btn.innerHTML;
    btn.innerHTML = '✓ Copied!';
    btn.classList.add('clay-btn-success');
    setTimeout(() => {
        btn.innerHTML = origHtml;
        btn.classList.remove('clay-btn-success');
    }, 2000);
}

// =========================================================================
// Custom Clay Confirm Dialog
// =========================================================================
function clayConfirm(message, onConfirm) {
    let backdrop = document.getElementById('clay-confirm-backdrop');
    if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.id = 'clay-confirm-backdrop';
        backdrop.className = 'clay-modal-backdrop';
        backdrop.innerHTML = `
            <div class="modal" style="max-width: 400px; background: var(--surface-card); border: 1px solid var(--border-outline); border-radius: var(--radius-lg); box-shadow: var(--shadow-modal);">
                <div class="modal-body text-center p-6">
                    <div class="inline-flex items-center justify-center mb-3" style="width: 48px; height: 48px; border-radius: 50%; color: var(--color-warning); background: var(--color-warning-subtle);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                    <h3 class="font-heading text-base font-bold text-primary mb-1">Are you sure?</h3>
                    <p id="clay-confirm-msg" class="text-xs text-secondary mb-5 leading-relaxed"></p>
                    <div class="flex items-center justify-center gap-2">
                        <button type="button" class="btn btn-secondary btn-sm" id="clay-confirm-cancel">Cancel</button>
                        <button type="button" class="btn btn-danger btn-sm" id="clay-confirm-yes">Confirm</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(backdrop);
    }

    document.getElementById('clay-confirm-msg').textContent = message;
    backdrop.classList.add('open');

    const yesBtn = document.getElementById('clay-confirm-yes');
    const cancelBtn = document.getElementById('clay-confirm-cancel');

    const cleanup = () => {
        backdrop.classList.remove('open');
        yesBtn.onclick = null;
        cancelBtn.onclick = null;
    };

    yesBtn.onclick = () => {
        cleanup();
        onConfirm();
    };

    cancelBtn.onclick = () => {
        cleanup();
    };
}
