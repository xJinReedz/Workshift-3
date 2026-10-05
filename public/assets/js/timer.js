/**
 * WorkShift Built-in Time Tracker JS
 * Floating Top Bar Widget & Time Logging with WS.modal
 */

let timerInterval = null;
let activeTimerEntryId = null;
let timerStartTimestamp = null;

document.addEventListener('DOMContentLoaded', function () {
    checkActiveTimer();
});

function checkActiveTimer() {
    fetch('/time/active')
        .then(r => r.json())
        .then(data => {
            if (data.active) {
                startTimerWidget(data.task_title, data.started_at, data.entry_id);
            }
        })
        .catch(err => {
            console.log('Silent active timer check error:', err);
        });
}

function startTimerWidget(taskTitle, startedAt, entryId) {
    activeTimerEntryId = entryId;
    timerStartTimestamp = new Date(startedAt.replace(' ', 'T')).getTime();

    const widget = document.getElementById('global-timer-widget');
    const titleEl = document.getElementById('global-timer-title');
    const stopBtn = document.getElementById('global-timer-stop-btn');

    if (!widget || !titleEl) return;

    titleEl.innerText = taskTitle;
    titleEl.title = taskTitle;
    widget.classList.remove('hidden');

    if (stopBtn) {
        stopBtn.onclick = () => stopActiveTimer(entryId);
    }

    if (timerInterval) clearInterval(timerInterval);
    updateTimerDigits();
    timerInterval = setInterval(updateTimerDigits, 1000);
}

function stopTimerWidget() {
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = null;
    activeTimerEntryId = null;
    timerStartTimestamp = null;

    const widget = document.getElementById('global-timer-widget');
    if (widget) widget.classList.add('hidden');
}

function updateTimerDigits() {
    if (!timerStartTimestamp) return;

    const now = Date.now();
    const diffSec = Math.max(0, Math.floor((now - timerStartTimestamp) / 1000));

    const hours = Math.floor(diffSec / 3600);
    const mins = Math.floor((diffSec % 3600) / 60);
    const secs = diffSec % 60;

    const formatted = [
        hours.toString().padStart(2, '0'),
        mins.toString().padStart(2, '0'),
        secs.toString().padStart(2, '0')
    ].join(':');

    const displayEl = document.getElementById('global-timer-display');
    if (displayEl) {
        displayEl.innerText = formatted;
    }
}

function startTimerForTask(taskId, taskTitle, clientName) {
    if (activeTimerEntryId) {
        window.WS.modal.confirm({
            title: 'Timer Already Running',
            message: `A work timer is currently active. Would you like to stop it and switch tracking to "<strong>${taskTitle}</strong>"?`,
            confirmText: 'Switch Timer',
            cancelText: 'Keep Current',
            variant: 'warning'
        }).then(shouldSwitch => {
            if (shouldSwitch) {
                executeStartTimer(taskId, taskTitle);
            }
        });
        return;
    }

    executeStartTimer(taskId, taskTitle);
}

function executeStartTimer(taskId, taskTitle) {
    fetch('/time/start', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            task_id: taskId,
            _csrf_token: csrfToken
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            startTimerWidget(taskTitle, data.started_at, data.entry_id);
            if (typeof closeTaskSlideover === 'function') closeTaskSlideover();
            if (typeof closeTaskModal === 'function') closeTaskModal();
            window.WS.modal.success({
                title: 'Timer Started',
                message: `Now tracking time on "${taskTitle}".`,
                autoCloseMs: 2500
            });
        } else {
            window.WS.modal.error({
                title: 'Failed to Start Timer',
                message: data.error || 'Could not start work timer.'
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

function stopActiveTimer(entryId) {
    // Check if running over 8 hours (8 * 3600 = 28800 seconds)
    if (timerStartTimestamp) {
        const elapsedSec = Math.floor((Date.now() - timerStartTimestamp) / 1000);
        if (elapsedSec > 28800) {
            window.WS.modal.confirm({
                title: 'Long Session Detected',
                message: 'This timer has been running for over 8 hours. Are you sure you want to stop and record all recorded hours?',
                confirmText: 'Log All Hours',
                cancelText: 'Keep Running',
                variant: 'warning'
            }).then(confirmed => {
                if (confirmed) executeStopTimer(entryId);
            });
            return;
        }
    }

    executeStopTimer(entryId);
}

function executeStopTimer(entryId) {
    fetch('/time/stop', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            entry_id: entryId,
            _csrf_token: csrfToken
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            stopTimerWidget();
            window.WS.modal.success({
                title: 'Timer Saved',
                message: 'Logged work session successfully.',
                autoCloseMs: 2000
            }).then(() => {
                window.location.reload();
            });
        } else {
            window.WS.modal.error({
                title: 'Error Stopping Timer',
                message: data.error || 'Failed to stop timer.'
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

function submitManualTime(event, taskId) {
    event.preventDefault();
    const minutes = document.getElementById('time-minutes').value;
    const date = document.getElementById('time-date').value;
    const note = document.getElementById('time-note').value;
    const isPrivateEl = document.getElementById('time-private');
    const isPrivate = isPrivateEl ? isPrivateEl.checked : false;

    fetch('/time/manual', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            task_id: taskId,
            duration_minutes: minutes,
            entry_date: date,
            notes: note,
            is_private: isPrivate ? 1 : 0,
            _csrf_token: csrfToken
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (typeof openTaskSlideover === 'function') openTaskSlideover(taskId);
            else if (typeof openTaskModal === 'function') openTaskModal(taskId);
        } else {
            window.WS.modal.error({
                title: 'Time Logging Failed',
                message: data.error || 'Failed to log time entry.'
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

function deleteTimeEntry(entryId) {
    window.WS.modal.confirm({
        title: 'Delete Time Entry?',
        message: 'Are you sure you want to delete this logged work session? This cannot be undone.',
        confirmText: 'Delete Entry',
        variant: 'danger'
    }).then(confirmed => {
        if (!confirmed) return;

        fetch(`/time/${entryId}`, {
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
                const el = document.getElementById(`time-row-${entryId}`);
                if (el) el.remove();
            } else {
                window.WS.modal.error({
                    title: 'Delete Failed',
                    message: data.error || 'Failed to delete time entry'
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
