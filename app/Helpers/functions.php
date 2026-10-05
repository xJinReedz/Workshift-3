<?php
/**
 * Global Helper Functions for WorkShift — Enterprise SaaS (Atlassian Design Language)
 */

use WorkShift\Core\Session;

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Session::getCsrfToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('format_currency')) {
    function format_currency(float $amount, string $currency = 'PHP'): string
    {
        $symbol = ($currency === 'PHP') ? '₱' : '$';
        return '<span class="tabular-nums font-semibold">' . $symbol . number_format($amount, 2) . '</span>';
    }
}

if (!function_exists('format_currency_raw')) {
    function format_currency_raw(float $amount, string $currency = 'PHP'): string
    {
        $symbol = ($currency === 'PHP') ? '₱' : '$';
        return $symbol . number_format($amount, 2);
    }
}

if (!function_exists('format_duration')) {
    function format_duration(int $minutes): string
    {
        if ($minutes < 60) {
            return "<span class=\"tabular-nums\">{$minutes}m</span>";
        }
        $hours = floor($minutes / 60);
        $remMinutes = $minutes % 60;
        if ($remMinutes === 0) {
            return "<span class=\"tabular-nums\">{$hours}h</span>";
        }
        return "<span class=\"tabular-nums\">{$hours}h {$remMinutes}m</span>";
    }
}

if (!function_exists('days_waiting')) {
    function days_waiting(string $sinceTimestamp): int
    {
        try {
            $since = new DateTime($sinceTimestamp);
            $now = new DateTime();
            $diff = $now->diff($since);
            return max(0, (int)$diff->days);
        } catch (\Exception $e) {
            return 0;
        }
    }
}

if (!function_exists('clay_icon')) {
    /**
     * Consistent Lucide-style SVG icons (1.5px stroke, 16px/20px sizes, no emoji)
     */
    function clay_icon(string $name, int $size = 16, string $class = '', float $stroke = 1.5): string
    {
        $icons = [
            'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
            'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
            'kanban' => '<rect x="3" y="3" width="5" height="18" rx="1"/><rect x="10" y="3" width="5" height="11" rx="1"/><rect x="17" y="3" width="5" height="15" rx="1"/>',
            'pipeline' => '<path d="M3 3v18h18"/><path d="M18 9l-5 5-4-4-3 3"/>',
            'invoice' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/>',
            'clock' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
            'bell' => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
            'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
            'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
            'search' => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
            'sun' => '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>',
            'moon' => '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>',
            'plus' => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
            'check' => '<polyline points="20 6 9 17 4 12"/>',
            'x' => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
            'menu' => '<line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>',
            'chevron-right' => '<polyline points="9 18 15 12 9 6"/>',
            'chevron-down' => '<polyline points="6 9 12 15 18 9"/>',
            'chevron-left' => '<polyline points="15 18 9 12 15 6"/>',
            'feedback' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
            'content' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
            'payment' => '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>',
            'scheduling' => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
            'arrow-right' => '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
            'link' => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
            'inbox' => '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
            'more-horizontal' => '<circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/>',
            'file-text' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/>',
            'alert-circle' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
            'play' => '<polygon points="5 3 19 12 5 21 5 3"/>',
            'square' => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>',
            'external-link' => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>',
            'paperclip' => '<path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>',
            'message-square' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
            'filter' => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>',
            'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
            'sparkles' => '<path d="M12 3l1.912 5.885L20 10l-5.088 3.115L16.824 19 12 15.885 7.176 19l1.912-5.885L4 10l6.088-1.115L12 3z"/>',
            'credit-card' => '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>',
            'printer' => '<polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
            'edit' => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
            'trash' => '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
            'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
            'grip-vertical' => '<circle cx="9" cy="12" r="1"/><circle cx="9" cy="5" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="19" r="1"/>',
            'help-circle' => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
            'layers' => '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>',
        ];

        $inner = $icons[$name] ?? '<circle cx="12" cy="12" r="9"/>';
        $classAttr = $class ? ' class="' . e($class) . '"' : '';

        return sprintf(
            '<svg width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="%.2f" stroke-linecap="round" stroke-linejoin="round"%s aria-hidden="true">%s</svg>',
            $size,
            $size,
            $stroke,
            $classAttr,
            $inner
        );
    }
}

if (!function_exists('blocker_badge')) {
    /**
     * Blocker Lozenge: 11-12px semibold, 2px 8px padding, 4px radius, subtle tinted background, matching text and icon
     * Feedback, Content, Payment, Scheduling
     */
    function blocker_badge(?array $blocker): string
    {
        if (!$blocker || empty($blocker['type'])) {
            return '';
        }

        $type = strtolower($blocker['type']);
        $days = days_waiting($blocker['waiting_since'] ?? 'now');
        $daysLabel = $days === 0 ? 'Today' : ($days === 1 ? '1d' : "{$days}d");

        $configs = [
            'feedback' => [
                'label' => 'Feedback',
                'icon' => 'feedback',
                'class' => 'clay-blocker-feedback',
            ],
            'content' => [
                'label' => 'Content',
                'icon' => 'content',
                'class' => 'clay-blocker-content',
            ],
            'payment' => [
                'label' => 'Payment',
                'icon' => 'payment',
                'class' => 'clay-blocker-payment',
            ],
            'scheduling' => [
                'label' => 'Scheduling',
                'icon' => 'scheduling',
                'class' => 'clay-blocker-scheduling',
            ],
        ];

        $cfg = $configs[$type] ?? [
            'label' => ucfirst($type),
            'icon' => 'alert-circle',
            'class' => 'clay-blocker-default',
        ];

        $waitingOn = ($blocker['waiting_on'] ?? 'client') === 'client' ? 'Client' : 'You';
        $iconSvg = clay_icon($cfg['icon'], 12, 'blocker-chip-icon', 1.75);

        return sprintf(
            '<span class="clay-chip clay-chip-blocker %s" title="%s: %s (Waiting on %s since %s)" role="status">' .
            '<span class="chip-icon-wrap">%s</span>' .
            '<span class="chip-label">%s</span>' .
            '<span class="chip-days-bubble">%s</span>' .
            '</span>',
            e($cfg['class']),
            e($cfg['label']),
            e($blocker['reason'] ?? ''),
            e($waitingOn),
            e($daysLabel),
            $iconSvg,
            e($cfg['label']),
            e($daysLabel)
        );
    }
}

if (!function_exists('status_chip')) {
    /**
     * Compact status lozenge: 11-12px semibold, 2px 8px padding, 4px radius
     */
    function status_chip(string $status, ?string $label = null, string $type = 'default'): string
    {
        $statusKey = strtolower(str_replace([' ', '-'], '_', $status));
        $label = $label ?? ucfirst($status);

        $colorClass = match ($statusKey) {
            'active', 'paid', 'approved', 'completed', 'done' => 'chip-success',
            'pending', 'waiting', 'in_progress', 'review', 'in_review' => 'chip-warning',
            'overdue', 'cancelled', 'blocked', 'failed', 'changes_requested' => 'chip-danger',
            'draft', 'archived', 'paused' => 'chip-muted',
            'pro' => 'chip-pro',
            default => 'chip-primary',
        };

        return sprintf(
            '<span class="clay-chip %s status-chip-%s"><span class="chip-dot"></span><span class="chip-text">%s</span></span>',
            $colorClass,
            e($statusKey),
            e($label)
        );
    }
}

if (!function_exists('empty_state')) {
    /**
     * Reusable clean empty state container
     */
    function empty_state(string $title, string $description, ?string $actionUrl = null, ?string $actionText = null, string $icon = 'inbox'): string
    {
        $iconSvg = clay_icon($icon, 24, 'empty-icon-svg', 1.5);
        $btn = '';
        if ($actionUrl && $actionText) {
            $btn = sprintf(
                '<div style="margin-top: var(--space-4);"><a href="%s" class="clay-btn clay-btn-primary font-medium"><span class="btn-icon">%s</span><span>%s</span></a></div>',
                e($actionUrl),
                clay_icon('plus', 14),
                e($actionText)
            );
        }

        return <<<HTML
<div class="clay-empty-state">
    <div class="empty-icon-bubble">
        {$iconSvg}
    </div>
    <h3 style="font-size: var(--text-h3); font-weight: var(--weight-semibold); margin-bottom: var(--space-2); color: var(--color-text);">{$title}</h3>
    <p style="font-size: var(--text-body); color: var(--color-text-subtle); max-width: 440px; margin: 0 auto;">{$description}</p>
    {$btn}
</div>
HTML;
    }
}

if (!function_exists('page_header')) {
    function page_header(string $title, string $subtitle = '', string $actionsHtml = ''): string
    {
        $subHtml = $subtitle ? '<p class="text-subtle text-sm mt-1">' . e($subtitle) . '</p>' : '';
        $actions = $actionsHtml ? '<div class="flex items-center gap-3">' . $actionsHtml . '</div>' : '';

        return <<<HTML
<div class="flex items-center justify-between flex-wrap gap-4 mb-6">
    <div>
        <h1 class="font-heading text-2xl font-bold">{$title}</h1>
        {$subHtml}
    </div>
    {$actions}
</div>
HTML;
    }
}

if (!function_exists('format_bytes')) {
    function format_bytes(int $bytes, int $precision = 1): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

if (!function_exists('logo_svg')) {
    /**
     * WorkShift Brand: Blue logo of two overlapping slanted blocks, clean flat inline SVG
     */
    function logo_svg(int $size = 24, string $class = ''): string
    {
        return <<<SVG
<svg width="{$size}" height="{$size}" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" class="{$class}" style="vertical-align: middle;">
  <!-- First slanted parallelogram block -->
  <polygon points="6,24 14,8 19,8 11,24" fill="var(--color-brand)" opacity="0.6"/>
  <!-- Second overlapping slanted parallelogram block -->
  <polygon points="13,24 21,8 26,8 18,24" fill="var(--color-brand)"/>
</svg>
SVG;
    }
}

if (!function_exists('active_nav')) {
    function active_nav(string $pathPrefix): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/';
        if ($pathPrefix === '/') {
            return $uri === '/' ? 'active' : '';
        }
        return str_starts_with($uri, $pathPrefix) ? 'active' : '';
    }
}
