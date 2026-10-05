/**
 * WorkShift Board & Kanban Drag-and-Drop (SortableJS)
 * Features: Touch-optimized dragging, Non-drag "Move to..." fallback,
 * Mobile snap-scroll stage switching, Quick task creation, Blocker updates,
 * Task comments & file management using WS.modal.
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
            onEnd: function (evt) {
                const itemEl = evt.item;
                const taskId = itemEl.getAttribute('data-task-id');
                const targetStageId = evt.to.getAttribute('data-stage-id');
                const newIndex = evt.newIndex;

                if (!taskId || !targetStageId) return;

                fetch(`/tasks/${taskId}/move`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        stage_id: targetStageId,
                        position: newIndex,
                        _csrf_token: csrfToken
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        window.WS.modal.error({
                            title: 'Could Not Move Task',
                            message: data.error || 'Failed to update task position on board.'
                        });
                    }
                })
                .catch(err => {
                    console.error('Task move error:', err);
                });
            }
        });
    });
}

/**
 * Fallback "Move to..." menu action for touch devices or users who prefer clicks
 */
function moveTaskToStage(taskId, stageId) {
    if (!taskId || !stageId) return;

    fetch(`/tasks/${taskId}/move`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            stage_id: stageId,
            position: 999,
            _csrf_token: csrfToken
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            window.WS.modal.error({
                title: 'Move Failed',
                message: data.error || 'Failed to move task.'
            });
        }
    })
    .catch(err => {
        window.WS.modal.error({
            title: 'Error Moving Task',
            message: err.message
        });
    });
}

/**
 * Mobile Stage Switcher Tabs (Scrolls horizontal canvas to target column)
 */
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

function submitQuickTask(event, boardId, stageId) {
    event.preventDefault();
    const form = event.target;
    const input = form.querySelector('input[name="title"], textarea[name="title"]');
    const title = input.value.trim();
    if (!title) return;

    fetch('/tasks', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            board_id: boardId,
            stage_id: stageId,
            title: title,
            _csrf_token: csrfToken
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            window.WS.modal.error({
                title: 'Task Creation Failed',
                message: data.error || 'Failed to create task'
            });
        }
    })
    .catch(err => {
        window.WS.modal.error({
            title: 'Network Error',
            message: err.message
        });
    });
}

function submitBlockerForm(event, taskId) {
    event.preventDefault();
    const type = document.getElementById('blocker-type').value;
    const waitingOn = document.getElementById('blocker-waiting-on').value;
    const reason = document.getElementById('blocker-reason').value;

    fetch(`/tasks/${taskId}/blocker`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            type: type,
            waiting_on: waitingOn,
            reason: reason,
            _csrf_token: csrfToken
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            openTaskSlideover(taskId);
        } else {
            window.WS.modal.error({
                title: 'Blocker Update Failed',
                message: data.error || 'Failed to update blocker status'
            });
        }
    })
    .catch(err => {
        window.WS.modal.error({
            title: 'Network Error',
            message: err.message
        });
    });
}

function submitComment(event, taskId, portalToken = '') {
    event.preventDefault();
    const form = event.target;
    const textarea = form.querySelector('textarea[name="comment"]');
    const content = textarea.value.trim();
    if (!content) return;

    fetch(`/tasks/${taskId}/comment`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            content: content,
            portal_token: portalToken,
            _csrf_token: csrfToken
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            textarea.value = '';
            const container = document.getElementById(`comments-container-${taskId}`);
            const noMsg = document.getElementById('no-comments-msg');
            if (noMsg) noMsg.remove();

            const bubble = document.createElement('div');
            bubble.className = `panel p-3 mb-2`;
            bubble.style.background = 'var(--color-bg-surface)';
            bubble.style.border = `1px solid ${data.comment.is_client ? 'var(--color-brand)' : 'var(--color-border)'}`;
            bubble.style.borderRadius = 'var(--radius-sm)';
            bubble.innerHTML = `
                <div class="flex items-center justify-between gap-2 mb-1">
                    <strong class="text-xs font-bold text-primary">${data.comment.author_name} (${data.comment.is_client ? 'Client' : 'Freelancer'})</strong>
                    <span class="text-xs text-subtle">${data.comment.created_at}</span>
                </div>
                <div class="text-xs text-subtle leading-relaxed">${data.comment.content}</div>
            `;
            container.appendChild(bubble);
            container.scrollTop = container.scrollHeight;
        } else {
            window.WS.modal.error({
                title: 'Comment Failed',
                message: data.error || 'Failed to post comment'
            });
        }
    })
    .catch(err => {
        window.WS.modal.error({
            title: 'Network Error',
            message: err.message
        });
    });
}

function uploadTaskFile(event, taskId, portalToken = '') {
    const file = event.target.files[0];
    if (!file) return;

    const loadingHandle = window.WS.modal.loading({
        title: 'Uploading File',
        message: `Uploading "${file.name}"...`
    });

    const formData = new FormData();
    formData.append('file', file);
    formData.append('_csrf_token', csrfToken);
    if (portalToken) {
        formData.append('portal_token', portalToken);
    }

    fetch(`/tasks/${taskId}/upload`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken
        },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        loadingHandle.close();
        if (data.success) {
            window.WS.modal.success({
                title: 'File Uploaded',
                message: `"${file.name}" was uploaded successfully.`
            });
            openTaskSlideover(taskId, portalToken);
        } else {
            window.WS.modal.error({
                title: 'Upload Failed',
                message: data.error || 'File upload failed.'
            });
        }
    })
    .catch(err => {
        loadingHandle.close();
        window.WS.modal.error({
            title: 'Upload Error',
            message: 'File upload failed: ' + err.message
        });
    });
}

function deleteTaskFile(fileId) {
    window.WS.modal.confirm({
        title: 'Delete File Attachment?',
        message: 'Are you sure you want to delete this file deliverable? This cannot be undone.',
        confirmText: 'Delete File',
        variant: 'danger'
    }).then(confirmed => {
        if (!confirmed) return;
        fetch(`/files/${fileId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                _method: 'DELETE',
                _csrf_token: csrfToken
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const el = document.getElementById(`file-item-${fileId}`);
                if (el) el.remove();
            } else {
                window.WS.modal.error({
                    title: 'Delete Failed',
                    message: data.error || 'Failed to delete file'
                });
            }
        })
        .catch(err => {
            window.WS.modal.error({
                title: 'Network Error',
                message: err.message
            });
        });
    });
}
