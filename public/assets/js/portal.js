/**
 * WorkShift Client Portal JS
 * Real-time polling (~10s) and Client Review Approvals using WS.modal
 */

const portalTokenEl = document.querySelector('[data-portal-token]');
const portalToken = portalTokenEl ? portalTokenEl.getAttribute('data-portal-token') : '';

let portalPollInterval = null;

document.addEventListener('DOMContentLoaded', function () {
    if (portalToken) {
        startPortalPolling();
    }
});

function startPortalPolling() {
    portalPollInterval = setInterval(pollPortalData, 10000); // 10 seconds
}

function pollPortalData() {
    if (!portalToken) return;

    fetch(`/portal/${portalToken}/poll`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Check if waiting count updated
                const stripClient = document.querySelector('.portal-summary-strip .strip-client strong');
                const stripYou = document.querySelector('.portal-summary-strip .strip-you strong');
                if (stripClient) stripClient.innerText = data.waitingOnClientCount;
                if (stripYou) stripYou.innerText = data.waitingOnYouCount;
            }
        })
        .catch(err => {
            console.log('Portal poll silent error:', err);
        });
}

function portalReviewTask(taskId, action) {
    if (action === 'approve') {
        window.WS.modal.confirm({
            title: 'Approve Deliverable Milestone?',
            message: 'Are you sure you want to sign off and approve this work? Your freelancer will be notified immediately.',
            confirmText: 'Approve Deliverable',
            variant: 'confirm'
        }).then(confirmed => {
            if (!confirmed) return;
            executePortalReview(taskId, 'approve', '');
        });
        return;
    }

    executePortalReview(taskId, action, '');
}

function executePortalReview(taskId, action, notes) {
    const loading = window.WS.modal.loading({
        title: 'Submitting Review',
        message: 'Updating deliverable sign-off status...'
    });

    fetch(`/portal/${portalToken}/review/${taskId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: action,
            notes: notes
        })
    })
    .then(r => r.json())
    .then(data => {
        loading.close();
        if (data.success) {
            window.WS.modal.success({
                title: 'Deliverable Approved',
                message: 'Deliverable successfully approved! Your freelancer has been notified.',
                autoCloseMs: 3000
            }).then(() => {
                window.location.reload();
            });
        } else {
            window.WS.modal.error({
                title: 'Review Submission Failed',
                message: data.error || 'Failed to submit review.'
            });
        }
    })
    .catch(err => {
        loading.close();
        window.WS.modal.error({
            title: 'Network Error',
            message: err.message
        });
    });
}

function portalShowChangesPrompt(taskId) {
    window.WS.modal.prompt({
        title: 'Request Revisions',
        label: 'Please explain what revisions or changes are needed:',
        placeholder: 'Describe the specific edits, fixes, or additional assets required...',
        multiline: true,
        required: true
    }).then(feedback => {
        if (!feedback || !feedback.trim()) return;

        const loading = window.WS.modal.loading({
            title: 'Submitting Feedback',
            message: 'Sending change requests to freelancer...'
        });

        fetch(`/portal/${portalToken}/review/${taskId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'request_changes',
                notes: feedback.trim()
            })
        })
        .then(r => r.json())
        .then(data => {
            loading.close();
            if (data.success) {
                window.WS.modal.success({
                    title: 'Revisions Requested',
                    message: 'Feedback submitted. The task blocker has been updated and your freelancer notified.',
                    autoCloseMs: 3000
                }).then(() => {
                    window.location.reload();
                });
            } else {
                window.WS.modal.error({
                    title: 'Feedback Submission Failed',
                    message: data.error || 'Feedback submission failed'
                });
            }
        })
        .catch(err => {
            loading.close();
            window.WS.modal.error({
                title: 'Network Error',
                message: err.message
            });
        });
    });
}
