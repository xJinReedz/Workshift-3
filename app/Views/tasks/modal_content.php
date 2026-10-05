<div class="task-modal-wrapper p-6" data-task-id="<?= $task['id'] ?>">
    <!-- Top Meta Row -->
    <div class="flex items-center justify-between flex-wrap gap-3 pb-4 mb-5" style="border-bottom: 1px solid var(--border-subtle);">
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs text-muted font-bold uppercase" style="letter-spacing: 0.04em;">Stage</span>
            <span class="lozenge lozenge-primary font-bold"><?= $this->e($task['stage_title']) ?></span>
            <?php if (!empty($task['is_review_stage'])): ?>
                <span class="lozenge lozenge-warning font-bold">Client Review Active</span>
            <?php endif; ?>
        </div>

        <div class="flex items-center gap-4 text-xs font-semibold text-secondary">
            <div>
                <span class="text-muted">Due:</span>
                <span class="text-primary font-semibold"><?= !empty($task['due_date']) ? date('M j, Y', strtotime($task['due_date'])) : 'None' ?></span>
            </div>
            <div>
                <span class="text-muted">Client:</span>
                <strong class="text-primary"><?= $this->e($task['client_name']) ?></strong>
            </div>
        </div>
    </div>

    <!-- Task Description Box -->
    <div class="mb-5">
        <div class="flex items-center justify-between mb-2">
            <h4 class="font-heading text-xs font-bold uppercase tracking-wider text-muted m-0">Description & Scope</h4>
            <?php if (!$isClient): ?>
                <button type="button" class="btn btn-ghost btn-sm text-xs py-1" onclick="toggleEditDesc(true)">Edit Scope</button>
            <?php endif; ?>
        </div>
        <?php if (!$isClient): ?>
            <div id="desc-view" class="p-3 text-sm cursor-pointer rounded transition" onclick="toggleEditDesc(true)" style="background: var(--surface-well); border: 1px solid var(--border-subtle); line-height: 1.5;">
                <?= !empty($task['description']) ? nl2br($this->e($task['description'])) : '<span class="text-muted italic">Click to add deliverable scope, requirements, or client instructions...</span>' ?>
            </div>
            <div id="desc-edit" class="hidden">
                <textarea id="task-desc-input" class="form-textarea w-full" rows="3"><?= $this->e($task['description'] ?? '') ?></textarea>
                <div class="flex items-center justify-end gap-2 mt-2">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="toggleEditDesc(false)">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="saveTaskDescription(<?= $task['id'] ?>)">Save Scope</button>
                </div>
            </div>
        <?php else: ?>
            <div class="p-3 text-sm rounded" style="background: var(--surface-well); border: 1px solid var(--border-subtle); line-height: 1.5;">
                <?= !empty($task['description']) ? nl2br($this->e($task['description'])) : '<span class="text-muted italic">No additional description provided.</span>' ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- SIGNATURE BLOCKER SECTION -->
    <div class="panel p-4 mb-5" style="border: 1px solid var(--border-outline); background: var(--surface-tray); border-radius: var(--radius-md);">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
            <div>
                <h4 class="font-heading text-xs font-bold uppercase tracking-wider text-primary m-0">Task Blocker Status</h4>
                <p class="text-xs text-muted m-0 mt-0.5">Visibility: shows why work is paused and who it's waiting on.</p>
            </div>
            <?php if (!$isClient): ?>
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleBlockerForm()">
                    <?= clay_icon('edit', 14) ?>
                    <span><?= !empty($task['blocker_type']) ? 'Edit Blocker' : '+ Add Blocker' ?></span>
                </button>
            <?php endif; ?>
        </div>

        <!-- Active Blocker Display -->
        <div id="active-blocker-display">
            <?php if (!empty($task['blocker_type'])): ?>
                <div class="panel p-3 mb-2" style="background: var(--surface-card); border: 1px solid var(--border-subtle);">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <?= blocker_badge([
                            'type' => $task['blocker_type'],
                            'reason' => $task['blocker_reason'],
                            'waiting_on' => $task['blocker_waiting_on'],
                            'waiting_since' => $task['blocker_waiting_since'],
                        ]) ?>
                    </div>
                    <div class="text-xs text-secondary mb-1">
                        <strong class="text-primary">Reason:</strong> <?= $this->e($task['blocker_reason']) ?>
                    </div>
                    <div class="text-xs text-muted">
                        Waiting since <?= date('M j, Y g:ia', strtotime($task['blocker_waiting_since'])) ?> (<?= days_waiting($task['blocker_waiting_since']) ?> days)
                    </div>
                </div>
            <?php else: ?>
                <div class="p-3 text-xs font-semibold rounded" style="background: var(--surface-card); border: 1px solid var(--border-subtle); color: var(--color-success-text);">
                    <span style="color: var(--color-success); font-weight: 700;">✓ Task is unblocked</span> — Work is moving smoothly.
                </div>
            <?php endif; ?>
        </div>

        <!-- Blocker Edit Form (Freelancer only) -->
        <?php if (!$isClient): ?>
            <div id="blocker-edit-form" class="hidden mt-3 pt-3" style="border-top: 1px solid var(--border-subtle);">
                <form onsubmit="submitBlockerForm(event, <?= $task['id'] ?>)">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-3); margin-bottom: var(--space-3);">
                        <div>
                            <label class="form-label text-xs font-bold text-muted">Blocker Type</label>
                            <select id="blocker-type" name="type" class="form-select w-full" required>
                                <option value="none" <?= empty($task['blocker_type']) ? 'selected' : '' ?>>None (Remove Blocker)</option>
                                <option value="feedback" <?= ($task['blocker_type'] ?? '') === 'feedback' ? 'selected' : '' ?>>Feedback (Waiting on review)</option>
                                <option value="content" <?= ($task['blocker_type'] ?? '') === 'content' ? 'selected' : '' ?>>Content (Waiting on assets/copy)</option>
                                <option value="payment" <?= ($task['blocker_type'] ?? '') === 'payment' ? 'selected' : '' ?>>Payment (Waiting on deposit/invoice)</option>
                                <option value="scheduling" <?= ($task['blocker_type'] ?? '') === 'scheduling' ? 'selected' : '' ?>>Scheduling (Waiting to book call)</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label text-xs font-bold text-muted">Waiting On</label>
                            <select id="blocker-waiting-on" name="waiting_on" class="form-select w-full">
                                <option value="client" <?= ($task['blocker_waiting_on'] ?? 'client') === 'client' ? 'selected' : '' ?>>Client</option>
                                <option value="freelancer" <?= ($task['blocker_waiting_on'] ?? '') === 'freelancer' ? 'selected' : '' ?>>You (Freelancer)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label text-xs font-bold text-muted">Reason / Specific Requirement</label>
                        <input type="text" id="blocker-reason" name="reason" class="form-input w-full"
                               value="<?= $this->e($task['blocker_reason'] ?? '') ?>"
                               placeholder="e.g. Waiting for client to upload vector logo and final copy">
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button type="button" class="btn btn-ghost btn-sm" onclick="toggleBlockerForm()">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">Save Blocker</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- Client Sign-off Review Box (In Review Stage) -->
    <?php if (!empty($task['is_review_stage'])): ?>
        <div class="panel p-4 mb-5" style="border: 1px solid var(--color-primary); background: var(--surface-card); border-radius: var(--radius-md);">
            <h4 class="font-heading text-xs font-bold uppercase tracking-wider text-primary mb-2 flex items-center gap-2">
                <?= clay_icon('shield', 16, 'text-primary') ?>
                <span>Client Review & Sign-Off</span>
            </h4>
            <div class="mb-3">
                <?php if ($task['review_status'] === 'approved'): ?>
                    <div class="p-3 rounded text-xs" style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); color: var(--color-success-text);">
                        <strong class="font-bold">✓ Work Approved:</strong> Client has signed off on this deliverable.
                        <?php if (!empty($task['review_notes'])): ?>
                            <div class="mt-1 text-muted"><em>"<?= $this->e($task['review_notes']) ?>"</em></div>
                        <?php endif; ?>
                    </div>
                <?php elseif ($task['review_status'] === 'changes_requested'): ?>
                    <div class="p-3 rounded text-xs" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: var(--color-danger-text);">
                        <strong class="font-bold">↻ Changes Requested:</strong>
                        <div class="mt-1"><?= $this->e($task['review_notes'] ?? 'Revisions needed.') ?></div>
                    </div>
                <?php else: ?>
                    <p class="text-xs text-muted m-0 font-medium">This deliverable is ready for sign-off review.</p>
                <?php endif; ?>
            </div>

            <?php if ($isClient): ?>
                <div class="flex items-center gap-2">
                    <button type="button" class="btn btn-success btn-sm" onclick="portalReviewTask(<?= $task['id'] ?>, 'approve')">
                        <?= clay_icon('check', 14) ?>
                        <span>Approve Deliverable</span>
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="portalShowChangesPrompt(<?= $task['id'] ?>)">
                        <?= clay_icon('feedback', 14) ?>
                        <span>Request Revisions</span>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Files & Deliverables Section -->
    <div class="mb-5">
        <div class="flex items-center justify-between mb-3">
            <h4 class="font-heading text-xs font-bold uppercase tracking-wider text-muted m-0 flex items-center gap-2">
                <?= clay_icon('paperclip', 14) ?>
                <span>Files & Deliverables (<?= count($files) ?>)</span>
            </h4>
            <label class="btn btn-secondary btn-sm cursor-pointer m-0">
                <?= clay_icon('plus', 14) ?>
                <span>Upload File</span>
                <input type="file" class="sr-only" onchange="uploadTaskFile(event, <?= $task['id'] ?>, '<?= $portalToken ?>')">
            </label>
        </div>

        <div id="files-list-<?= $task['id'] ?>" class="flex flex-col gap-2">
            <?php if (empty($files)): ?>
                <p class="text-xs text-muted italic p-2 m-0" id="no-files-msg">No files attached yet.</p>
            <?php else: ?>
                <?php foreach ($files as $f): ?>
                    <div class="panel p-3 flex items-center justify-between" id="file-item-<?= $f['id'] ?>" style="border: 1px solid var(--border-subtle); background: var(--surface-card); border-radius: var(--radius-sm);">
                        <div class="flex items-center gap-3">
                            <span class="text-muted" style="display:flex; align-items:center;"><?= clay_icon('paperclip', 16) ?></span>
                            <div>
                                <div class="text-xs font-bold text-primary"><?= $this->e($f['original_name']) ?></div>
                                <div class="text-xs text-muted"><?= format_bytes((int)$f['file_size']) ?> · <?= $f['uploaded_by_client'] ? 'Client Upload' : 'Delivered by Freelancer' ?></div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="/files/<?= $f['id'] ?>/download<?= !empty($portalToken) ? '?portal_token=' . urlencode($portalToken) : '' ?>" class="btn btn-secondary btn-sm py-1 px-2" title="Download">
                                <?= clay_icon('download', 14) ?>
                            </a>
                            <?php if (!$isClient): ?>
                                <button type="button" class="btn btn-ghost btn-sm py-1 px-2 text-danger" onclick="deleteTaskFile(<?= $f['id'] ?>)" title="Delete file">
                                    <?= clay_icon('trash', 14) ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Time Tracker Section -->
    <?php if (!$isClient || $task['billing_type'] === 'hourly'): ?>
        <div class="mb-5">
            <div class="flex items-center justify-between mb-3">
                <h4 class="font-heading text-xs font-bold uppercase tracking-wider text-muted m-0 flex items-center gap-2">
                    <?= clay_icon('clock', 14) ?>
                    <span>Time Tracker</span>
                </h4>
                <?php if (!$isClient): ?>
                    <div class="flex items-center gap-2">
                        <button type="button" class="btn btn-primary btn-sm" onclick="startTimerForTask(<?= $task['id'] ?>, '<?= $this->e(addslashes($task['title'])) ?>', '<?= $this->e(addslashes($task['client_name'])) ?>')">
                            <?= clay_icon('play', 12) ?>
                            <span>Start Timer</span>
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="toggleManualTimeForm()">
                            <span>+ Log Hours</span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Manual Log Form -->
            <?php if (!$isClient): ?>
                <div id="manual-time-form" class="hidden panel p-4 mb-3" style="background: var(--surface-tray); border: 1px solid var(--border-subtle); border-radius: var(--radius-md);">
                    <form onsubmit="submitManualTime(event, <?= $task['id'] ?>)">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-3); margin-bottom: var(--space-3);">
                            <div>
                                <label class="form-label text-xs font-bold text-muted">Minutes Spent</label>
                                <input type="number" id="time-minutes" placeholder="e.g. 60" min="1" class="form-input w-full" required>
                            </div>
                            <div>
                                <label class="form-label text-xs font-bold text-muted">Date</label>
                                <input type="date" id="time-date" value="<?= date('Y-m-d') ?>" class="form-input w-full">
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label text-xs font-bold text-muted">Notes</label>
                            <input type="text" id="time-note" placeholder="Notes (e.g. Iterated on dashboard UI)" class="form-input w-full">
                        </div>
                        <div class="flex items-center justify-end gap-2">
                            <button type="button" class="btn btn-ghost btn-sm" onclick="toggleManualTimeForm()">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm">Save Entry</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Time Entries Mini List -->
            <div class="flex flex-col gap-2">
                <?php if (empty($timeEntries)): ?>
                    <p class="text-xs text-muted italic p-2 m-0">No time logged on this task yet.</p>
                <?php else: ?>
                    <?php foreach ($timeEntries as $te): ?>
                        <div class="panel p-3 flex items-center justify-between" id="time-row-<?= $te['id'] ?>" style="background: var(--surface-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm);">
                            <div>
                                <span class="text-xs font-bold text-primary"><?= date('M j, Y', strtotime($te['entry_date'])) ?></span>
                                <span class="text-xs text-muted ml-2"><?= $this->e($te['notes'] ?: 'Work session') ?></span>
                                <?php if ($te['is_private']): ?>
                                    <span class="lozenge lozenge-muted ml-1" style="font-size: 0.65rem;">Private</span>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-xs text-primary"><?= format_duration((int)$te['duration_minutes']) ?></span>
                                <?php if (!$isClient): ?>
                                    <button type="button" class="btn btn-ghost btn-sm py-0 px-1 text-danger" onclick="deleteTimeEntry(<?= $te['id'] ?>)" title="Delete">&times;</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Discussion Thread -->
    <div>
        <h4 class="font-heading text-xs font-bold uppercase tracking-wider text-muted mb-3 flex items-center gap-2">
            <?= clay_icon('message-square', 14) ?>
            <span>Discussion & Comments</span>
        </h4>

        <div id="comments-container-<?= $task['id'] ?>" class="flex flex-col gap-2 mb-3" style="max-height: 260px; overflow-y: auto;">
            <?php if (empty($comments)): ?>
                <p class="text-xs text-muted italic p-2 m-0" id="no-comments-msg">No comments yet. Leave an update for the client.</p>
            <?php else: ?>
                <?php foreach ($comments as $c): ?>
                    <div class="panel p-3" style="background: var(--surface-card); border: 1px solid <?= $c['is_client'] ? 'var(--color-primary)' : 'var(--border-subtle)' ?>; border-radius: var(--radius-sm);">
                        <div class="flex items-center justify-between mb-1">
                            <strong class="text-xs font-bold text-primary"><?= $this->e($c['author_name']) ?> (<?= $c['is_client'] ? 'Client' : 'Freelancer' ?>)</strong>
                            <span class="text-xs text-muted"><?= date('M j, g:ia', strtotime($c['created_at'])) ?></span>
                        </div>
                        <div class="text-xs text-secondary leading-relaxed"><?= nl2br($this->e($c['content'])) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Add Comment Input -->
        <form onsubmit="submitComment(event, <?= $task['id'] ?>, '<?= $portalToken ?>')">
            <textarea name="comment" class="form-textarea w-full" rows="2" placeholder="Write a comment or question..." required style="min-height: 70px;"></textarea>
            <div class="flex items-center justify-end mt-2">
                <button type="submit" class="btn btn-primary btn-sm font-semibold">
                    <?= clay_icon('arrow-right', 14) ?>
                    <span>Send Comment</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleEditDesc(show) {
    document.getElementById('desc-view')?.classList.toggle('hidden', show);
    document.getElementById('desc-edit')?.classList.toggle('hidden', !show);
    if (show) document.getElementById('task-desc-input')?.focus();
}

function saveTaskDescription(taskId) {
    const val = document.getElementById('task-desc-input').value;
    fetch(`/tasks/${taskId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            description: val,
            _csrf_token: csrfToken
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('desc-view').innerHTML = val ? val.replace(/\n/g, '<br>') : '<span class="text-muted italic">Click to add deliverable scope...</span>';
            toggleEditDesc(false);
        }
    });
}

function toggleBlockerForm() {
    document.getElementById('blocker-edit-form')?.classList.toggle('hidden');
}

function toggleManualTimeForm() {
    document.getElementById('manual-time-form')?.classList.toggle('hidden');
}

function submitManualTime(event, taskId) {
    event.preventDefault();
    const mins = document.getElementById('time-minutes').value;
    const date = document.getElementById('time-date').value;
    const notes = document.getElementById('time-note').value;

    fetch('/time/manual', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            task_id: taskId,
            duration_minutes: mins,
            entry_date: date,
            notes: notes,
            _csrf_token: csrfToken
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            openTaskSlideover(taskId);
        } else {
            window.WS.modal.error({
                title: 'Time Logging Failed',
                message: data.error || 'Failed to log time'
            });
        }
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
                document.getElementById(`time-row-${entryId}`)?.remove();
            } else {
                window.WS.modal.error({
                    title: 'Delete Failed',
                    message: data.error || 'Failed to delete time entry'
                });
            }
        });
    });
}
</script>
