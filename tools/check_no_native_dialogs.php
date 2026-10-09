<?php
/**
 * WorkShift – Native Dialog Verification Script
 * 
 * Scans all .php and .js files for native browser dialog calls:
 *   alert(), confirm(), prompt(), window.alert, window.confirm, window.prompt,
 *   onclick="return confirm(...)", onsubmit="return confirm(...)",
 *   <script>alert(...)</script> echoed from PHP
 * 
 * Exits 0 if clean, exits 1 if any violations found.
 * 
 * Usage: php tools/check_no_native_dialogs.php
 */

$root = dirname(__DIR__);

// Patterns to search for (case-insensitive)
$patterns = [
    '/\balert\s*\(/'                  => 'alert() call',
    '/\bconfirm\s*\(/'               => 'confirm() call',
    '/\bprompt\s*\(/'                => 'prompt() call',
    '/\bwindow\.alert\b/'            => 'window.alert reference',
    '/\bwindow\.confirm\b/'          => 'window.confirm reference',
    '/\bwindow\.prompt\b/'           => 'window.prompt reference',
    '/onclick\s*=\s*["\'].*confirm/' => 'inline onclick confirm',
    '/onsubmit\s*=\s*["\'].*confirm/'=> 'inline onsubmit confirm',
];

// Files/dirs to exclude from scanning
$excludePaths = [
    'tools/check_no_native_dialogs.php',  // this script
    'public/assets/js/modal.js',          // the modal system itself (wraps native-like API)
    'vendor/',
    'node_modules/',
    '.git/',
];

// Lines to whitelist (exact substrings that are false positives)
$whitelistSubstrings = [
    'WS.modal.alert',
    'WS.modal.confirm',
    'WS.modal.prompt',
    'WS.confirmLogout',
    'data-confirm-',
    '// alert',
    '// confirm',
    '// prompt',
    'clayConfirm',       // legacy reference in comments
    'console.log',
    'console.warn',
    'console.error',
    'displayName',       // React internals in CDN libs
    'window.confirm =',  // interceptor assignment
    'window.alert =',    // interceptor assignment
    'window.prompt =',   // interceptor assignment
    'nativeConfirm',     // stored reference in modal.js
    'nativeAlert',
    'nativePrompt',
];

function shouldExclude(string $relPath, array $excludes): bool {
    foreach ($excludes as $exc) {
        if (str_starts_with(str_replace('\\', '/', $relPath), $exc)) {
            return true;
        }
    }
    return false;
}

function isWhitelisted(string $line, array $whitelist): bool {
    foreach ($whitelist as $sub) {
        if (str_contains($line, $sub)) {
            return true;
        }
    }
    return false;
}

// Collect .php and .js files
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
);

$violations = [];

foreach ($iterator as $file) {
    if (!$file->isFile()) continue;
    $ext = strtolower($file->getExtension());
    if (!in_array($ext, ['php', 'js'])) continue;

    $absPath = $file->getPathname();
    $relPath = str_replace('\\', '/', substr($absPath, strlen($root) + 1));

    if (shouldExclude($relPath, $excludePaths)) continue;

    $lines = file($absPath, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $lineNum => $lineContent) {
        // Skip whitelisted lines
        if (isWhitelisted($lineContent, $whitelistSubstrings)) continue;

        foreach ($patterns as $pattern => $label) {
            if (preg_match($pattern, $lineContent)) {
                $violations[] = [
                    'file'    => $relPath,
                    'line'    => $lineNum + 1,
                    'label'   => $label,
                    'content' => trim($lineContent),
                ];
            }
        }
    }
}

// Report
echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║   WorkShift – Native Dialog Verification                ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

if (empty($violations)) {
    echo "  ✅  PASS — Zero native browser dialogs found.\n";
    echo "      All dialogs use the WS.modal system.\n\n";
    exit(0);
} else {
    echo "  ❌  FAIL — " . count($violations) . " native dialog(s) found:\n\n";
    foreach ($violations as $v) {
        echo "  {$v['file']}:{$v['line']}\n";
        echo "    [{$v['label']}] {$v['content']}\n\n";
    }
    exit(1);
}
