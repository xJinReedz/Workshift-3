<?php
// Full-bleed layout for edge-to-edge Kanban canvas
$isFullBleed = true;
?>
<div class="board-container" data-board-id="<?= $board['id'] ?>">
    <!-- Sticky Board Header & Breadcrumbs -->
    <header class="board-header">
        <!-- Breadcrumb: Clients / [Client name] / Board -->
        <div class="breadcrumb" style="margin-bottom: 0;">
            <a href="/clients">Clients</a>
            <span class="breadcrumb-separator">/</span>
            <a href="/clients/<?= $board['client_id'] ?>"><?= $this->e($board['client_name']) ?></a>
            <span class="breadcrumb-separator">/</span>
            <span>Board</span>
        </div>

        <div class="board-header-top">
            <div class="board-title-group">
                <h1 class="font-heading text-xl font-bold" style="line-height: 1.1; margin-bottom: 0;">
                    <?= $this->e($board['title']) ?>
                </h1>
                <span class="lozenge lozenge-default" style="font-size: 11px;">
                    <?= $this->e($board['client_company'] ?: $board['client_name']) ?>
                </span>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                <!-- Waiting-on Summary Strip -->
                <div class="board-summary-strip">
                    <span class="flex items-center gap-1" style="color: var(--blocker-feedback-text);">
                        <?= clay_icon('feedback', 12) ?>
                        <strong><?= $waitingOnClientCount ?></strong> waiting on client
                    </span>
                    <span style="opacity: 0.3;">|</span>
                    <span class="flex items-center gap-1 text-brand">
                        <?= clay_icon('alert-circle', 12) ?>
                        <strong><?= $waitingOnYouCount ?></strong> waiting on you
                    </span>
                </div>

                <!-- Copy Client Portal Link -->
                <button type="button" class="clay-btn clay-btn-secondary clay-btn-sm" onclick="copyPortalLink('<?= $portalUrl ?>', this)">
                    <?= clay_icon('link', 12) ?>
                    <span>Share Portal</span>
                </button>

                <!-- Add Stage Column Button -->
                <button type="button" class="clay-btn clay-btn-primary clay-btn-sm" onclick="showAddStagePrompt(<?= $board['id'] ?>)">
                    <?= clay_icon('plus', 12) ?>
                    <span>Add Stage</span>
                </button>
            </div>
        </div>

        <!-- Underlined Tabs & Toolbar Row -->
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: var(--space-3); border-top: 1px solid var(--color-border); padding-top: var(--space-3); margin-top: var(--space-1);">
            <div class="flex items-center gap-2">
                <!-- Filter: Waiting on Client Only -->
                <button type="button" 
                        class="clay-btn clay-btn-sm <?= $filterWaitingOnClient ? 'clay-btn-primary' : 'clay-btn-secondary' ?>"
                        onclick="toggleFilterWaitingClient(<?= $filterWaitingOnClient ? 'false' : 'true' ?>)">
                    <?= clay_icon('filter', 12) ?>
                    <span>Waiting on Client</span>
                </button>

                <!-- Client Details Link -->
                <a href="/clients/<?= $board['client_id'] ?>" class="clay-btn clay-btn-ghost clay-btn-sm">
                    <?= clay_icon('file-text', 12) ?>
                    <span>Client Summary</span>
                </a>
            </div>

            <!-- Fast Search within board -->
            <div style="position: relative; width: 220px;">
                <span style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); color: var(--color-text-subtlest);"><?= clay_icon('search', 12) ?></span>
                <input type="text" id="board-search-input" class="clay-input" placeholder="Filter tasks..." style="height: 32px; min-height: 32px; padding-left: 28px; font-size: 0.8125rem;" oninput="filterBoardCards(this.value)">
            </div>
        </div>
    </header>

    <!-- Mobile Stage Switcher Nav (Phones < 640px) -->
    <nav class="board-mobile-nav" aria-label="Kanban Stages">
        <?php foreach ($stages as $idx => $stage): ?>
            <button type="button" 
                    class="board-mobile-tab <?= $idx === 0 ? 'active' : '' ?>"
                    data-stage-id="<?= $stage['id'] ?>">
                <?= $this->e($stage['title']) ?> (<?= count($tasksByStage[$stage['id']] ?? []) ?>)
            </button>
        <?php endforeach; ?>
    </nav>

    <!-- Kanban Columns Wrapper (Horizontal Scroll Canvas) -->
    <main class="board-canvas" id="board-canvas">
        <?php foreach ($stages as $stage): ?>
            <?php
            $stageTasks = $tasksByStage[$stage['id']] ?? [];
            $isReview = !empty($stage['client_review']) || !empty($stage['is_review_stage']);
            $isDone = !empty($stage['is_done_stage']);
            $otherStages = array_values(array_filter($stages, fn($s) => $s['id'] != $stage['id']));
            ?>
            <div class="board-column" id="stage-col-<?= $stage['id'] ?>" data-stage-id="<?= $stage['id'] ?>">
                <div class="board-column-header">
                    <div class="column-title flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full inline-block" style="background: <?= $this->e($stage['color'] ?? '#4C9AFF') ?>;"></span>
                        <span><?= $this->e($stage['title']) ?></span>
                        <span class="column-count-badge"><?= count($stageTasks) ?></span>
                    </div>
                    <div class="flex items-center gap-1">
                        <?php if ($isReview): ?>
                            <span class="lozenge lozenge-warning" title="Tasks in this stage show Approve & Request Changes buttons to clients">
                                Client Review
                            </span>
                        <?php elseif ($isDone): ?>
                            <span class="lozenge lozenge-success">
                                Done
                            </span>
                        <?php endif; ?>
                        <button type="button" class="btn btn-ghost btn-sm p-1" style="width: 24px; height: 24px; min-height: 24px;" onclick="showDeleteStagePrompt('<?= $board['id'] ?>', '<?= $stage['id'] ?>', '<?= $this->e($stage['title']) ?>', <?= count($stageTasks) ?>, <?= $this->e(json_encode($otherStages)) ?>)" title="Delete stage">
                            <?= clay_icon('trash', 12) ?>
                        </button>
                    </div>
                </div>

                <!-- Vertical Scrollable Task Cards Tray (12px padding, 12px gap) -->
                <div class="board-task-list task-cards-list" id="stage-cards-<?= $stage['id'] ?>" data-stage-id="<?= $stage['id'] ?>">
                    <?php foreach ($stageTasks as $task): ?>
                        <div class="task-card"
                             data-task-id="<?= $task['id'] ?>"
                             data-stage-id="<?= $stage['id'] ?>"
                             onclick="openTaskSlideover(<?= $task['id'] ?>)">

                            <div class="task-card-header">
                                <h4 class="task-card-title"><?= $this->e($task['title']) ?></h4>
                                <span class="task-card-drag-handle" title="Drag to reorder" onclick="event.stopPropagation()" style="color: var(--color-text-subtlest); cursor: grab;">
                                    <?= clay_icon('grip-vertical', 14) ?>
                                </span>
                            </div>

                            <!-- Blocker Lozenge if active -->
                            <?php if (!empty($task['blocker_type'])): ?>
                                <div class="mb-2">
                                    <?= blocker_badge([
                                        'type' => $task['blocker_type'],
                                        'reason' => $task['blocker_reason'],
                                        'waiting_on' => $task['blocker_waiting_on'],
                                        'waiting_since' => $task['blocker_waiting_since'],
                                    ]) ?>
                                </div>
                            <?php endif; ?>

                            <!-- Review Status Tag -->
                            <?php if ($task['review_status'] === 'approved'): ?>
                                <div class="mb-2">
                                    <span class="lozenge lozenge-success">
                                        ✓ Approved by Client
                                    </span>
                                </div>
                            <?php elseif ($task['review_status'] === 'changes_requested'): ?>
                                <div class="mb-2">
                                    <span class="lozenge lozenge-danger">
                                        ↻ Revisions Requested
                                    </span>
                                </div>
                            <?php endif; ?>

                            <!-- Card Footer: Key, Due Date, Indicators & Avatar -->
                            <div class="task-card-footer">
                                <div class="task-card-meta">
                                    <!-- Task Key (Jira style, e.g. WS-12) -->
                                    <span class="task-card-key">WS-<?= $task['id'] ?></span>

                                    <?php if (!empty($task['due_date'])): ?>
                                        <span class="flex items-center gap-1 <?= strtotime($task['due_date']) < time() && !$isDone ? 'text-danger font-bold' : '' ?>" title="Due Date">
                                            <?= clay_icon('scheduling', 12) ?>
                                            <span><?= date('M j', strtotime($task['due_date'])) ?></span>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ((int)$task['total_time_minutes'] > 0): ?>
                                        <span class="flex items-center gap-1 font-semibold" title="Time Logged">
                                            <?= clay_icon('clock', 12) ?>
                                            <?= format_duration((int)$task['total_time_minutes']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="task-card-meta" onclick="event.stopPropagation()">
                                    <?php if ((int)$task['file_count'] > 0): ?>
                                        <span class="flex items-center gap-1" title="Attachments">
                                            <?= clay_icon('paperclip', 12) ?>
                                            <span><?= $task['file_count'] ?></span>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ((int)$task['comment_count'] > 0): ?>
                                        <span class="flex items-center gap-1" title="Comments">
                                            <?= clay_icon('message-square', 12) ?>
                                            <span><?= $task['comment_count'] ?></span>
                                        </span>
                                    <?php endif; ?>

                                    <!-- Touch Fallback Move To Stage Menu -->
                                    <select class="clay-select" style="width: auto; height: 24px; min-height: 24px; padding: 0 18px 0 6px; font-size: 11px; background-position: right 4px center;" onchange="moveTaskToStage(<?= $task['id'] ?>, this.value)" title="Move to stage">
                                        <option value="" disabled selected>Move</option>
                                        <?php foreach ($stages as $s): ?>
                                            <?php if ($s['id'] != $stage['id']): ?>
                                                <option value="<?= $s['id'] ?>"><?= $this->e($s['title']) ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Dashed Add Task Row at Bottom of Lane -->
                <div style="padding: var(--space-3); border-top: 1px solid var(--color-border); background: var(--color-bg-surface-sunken);">
                    <button type="button" class="lane-add-task-btn btn-add-task-trigger-<?= $stage['id'] ?>" onclick="showQuickAddForm(<?= $stage['id'] ?>)">
                        <?= clay_icon('plus', 14) ?>
                        <span>Add task</span>
                    </button>
                    <div id="quick-add-form-<?= $stage['id'] ?>" class="hidden" style="margin-top: 4px;">
                        <form onsubmit="submitQuickTask(event, <?= $board['id'] ?>, <?= $stage['id'] ?>)">
                            <textarea name="title" class="clay-textarea" placeholder="What needs to be done?" required rows="2" autofocus style="min-height: 60px; font-size: 0.8125rem; margin-bottom: var(--space-2);"></textarea>
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" class="clay-btn clay-btn-ghost clay-btn-sm" onclick="hideQuickAddForm(<?= $stage['id'] ?>)">Cancel</button>
                                <button type="submit" class="clay-btn clay-btn-primary clay-btn-sm">Add Card</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Add Stage "+" Button after last lane -->
        <div class="board-add-stage-col">
            <button type="button" class="add-stage-btn" onclick="showAddStagePrompt(<?= $board['id'] ?>)">
                <?= clay_icon('plus', 16) ?>
                <span>Add column</span>
            </button>
        </div>
    </main>
</div>

<!-- Board Scripts -->
<script src="/assets/js/board.js"></script>
<script>
function toggleFilterWaitingClient(enable) {
    const url = new URL(window.location.href);
    if (enable) {
        url.searchParams.set('waiting_client', '1');
    } else {
        url.searchParams.delete('waiting_client');
    }
    window.location.href = url.toString();
}

function showQuickAddForm(stageId) {
    document.getElementById('quick-add-form-' + stageId)?.classList.remove('hidden');
    document.querySelector('.btn-add-task-trigger-' + stageId)?.classList.add('hidden');
    document.querySelector('#quick-add-form-' + stageId + ' textarea')?.focus();
}

function hideQuickAddForm(stageId) {
    document.getElementById('quick-add-form-' + stageId)?.classList.add('hidden');
    document.querySelector('.btn-add-task-trigger-' + stageId)?.classList.remove('hidden');
}

function showAddStagePrompt(boardId) {
    window.WS.modal.prompt({
        title: 'Add Stage Column',
        label: 'Column Title',
        placeholder: 'e.g. Client Feedback, Final Polish...',
        required: true
    }).then(title => {
        if (!title || !title.trim()) return;

        window.WS.modal.confirm({
            title: 'Client Review Stage?',
            message: 'Should this be a Client Review column? Clients will be able to approve or request revisions on deliverables placed in this stage.',
            confirmText: 'Yes, Review Stage',
            cancelText: 'Standard Stage',
            variant: 'confirm'
        }).then(isReview => {
            fetch(`/boards/${boardId}/stages`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    title: title.trim(),
                    is_review_stage: isReview ? 1 : 0,
                    _csrf_token: csrfToken
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    window.WS.modal.error({
                        title: 'Error Adding Stage',
                        message: data.error || 'Failed to add stage'
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
    });
}

function filterBoardCards(query) {
    const term = query.toLowerCase().trim();
    document.querySelectorAll('.task-card').forEach(card => {
        const title = card.querySelector('.task-card-title')?.textContent.toLowerCase() || '';
        const key = card.querySelector('.task-card-key')?.textContent.toLowerCase() || '';
        if (!term || title.includes(term) || key.includes(term)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>
