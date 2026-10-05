/**
 * WorkShift Application JavaScript — Claymorphism Edition
 * Handles Theme Toggling (Light/Dark), Off-Canvas Drawer, Task Slide-Over,
 * Custom Clay Modals, and Micro-interactions.
 */

// Global CSRF Token
const csrfToken = document.querySelector('input[name="_csrf_token"]')?.value || '';

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
