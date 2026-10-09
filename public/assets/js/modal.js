/**
 * WorkShift Enterprise Modal System (WS.modal)
 * Native <dialog> / role="dialog" with Promise API, Declarative Confirmations,
 * Stacking Queue, Form Dirty Tracking, Global AJAX 401/500 Interceptor,
 * and Accessible Bottom Sheet on Mobile.
 */

(function (window, document) {
    'use strict';

    window.WS = window.WS || {};

    // Stacking Queue
    const modalQueue = [];
    let activeModalInstance = null;
    let lastFocusedElement = null;

    // SVG Icons by Variant (Crisp Lucide/Atlassian style, 1.5-2px stroke)
    const ICONS = {
        success: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#22C55E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`,
        error: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,
        warning: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
        info: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#579DFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`,
        confirm: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#579DFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
        danger: `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
        loading: `<svg class="ws-spin" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#579DFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>`
    };

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Internal: Process the next modal item in queue
     */
    function processQueue() {
        if (activeModalInstance || modalQueue.length === 0) return;
        const next = modalQueue.shift();
        activeModalInstance = next;
        renderModal(next);
    }

    /**
     * Render and display a modal
     */
    function renderModal(item) {
        lastFocusedElement = document.activeElement;
        const {
            id = 'ws-modal-' + Math.random().toString(36).substr(2, 9),
            type = 'info', // success, error, warning, info, confirm, danger, prompt, loading, custom
            variant = type,
            title = '',
            message = '',
            contentHTML = '',
            confirmText = 'OK',
            cancelText = 'Cancel',
            showCancel = (type === 'confirm' || variant === 'danger' || type === 'prompt'),
            autoCloseMs = 0,
            requiredMatch = '', // string to match for danger actions
            prompt = null, // { label, placeholder, defaultValue, multiline, required, validate }
            resolve,
            reject,
            actions = null,
            closeOnBackdrop = (type === 'info' || type === 'success')
        } = item;

        // Container element
        let root = document.getElementById('ws-modal-root');
        if (!root) {
            root = document.createElement('div');
            root.id = 'ws-modal-root';
            document.body.appendChild(root);
        }

        const isAlertdialog = (variant === 'danger' || type === 'confirm' || variant === 'warning');
        const iconSvg = ICONS[variant] || ICONS[type] || ICONS.info;

        // Dimmed backdrop wrapper
        const backdrop = document.createElement('div');
        backdrop.id = id;
        backdrop.className = 'ws-modal-backdrop';
        backdrop.setAttribute('role', isAlertdialog ? 'alertdialog' : 'dialog');
        backdrop.setAttribute('aria-modal', 'true');
        backdrop.setAttribute('aria-labelledby', `${id}-title`);
        backdrop.setAttribute('aria-describedby', `${id}-desc`);

        // Modal container box
        const card = document.createElement('div');
        card.className = `ws-modal-card ${type === 'prompt' || type === 'custom' ? 'ws-modal-wide' : ''}`;

        // Top drag handle for mobile bottom sheet
        const handle = document.createElement('div');
        handle.className = 'ws-modal-drag-handle';
        card.appendChild(handle);

        // Header / Icon row
        const bodyContent = document.createElement('div');
        bodyContent.className = 'ws-modal-body';

        let promptHtml = '';
        if (type === 'prompt' && prompt) {
            const inputId = `${id}-prompt-input`;
            promptHtml = `
                <div class="ws-modal-prompt-group" style="margin-top: 16px;">
                    ${prompt.label ? `<label for="${inputId}" class="ws-modal-label">${escapeHtml(prompt.label)}</label>` : ''}
                    ${prompt.multiline
                        ? `<textarea id="${inputId}" class="form-textarea w-full" rows="3" placeholder="${escapeHtml(prompt.placeholder || '')}">${escapeHtml(prompt.defaultValue || '')}</textarea>`
                        : `<input type="text" id="${inputId}" class="form-input w-full" value="${escapeHtml(prompt.defaultValue || '')}" placeholder="${escapeHtml(prompt.placeholder || '')}">`
                    }
                    <div id="${id}-prompt-err" class="ws-modal-input-error hidden"></div>
                </div>
            `;
        }

        let matchInputHtml = '';
        if (requiredMatch) {
            matchInputHtml = `
                <div class="ws-modal-match-group" style="margin-top: 16px;">
                    <label class="ws-modal-label">Please type <strong>${escapeHtml(requiredMatch)}</strong> to confirm:</label>
                    <input type="text" id="${id}-match-input" class="form-input w-full" autocomplete="off" placeholder="${escapeHtml(requiredMatch)}">
                </div>
            `;
        }

        let autoCloseBarHtml = '';
        if (autoCloseMs > 0) {
            autoCloseBarHtml = `
                <div class="ws-modal-countdown-track">
                    <div class="ws-modal-countdown-bar" style="animation-duration: ${autoCloseMs}ms;"></div>
                </div>
            `;
        }

        bodyContent.innerHTML = `
            <div class="ws-modal-header-row">
                <div class="ws-modal-icon-bubble ws-icon-${variant}">
                    ${iconSvg}
                </div>
                <div class="ws-modal-titles">
                    <h3 id="${id}-title" class="ws-modal-title">${escapeHtml(title)}</h3>
                    ${message ? `<div id="${id}-desc" class="ws-modal-message">${message}</div>` : ''}
                </div>
            </div>
            ${contentHTML ? `<div class="ws-modal-custom-content">${contentHTML}</div>` : ''}
            ${promptHtml}
            ${matchInputHtml}
            ${autoCloseBarHtml}
        `;
        card.appendChild(bodyContent);

        // Footer buttons
        const footer = document.createElement('div');
        footer.className = 'ws-modal-footer';

        if (actions && Array.isArray(actions) && actions.length > 0) {
            actions.forEach(act => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = `btn btn-sm ${act.className || 'btn-secondary'}`;
                btn.textContent = act.text;
                btn.onclick = () => {
                    act.onClick ? act.onClick(closeModal) : closeModal(act.value);
                };
                footer.appendChild(btn);
            });
        } else if (type === 'loading') {
            // Loading has no buttons
            footer.style.display = 'none';
        } else {
            if (showCancel) {
                const cancelBtn = document.createElement('button');
                cancelBtn.type = 'button';
                cancelBtn.id = `${id}-cancel`;
                cancelBtn.className = 'btn btn-secondary btn-sm';
                cancelBtn.textContent = cancelText;
                cancelBtn.onclick = () => closeModal(type === 'prompt' ? null : false);
                footer.appendChild(cancelBtn);
            }

            const confirmBtn = document.createElement('button');
            confirmBtn.type = 'button';
            confirmBtn.id = `${id}-confirm`;
            confirmBtn.className = `btn btn-sm ${variant === 'danger' ? 'btn-danger' : 'btn-primary'}`;
            confirmBtn.textContent = confirmText;

            if (requiredMatch) {
                confirmBtn.disabled = true;
            }

            confirmBtn.onclick = () => {
                if (type === 'prompt' && prompt) {
                    const inp = document.getElementById(`${id}-prompt-input`);
                    const val = inp ? inp.value.trim() : '';
                    if (prompt.required && !val) {
                        const err = document.getElementById(`${id}-prompt-err`);
                        if (err) {
                            err.textContent = 'This field is required.';
                            err.classList.remove('hidden');
                        }
                        inp.focus();
                        return;
                    }
                    if (typeof prompt.validate === 'function') {
                        const vRes = prompt.validate(val);
                        if (vRes !== true) {
                            const err = document.getElementById(`${id}-prompt-err`);
                            if (err) {
                                err.textContent = typeof vRes === 'string' ? vRes : 'Invalid entry.';
                                err.classList.remove('hidden');
                            }
                            inp.focus();
                            return;
                        }
                    }
                    closeModal(val);
                } else {
                    closeModal(true);
                }
            };

            footer.appendChild(confirmBtn);
        }

        card.appendChild(footer);
        backdrop.appendChild(card);
        root.appendChild(backdrop);

        // Lock page scroll
        document.body.classList.add('ws-modal-locked');

        // Required match input handler
        if (requiredMatch) {
            const matchInput = document.getElementById(`${id}-match-input`);
            const confirmBtn = document.getElementById(`${id}-confirm`);
            if (matchInput && confirmBtn) {
                matchInput.addEventListener('input', () => {
                    confirmBtn.disabled = (matchInput.value.trim() !== requiredMatch.trim());
                });
            }
        }

        // Auto close timer
        let autoCloseTimer = null;
        if (autoCloseMs > 0) {
            autoCloseTimer = setTimeout(() => {
                closeModal(true);
            }, autoCloseMs);

            card.addEventListener('mouseenter', () => {
                if (autoCloseTimer) clearTimeout(autoCloseTimer);
            });
            card.addEventListener('mouseleave', () => {
                autoCloseTimer = setTimeout(() => {
                    closeModal(true);
                }, 1500);
            });
        }

        // Close modal function
        function closeModal(result) {
            if (autoCloseTimer) clearTimeout(autoCloseTimer);
            backdrop.classList.add('ws-modal-closing');
            setTimeout(() => {
                backdrop.remove();
                document.body.classList.remove('ws-modal-locked');
                if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
                    lastFocusedElement.focus();
                }
                activeModalInstance = null;
                resolve(result);
                processQueue();
            }, 180);
        }

        // Handle backdrop click
        if (closeOnBackdrop) {
            backdrop.addEventListener('click', (e) => {
                if (e.target === backdrop) {
                    closeModal(false);
                }
            });
        }

        // Esc key and focus trap
        const focusableElements = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
        function handleKeyDown(e) {
            if (e.key === 'Escape' && type !== 'loading') {
                if (variant === 'danger' && requiredMatch) {
                    // Critical modal; cancel via button only or explicit esc
                    closeModal(false);
                } else {
                    closeModal(type === 'prompt' ? null : false);
                }
                document.removeEventListener('keydown', handleKeyDown);
                return;
            }

            if (e.key === 'Tab') {
                const focusables = card.querySelectorAll(focusableElements);
                if (focusables.length === 0) return;
                const first = focusables[0];
                const last = focusables[focusables.length - 1];

                if (e.shiftKey) {
                    if (document.activeElement === first) {
                        last.focus();
                        e.preventDefault();
                    }
                } else {
                    if (document.activeElement === last) {
                        first.focus();
                        e.preventDefault();
                    }
                }
            }
        }
        document.addEventListener('keydown', handleKeyDown);

        // Initial Focus placement
        setTimeout(() => {
            if (type === 'prompt') {
                const inp = document.getElementById(`${id}-prompt-input`);
                if (inp) {
                    inp.focus();
                    if (inp.select) inp.select();
                }
            } else if (variant === 'danger') {
                const cancel = document.getElementById(`${id}-cancel`);
                if (cancel) cancel.focus();
                else {
                    const matchInput = document.getElementById(`${id}-match-input`);
                    if (matchInput) matchInput.focus();
                }
            } else {
                const confirmBtn = document.getElementById(`${id}-confirm`);
                if (confirmBtn) confirmBtn.focus();
            }
        }, 30);

        // Loading handle
        if (type === 'loading') {
            return {
                close: () => closeModal(true)
            };
        }
    }

    /**
     * Enqueue a modal request
     */
    function enqueueModal(options) {
        return new Promise((resolve, reject) => {
            const item = { ...options, resolve, reject };
            modalQueue.push(item);
            processQueue();
        });
    }

    // =========================================================================
    // Public JS API: WS.modal
    // =========================================================================
    window.WS.modal = {
        alert: function (opts) {
            if (typeof opts === 'string') opts = { message: opts };
            return enqueueModal({
                type: 'info',
                variant: opts.variant || 'info',
                title: opts.title || 'Notification',
                message: opts.message || '',
                confirmText: opts.buttonText || 'OK'
            });
        },

        success: function (opts) {
            if (typeof opts === 'string') opts = { message: opts };
            return enqueueModal({
                type: 'success',
                variant: 'success',
                title: opts.title || 'Success',
                message: opts.message || '',
                confirmText: opts.buttonText || 'OK',
                autoCloseMs: opts.autoCloseMs !== undefined ? opts.autoCloseMs : 3500
            });
        },

        error: function (opts) {
            if (typeof opts === 'string') opts = { message: opts };
            return enqueueModal({
                type: 'error',
                variant: 'error',
                title: opts.title || 'Error',
                message: opts.message || 'An unexpected error occurred.',
                confirmText: opts.buttonText || 'Close'
            });
        },

        warning: function (opts) {
            if (typeof opts === 'string') opts = { message: opts };
            return enqueueModal({
                type: 'warning',
                variant: 'warning',
                title: opts.title || 'Warning',
                message: opts.message || '',
                confirmText: opts.buttonText || 'Understood'
            });
        },

        info: function (opts) {
            if (typeof opts === 'string') opts = { message: opts };
            return enqueueModal({
                type: 'info',
                variant: 'info',
                title: opts.title || 'Notice',
                message: opts.message || '',
                confirmText: opts.buttonText || 'OK'
            });
        },

        confirm: function (opts) {
            if (typeof opts === 'string') opts = { message: opts };
            return enqueueModal({
                type: 'confirm',
                variant: opts.variant || 'confirm',
                title: opts.title || 'Please Confirm',
                message: opts.message || '',
                confirmText: opts.confirmText || (opts.variant === 'danger' ? 'Delete' : 'Confirm'),
                cancelText: opts.cancelText || 'Cancel',
                requiredMatch: opts.requiredMatch || ''
            });
        },

        prompt: function (opts) {
            if (typeof opts === 'string') opts = { message: opts };
            return enqueueModal({
                type: 'prompt',
                variant: 'confirm',
                title: opts.title || 'Input Required',
                message: opts.message || '',
                confirmText: opts.confirmText || 'Submit',
                cancelText: opts.cancelText || 'Cancel',
                prompt: {
                    label: opts.label || '',
                    placeholder: opts.placeholder || '',
                    defaultValue: opts.defaultValue || '',
                    multiline: Boolean(opts.multiline),
                    required: opts.required !== false,
                    validate: opts.validate || null
                }
            });
        },

        loading: function (opts) {
            if (typeof opts === 'string') opts = { message: opts };
            let closeHandle = null;
            new Promise((resolve) => {
                const item = {
                    type: 'loading',
                    variant: 'loading',
                    title: opts.title || 'Please Wait',
                    message: opts.message || 'Processing your request...',
                    resolve
                };
                closeHandle = renderModal(item);
            });
            return closeHandle || { close: () => {} };
        },

        custom: function (opts) {
            return enqueueModal({
                type: 'custom',
                variant: opts.variant || 'info',
                title: opts.title || '',
                contentHTML: opts.contentHTML || '',
                actions: opts.actions || null
            });
        }
    };

    // Compatibility shim: replace legacy clayConfirm
    window.clayConfirm = function (message, onConfirm) {
        window.WS.modal.confirm({
            title: 'Please Confirm',
            message: message,
            confirmText: 'Confirm',
            variant: 'danger'
        }).then(ok => {
            if (ok && typeof onConfirm === 'function') onConfirm();
        });
    };

    // =========================================================================
    // Declarative Event Delegation: data-confirm-*
    // =========================================================================
    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('[data-confirm-message], [data-confirm-title]');
        if (!trigger) return;

        // If it's a submit button inside a form, handle in submit listener
        if (trigger.tagName === 'BUTTON' && trigger.type === 'submit' && trigger.form) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        const title = trigger.dataset.confirmTitle || 'Please Confirm';
        const message = trigger.dataset.confirmMessage || 'Are you sure you want to proceed?';
        const confirmText = trigger.dataset.confirmText || 'Confirm';
        const cancelText = trigger.dataset.confirmCancel || 'Cancel';
        const variant = trigger.dataset.confirmVariant || 'confirm';
        const match = trigger.dataset.confirmMatch || '';

        window.WS.modal.confirm({
            title,
            message,
            confirmText,
            cancelText,
            variant,
            requiredMatch: match
        }).then(confirmed => {
            if (confirmed) {
                // If it's an anchor, navigate
                if (trigger.tagName === 'A' && trigger.href) {
                    window.location.href = trigger.href;
                } else if (trigger.onclick) {
                    trigger.onclick();
                }
            }
        });
    }, true);

    document.addEventListener('submit', function (e) {
        const form = e.target;
        // Check form itself or active submit button
        const submitBtn = e.submitter || form.querySelector('[type="submit"]:focus') || form.querySelector('[type="submit"]');
        const trigger = (submitBtn && submitBtn.hasAttribute('data-confirm-message')) ? submitBtn : (form.hasAttribute('data-confirm-message') ? form : null);

        if (!trigger || form.dataset.wsConfirmed === 'true') {
            return; // let form proceed
        }

        e.preventDefault();
        e.stopPropagation();

        const title = trigger.dataset.confirmTitle || 'Please Confirm';
        const message = trigger.dataset.confirmMessage || 'Are you sure you want to submit this?';
        const confirmText = trigger.dataset.confirmText || 'Confirm';
        const cancelText = trigger.dataset.confirmCancel || 'Cancel';
        const variant = trigger.dataset.confirmVariant || 'confirm';
        const match = trigger.dataset.confirmMatch || '';

        window.WS.modal.confirm({
            title,
            message,
            confirmText,
            cancelText,
            variant,
            requiredMatch: match
        }).then(confirmed => {
            if (confirmed) {
                form.dataset.wsConfirmed = 'true';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('btn-loading');
                }
                form.submit();
            }
        });
    }, true);

    // =========================================================================
    // Server-Side Flash Messages Bootstrapper
    // =========================================================================
    document.addEventListener('DOMContentLoaded', function () {
        const flashEl = document.getElementById('ws-flash-data');
        if (!flashEl) return;

        try {
            const raw = flashEl.getAttribute('data-flash');
            if (!raw) return;
            const data = JSON.parse(raw);

            if (data.upgrade_prompt) {
                window.WS.modal.warning({
                    title: 'WorkShift Pro Feature',
                    message: data.upgrade_prompt + `<br><div class="mt-3"><a href="/settings?upgrade=1" class="btn btn-primary btn-sm">Upgrade to Pro (₱499/mo)</a></div>`,
                    buttonText: 'Close'
                });
            } else if (data.error) {
                window.WS.modal.error({
                    title: 'Action Failed',
                    message: data.error,
                    buttonText: 'Close'
                });
            } else if (data.success) {
                window.WS.modal.success({
                    title: 'Success',
                    message: data.success,
                    buttonText: 'Continue'
                });
            } else if (data.warning) {
                window.WS.modal.warning({
                    title: 'Notice',
                    message: data.warning,
                    buttonText: 'Understood'
                });
            } else if (data.info) {
                window.WS.modal.info({
                    title: 'Information',
                    message: data.info,
                    buttonText: 'OK'
                });
            }
        } catch (err) {
            console.error('Error parsing flash modal payload:', err);
        }
    });

    // =========================================================================
    // Unsaved Changes Form Tracker
    // =========================================================================
    let isFormDirty = false;

    document.addEventListener('DOMContentLoaded', function () {
        const forms = document.querySelectorAll('form.dirty-check, form[data-dirty-check]');
        forms.forEach(form => {
            form.addEventListener('input', () => {
                isFormDirty = true;
            });
            form.addEventListener('submit', () => {
                isFormDirty = false;
            });
        });

        // Intercept internal links when dirty
        document.addEventListener('click', function (e) {
            if (!isFormDirty) return;
            const link = e.target.closest('a[href]:not([target="_blank"]):not([download])');
            if (!link || link.getAttribute('href').startsWith('#') || link.getAttribute('href').startsWith('javascript:')) return;

            e.preventDefault();
            e.stopPropagation();

            window.WS.modal.confirm({
                title: 'Discard changes?',
                message: 'You have unsaved edits on this page. If you leave now, your changes will be lost.',
                confirmText: 'Discard',
                cancelText: 'Stay',
                variant: 'warning'
            }).then(discard => {
                if (discard) {
                    isFormDirty = false;
                    window.location.href = link.href;
                }
            });
        });

        // Browser beforeunload fallback
        window.addEventListener('beforeunload', function (e) {
            if (isFormDirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    });

    // =========================================================================
    // Global Fetch / AJAX Failure Interceptor
    // =========================================================================
    const originalFetch = window.fetch;
    window.fetch = async function (...args) {
        try {
            const response = await originalFetch(...args);
            if (response.status === 401) {
                window.WS.modal.custom({
                    variant: 'warning',
                    title: 'Session Expired',
                    contentHTML: `<p class="text-xs text-muted mb-4">Your login session has timed out. Please sign in again to continue your work.</p>`,
                    actions: [
                        {
                            text: 'Log In Again',
                            className: 'btn-primary',
                            onClick: () => { window.location.href = '/login'; }
                        }
                    ]
                });
            } else if (response.status === 403) {
                window.WS.modal.error({
                    title: 'Access Forbidden',
                    message: 'You do not have permission to perform this action.'
                });
            } else if (response.status === 500) {
                window.WS.modal.error({
                    title: 'Server Error',
                    message: 'The server encountered an error processing your request. Please try again shortly.'
                });
            }
            return response;
        } catch (networkError) {
            window.WS.modal.error({
                title: 'Network Error',
                message: 'Unable to communicate with the server. Please check your internet connection.'
            });
            throw networkError;
        }
    };

    function getBaseUrl() {
        if (window.__BASE_URL__ !== undefined && window.__BASE_URL__ !== null) {
            return window.__BASE_URL__.replace(/\/+$/, '');
        }
        const match = window.location.pathname.match(/^(\/[^\/]+\/public)/i);
        if (match && match[1]) {
            return match[1].replace(/\/+$/, '');
        }
        return '';
    }

    // Logout Confirmation Handler
    window.WS.confirmLogout = function (event) {
        if (event) event.preventDefault();
        window.WS.modal.confirm({
            title: 'Log out of WorkShift?',
            message: 'You will need to sign in again to access your clients and projects.',
            confirmText: 'Log out',
            cancelText: 'Cancel',
            variant: 'danger'
        }).then(confirmed => {
            if (confirmed) {
                const baseUrl = getBaseUrl();
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = baseUrl + '/logout';
                const csrf = window.__CSRF_TOKEN__ || document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_csrf_token"]')?.value || '';
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_csrf_token';
                csrfInput.value = csrf;
                form.appendChild(csrfInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
        return false;
    };

})(window, document);
