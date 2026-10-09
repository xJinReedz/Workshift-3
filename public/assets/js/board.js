/**
 * WorkShift Board & Kanban Canvas (SortableJS + WS.modal Stage Engine)
 * Product: WorkShift
 */

document.addEventListener('DOMContentLoaded', function () {
    initBoardSortables();
    initMobileStageTabs();
});

function initBoardSortables() {
    const cardContainers = document.querySelectorAll('.task-cards-list, .board-task-list');
    if (!cardContainers.length || typeof Sortable === 'undefined') return;

    cardContainers.forEach(container => {
        new Sortable(container, {
            group: 'board-tasks',
            animation: 180,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            delay: 150,
            delayOnTouchOnly: true,
            touchStartThreshold: 5,
            onEnd: async function (evt) {
                const itemEl = evt.item;
                const taskId = itemEl.getAttribute('data-task-id');
                const targetStageId = evt.to.getAttribute('data-stage-id');
                const newIndex = evt.newIndex;

                if (!taskId || !targetStageId) return;

                try {
                    await apiFetch(`/tasks/${taskId}/move`, {
                        method: 'POST',
                        body: {
                            stage_id: targetStageId,
                            position: newIndex
                        }
                    });
                } catch (err) {
                    console.error('Task move failed:', err);
                }
            }
        });
    });
}

async function moveTaskToStage(taskId, stageId) {
    if (!taskId || !stageId) return;
    try {
        const res = await apiFetch(`/tasks/${taskId}/move`, {
            method: 'POST',
            body: { stage_id: stageId, position: 999 }
        });
        if (res.ok || res.success) {
            window.location.reload();
        }
    } catch (err) {
        console.error('Task move failed:', err);
    }
}

function initMobileStageTabs() {
    const tabs = document.querySelectorAll('.board-mobile-tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function () {
            const stageId = this.dataset.stageId;
            const col = document.getElementById(`stage-col-${stageId}`);
            if (col) {
                col.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });
}

async function submitQuickTask(event, boardId, stageId) {
    event.preventDefault();
    const form = event.target;
    const input = form.querySelector('input[name="title"], textarea[name="title"]');
    const title = input.value.trim();
    if (!title) return;

    try {
        const data = await apiFetch('/tasks', {
            method: 'POST',
            body: { board_id: boardId, stage_id: stageId, title: title }
        });
        if (data.ok || data.success) {
            window.location.reload();
        }
    } catch (err) {
        console.error('Task creation failed:', err);
    }
}

async function submitBlockerForm(event, taskId) {
    event.preventDefault();
    const type = document.getElementById('blocker-type').value;
    const waitingOn = document.getElementById('blocker-waiting-on').value;
    const reason = document.getElementById('blocker-reason').value;

    try {
        const data = await apiFetch(`/tasks/${taskId}/blocker`, {
            method: 'POST',
            body: { type, waiting_on: waitingOn, reason }
        });
        if (data.ok || data.success) {
            openTaskSlideover(taskId);
        }
    } catch (err) {
        console.error('Blocker update failed:', err);
    }
}

async function submitComment(event, taskId, portalToken = '') {
    event.preventDefault();
    const form = event.target;
    const textarea = form.querySelector('textarea[name="comment"]');
    const content = textarea.value.trim();
    if (!content) return;

    try {
        const data = await apiFetch(`/tasks/${taskId}/comment`, {
            method: 'POST',
            body: { content, portal_token: portalToken }
        });
        if (data.ok || data.success) {
            textarea.value = '';
            const container = document.getElementById(`comments-container-${taskId}`);
            const noMsg = document.getElementById('no-comments-msg');
            if (noMsg) noMsg.remove();

            const c = data.comment || data.data?.comment;
            if (c) {
                const bubble = document.createElement('div');
                bubble.className = `panel p-3 mb-2`;
                bubble.style.background = 'var(--color-bg-surface)';
                bubble.style.border = `1px solid ${c.is_client ? 'var(--color-brand)' : 'var(--color-border)'}`;
                bubble.style.borderRadius = 'var(--radius-sm)';
                bubble.innerHTML = `
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <strong class="text-xs font-bold text-primary">${c.author_name} (${c.is_client ? 'Client' : 'Freelancer'})</strong>
                        <span class="text-xs text-subtle">${c.created_at}</span>
                    </div>
                    <div class="text-xs text-subtle leading-relaxed">${c.content}</div>
                `;
                container.appendChild(bubble);
                container.scrollTop = container.scrollHeight;
            }
        }
    } catch (err) {
        console.error('Comment submission failed:', err);
    }
}

async function uploadTaskFile(event, taskId, portalToken = '') {
    const file = event.target.files[0];
    if (!file) return;

    const loadingHandle = window.WS.modal.loading({
        title: 'Uploading File',
        message: `Uploading "${file.name}"...`
    });

    const formData = new FormData();
    formData.append('file', file);
    if (portalToken) {
        formData.append('portal_token', portalToken);
    }

    try {
        const data = await apiFetch(`/tasks/${taskId}/upload`, {
            method: 'POST',
            body: formData
        });
        loadingHandle.close();
        if (data.ok || data.success) {
            window.WS.modal.success({
                title: 'File Uploaded',
                message: `"${file.name}" was uploaded successfully.`
            });
            openTaskSlideover(taskId, portalToken);
        }
    } catch (err) {
        loadingHandle.close();
        console.error('Upload error:', err);
    }
}

function deleteTaskFile(fileId) {
    window.WS.modal.confirm({
        title: 'Delete File Attachment?',
        message: 'Are you sure you want to delete this file deliverable? This cannot be undone.',
        confirmText: 'Delete File',
        variant: 'danger'
    }).then(async confirmed => {
        if (!confirmed) return;
        try {
            const data = await apiFetch(`/files/${fileId}`, {
                method: 'DELETE'
            });
            if (data.ok || data.success) {
                const el = document.getElementById(`file-item-${fileId}`);
                if (el) el.remove();
            }
        } catch (err) {
            console.error('Delete file error:', err);
        }
    });
}

/**
 * Stage Management via WS.modal System (Independent Stage Creation)
 */
function showAddStagePrompt(boardId) {
    const html = `
        <form id="ws-add-stage-form" onsubmit="return false;">
            <div class="mb-4">
                <label class="block text-xs font-semibold text-primary mb-1">Quick Presets</label>
                <div class="flex items-center gap-2 flex-wrap mb-2">
                    <button type="button" class="clay-btn clay-btn-ghost clay-btn-sm" onclick="applyStagePreset('To Do', '#4C9AFF', false)">To Do</button>
                    <button type="button" class="clay-btn clay-btn-ghost clay-btn-sm" onclick="applyStagePreset('In Progress', '#F5CD47', false)">In Progress</button>
                    <button type="button" class="clay-btn clay-btn-ghost clay-btn-sm" onclick="applyStagePreset('In Review', '#9F8FEF', true)">In Review</button>
                    <button type="button" class="clay-btn clay-btn-ghost clay-btn-sm" onclick="applyStagePreset('Done', '#57D9A3', false, true)">Done</button>
                </div>
            </div>
            <div class="mb-4">
                <label for="ws-stage-title-input" class="block text-xs font-semibold text-primary mb-1">Stage Column Name *</label>
                <input type="text" id="ws-stage-title-input" class="clay-input w-full" placeholder="e.g. Client Feedback, Final Polish..." required autofocus>
            </div>
            <div class="mb-4">
                <label class="block text-xs font-semibold text-primary mb-1">Column Accent Color</label>
                <div class="flex items-center gap-2">
                    <input type="color" id="ws-stage-color-input" value="#4C9AFF" style="width: 36px; height: 36px; padding: 0; border: none; cursor: pointer; border-radius: 4px;">
                    <span class="text-xs text-muted">Lane theme color</span>
                </div>
            </div>
            <div class="p-3 mb-4 rounded" style="background: var(--color-bg-surface-sunken); border: 1px solid var(--color-border);">
                <label class="flex items-start gap-2 cursor-pointer">
                    <input type="checkbox" id="ws-stage-review-toggle" class="mt-1" style="width: 16px; height: 16px;">
                    <div>
                        <strong class="text-xs font-bold text-primary block">Ask the client to review tasks in this stage (optional)</strong>
                        <span class="text-xs text-subtle block leading-normal mt-0.5">
                            When enabled, tasks in this column will show Approve and Request Changes buttons to the client in their portal.
                        </span>
                    </div>
                </label>
            </div>
            <div id="ws-add-stage-error" class="text-xs text-danger mb-3 hidden"></div>
        </form>
    `;

    window.WS.modal.custom({
        title: 'Add Stage Column',
        contentHTML: html,
        actions: [
            {
                text: 'Cancel',
                className: 'clay-btn-ghost',
                onClick: (close) => close()
            },
            {
                text: 'Create Stage',
                className: 'clay-btn-primary',
                onClick: async (close) => {
                    const titleInput = document.getElementById('ws-stage-title-input');
                    const colorInput = document.getElementById('ws-stage-color-input');
                    const reviewToggle = document.getElementById('ws-stage-review-toggle');
                    const errorEl = document.getElementById('ws-add-stage-error');

                    const title = titleInput ? titleInput.value.trim() : '';
                    const color = colorInput ? colorInput.value : '#4C9AFF';
                    const clientReview = reviewToggle ? reviewToggle.checked : false;

                    if (!title) {
                        if (errorEl) {
                            errorEl.textContent = 'Please enter a stage name.';
                            errorEl.classList.remove('hidden');
                        }
                        return;
                    }

                    try {
                        const data = await apiFetch(`/boards/${boardId}/stages`, {
                            method: 'POST',
                            body: {
                                title: title,
                                color: color,
                                client_review: clientReview ? 1 : 0
                            }
                        });
                        if (data.ok || data.success) {
                            close();
                            window.location.reload();
                        }
                    } catch (err) {
                        if (errorEl) {
                            errorEl.textContent = err.message || 'Failed to create stage.';
                            errorEl.classList.remove('hidden');
                        }
                    }
                }
            }
        ]
    });
}

function applyStagePreset(name, color, isReview = false, isDone = false) {
    const titleInput = document.getElementById('ws-stage-title-input');
    const colorInput = document.getElementById('ws-stage-color-input');
    const reviewToggle = document.getElementById('ws-stage-review-toggle');

    if (titleInput) titleInput.value = name;
    if (colorInput) colorInput.value = color;
    if (reviewToggle) reviewToggle.checked = isReview;
}

function showDeleteStagePrompt(boardId, stageId, stageTitle, taskCount, otherStages) {
    let selectHtml = '';
    if (taskCount > 0 && otherStages && otherStages.length) {
        selectHtml = `
            <div class="mb-4 text-left">
                <label class="block text-xs font-semibold text-primary mb-1">Destination Stage for Existing ${taskCount} Task(s) *</label>
                <select id="ws-dest-stage-select" class="clay-select w-full">
                    ${otherStages.map(s => `<option value="${s.id}">${s.title}</option>`).join('')}
                </select>
            </div>
        `;
    }

    const html = `
        <div class="text-center p-2">
            <p class="text-xs text-muted mb-4">
                Are you sure you want to delete stage column "<strong>${stageTitle}</strong>"?
                ${taskCount > 0 ? `This stage currently contains <strong>${taskCount} task(s)</strong>.` : ''}
            </p>
            ${selectHtml}
            <div id="ws-delete-stage-error" class="text-xs text-danger mb-2 hidden"></div>
        </div>
    `;

    window.WS.modal.custom({
        variant: 'danger',
        title: 'Delete Stage Column?',
        contentHTML: html,
        actions: [
            {
                text: 'Cancel',
                className: 'clay-btn-ghost',
                onClick: (close) => close()
            },
            {
                text: 'Delete Stage',
                className: 'clay-btn-danger',
                onClick: async (close) => {
                    const destSelect = document.getElementById('ws-dest-stage-select');
                    const destStageId = destSelect ? destSelect.value : null;

                    try {
                        const data = await apiFetch(`/boards/${boardId}/stages/${stageId}`, {
                            method: 'DELETE',
                            body: { destination_stage_id: destStageId }
                        });
                        if (data.ok || data.success) {
                            close();
                            window.location.reload();
                        }
                    } catch (err) {
                        const errEl = document.getElementById('ws-delete-stage-error');
                        if (errEl) {
                            errEl.textContent = err.message || 'Failed to delete stage.';
                            errEl.classList.remove('hidden');
                        }
                    }
                }
            }
        ]
    });
}
