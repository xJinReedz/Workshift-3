<?php
/**
 * Global In-App Modal Container & Flash Messages Dispatcher
 * Included on every layout (main, portal, auth, public).
 */
use WorkShift\Core\Session;

$flashData = [];
foreach (['success', 'error', 'warning', 'info', 'upgrade_prompt'] as $key) {
    if ($val = Session::flash($key)) {
        $flashData[$key] = $val;
    }
}
?>
<div id="ws-modal-root"></div>

<?php if (!empty($flashData)): ?>
    <div id="ws-flash-data" data-flash="<?= htmlspecialchars(json_encode($flashData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>" style="display:none;"></div>
<?php endif; ?>

<script src="<?= asset('/assets/js/modal.js') ?>"></script>
