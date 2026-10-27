<?php
declare(strict_types=1);

// ---------------------------------------------------------------------------
// Layout
// ---------------------------------------------------------------------------

/** Sidebar structure. Items only show when the user has the permission. */
function navigation(): array
{
    return [
        ['label' => null, 'dept' => 'home', 'items' => [
            ['key' => 'dashboard', 'label' => 'Overview', 'href' => 'index.php', 'icon' => 'home', 'can' => null],
            ['key' => 'me', 'label' => 'My record', 'href' => 'me.php', 'icon' => 'user', 'can' => 'self.view'],
        ]],
        ['label' => 'Registry', 'dept' => 'registry', 'items' => [
            ['key' => 'civilians', 'label' => 'Civilians', 'href' => 'registry/civilians.php', 'icon' => 'users', 'can' => 'civilians.view'],
            ['key' => 'licenses', 'label' => 'Licenses', 'href' => 'registry/licenses.php', 'icon' => 'card', 'can' => 'licenses.view'],
            ['key' => 'businesses', 'label' => 'Businesses', 'href' => 'registry/businesses.php', 'icon' => 'building', 'can' => 'businesses.view'],
            ['key' => 'gov_ops', 'label' => 'Public works', 'href' => 'operations.php?agency=government', 'icon' => 'flag', 'can' => 'gov_ops.view'],
        ]],
        ['label' => 'Police', 'dept' => 'police', 'items' => [
            ['key' => 'dispatch', 'label' => 'Dispatch', 'href' => 'police/dispatch.php', 'icon' => 'radio', 'can' => 'dispatch.use'],
            ['key' => 'warrants', 'label' => 'Warrants', 'href' => 'police/warrants.php', 'icon' => 'stamp', 'can' => 'warrants.view'],
            ['key' => 'records', 'label' => 'Criminal records', 'href' => 'police/records.php', 'icon' => 'shield', 'can' => 'records.view'],
            ['key' => 'police_ops', 'label' => 'Operations', 'href' => 'operations.php?agency=police', 'icon' => 'target', 'can' => 'police_ops.view'],
        ]],
        ['label' => 'Court', 'dept' => 'court', 'items' => [
            ['key' => 'files', 'label' => 'Case files', 'href' => 'court/files.php', 'icon' => 'folder', 'can' => 'court.view'],
            ['key' => 'documents', 'label' => 'Documents', 'href' => 'court/documents.php', 'icon' => 'file', 'can' => 'court.view'],
        ]],
        ['label' => 'Administration', 'dept' => 'admin', 'items' => [
            ['key' => 'users', 'label' => 'Users', 'href' => 'admin/users.php', 'icon' => 'key', 'can' => 'users.manage'],
            ['key' => 'activity', 'label' => 'Activity log', 'href' => 'admin/activity.php', 'icon' => 'list', 'can' => 'audit.view'],
        ]],
    ];
}

/** The department a nav key belongs to; sets the page accent colour. */
function department_of(string $navKey): string
{
    foreach (navigation() as $group) {
        foreach ($group['items'] as $item) {
            if ($item['key'] === $navKey) {
                return $group['dept'];
            }
        }
    }
    return 'home';
}

function page_start(string $title, string $nav = '', array $options = []): void
{
    $user = current_user();
    $dept = $options['dept'] ?? department_of($nav);
    $flashes = take_flashes();
    include __DIR__ . '/views/layout_start.php';
}

function page_end(): void
{
    include __DIR__ . '/views/layout_end.php';
}

/** Page title row with optional subtitle and action buttons (raw HTML). */
function page_header(string $title, string $subtitle = '', string $actions = ''): void
{
    echo '<header class="page-header"><div>';
    echo '<h1>' . e($title) . '</h1>';
    if ($subtitle !== '') {
        echo '<p class="page-subtitle">' . e($subtitle) . '</p>';
    }
    echo '</div>';
    if ($actions !== '') {
        echo '<div class="page-actions">' . $actions . '</div>';
    }
    echo '</header>';
}

function render_error_page(int $status, string $title, string $message): void
{
    // Kept free of database calls so it still works when the database is down.
    include __DIR__ . '/views/error.php';
}

// ---------------------------------------------------------------------------
// Small components
// ---------------------------------------------------------------------------

function button_link(string $label, string $href, string $variant = 'secondary', ?string $icon = null): string
{
    $iconHtml = $icon ? icon($icon) : '';
    return '<a class="btn btn-' . e($variant) . '" href="' . e($href) . '">' . $iconHtml . '<span>' . e($label) . '</span></a>';
}

/**
 * A one-button POST form (delete, change status...).
 * $confirm shows a confirmation dialog first.
 */
function action_form(string $action, array $fields, string $label, string $variant = 'secondary', ?string $confirm = null): string
{
    $html = '<form method="post" action="' . e($action) . '" class="inline-form"'
        . ($confirm ? ' data-confirm="' . e($confirm) . '"' : '') . '>' . csrf_field();
    foreach ($fields as $name => $value) {
        $html .= '<input type="hidden" name="' . e($name) . '" value="' . e($value) . '">';
    }
    return $html . '<button type="submit" class="btn btn-' . e($variant) . ' btn-sm">' . e($label) . '</button></form>';
}

const STATUS_TONES = [
    'active' => 'ok', 'issued' => 'warn', 'pending' => 'info', 'served' => 'ok',
    'cancelled' => 'muted', 'suspended' => 'warn', 'revoked' => 'bad', 'expired' => 'bad',
    'open' => 'info', 'closed' => 'muted', 'planned' => 'info', 'completed' => 'muted',
    'urgent' => 'bad', 'normal' => 'info', 'low' => 'muted',
];

function badge(string $status, ?string $label = null): string
{
    $tone = STATUS_TONES[$status] ?? 'muted';
    return '<span class="badge badge-' . $tone . '">' . e($label ?? ucfirst($status)) . '</span>';
}

/** Licenses past their end date show as expired whatever their stored status. */
function license_status(array $license): string
{
    if ($license['status'] === 'active' && $license['expires_on'] < date('Y-m-d')) {
        return 'expired';
    }
    return $license['status'];
}

function empty_state(string $message, string $action = ''): string
{
    return '<div class="empty"><p>' . e($message) . '</p>' . $action . '</div>';
}

function pagination(array $result): string
{
    if ($result['pages'] <= 1) {
        return '';
    }
    $query = $_GET;
    $link = function (int $page, string $label, bool $disabled = false) use ($query) {
        if ($disabled) {
            return '<span class="btn btn-ghost btn-sm" aria-disabled="true">' . $label . '</span>';
        }
        $query['page'] = $page;
        return '<a class="btn btn-ghost btn-sm" href="?' . e(http_build_query($query)) . '">' . $label . '</a>';
    };
    return '<nav class="pagination" aria-label="Pages">'
        . $link($result['page'] - 1, 'Previous', $result['page'] <= 1)
        . '<span class="pagination-info">Page ' . $result['page'] . ' of ' . $result['pages'] . '</span>'
        . $link($result['page'] + 1, 'Next', $result['page'] >= $result['pages'])
        . '</nav>';
}

/** Search box used at the top of list pages (GET form). */
function search_bar(string $placeholder, array $filters = []): string
{
    $html = '<form class="toolbar" method="get" role="search">';
    $html .= '<label class="search-input">' . icon('search')
        . '<span class="sr-only">Search</span>'
        . '<input type="search" name="q" value="' . e(input('q')) . '" placeholder="' . e($placeholder) . '"></label>';
    foreach ($filters as $name => $options) {
        $current = input($name);
        $html .= '<label class="sr-only" for="filter-' . e($name) . '">Filter by ' . e($name) . '</label>';
        $html .= '<select id="filter-' . e($name) . '" name="' . e($name) . '" data-autosubmit>';
        foreach ($options as $value => $label) {
            $html .= '<option value="' . e($value) . '"' . ((string) $value === $current ? ' selected' : '') . '>' . e($label) . '</option>';
        }
        $html .= '</select>';
    }
    foreach (['agency'] as $keep) {
        if (input($keep) !== '') {
            $html .= '<input type="hidden" name="' . $keep . '" value="' . e(input($keep)) . '">';
        }
    }
    return $html . '<button class="btn btn-secondary" type="submit">Search</button></form>';
}

// ---------------------------------------------------------------------------
// Forms
// ---------------------------------------------------------------------------

/** Set the values and errors that field() reads from. */
function form_context(?array $values = null, ?array $errors = null): array
{
    static $context = ['values' => [], 'errors' => []];
    if ($values !== null) {
        $context = ['values' => $values, 'errors' => $errors ?? []];
    }
    return $context;
}

/**
 * Render a labelled form field.
 * Options: type, required, hint, placeholder, options (select), rows,
 * autocomplete, value, wide (spans both grid columns), attrs.
 */
function field(string $name, string $label, array $o = []): string
{
    $ctx = form_context();
    $type = $o['type'] ?? 'text';
    $value = $o['value'] ?? ($ctx['values'][$name] ?? '');
    $error = $ctx['errors'][$name] ?? null;
    $id = 'f-' . $name;
    $required = !empty($o['required']);

    $attrs = 'id="' . $id . '" name="' . e($name) . '"'
        . ($required ? ' required' : '')
        . (isset($o['placeholder']) ? ' placeholder="' . e($o['placeholder']) . '"' : '')
        . (isset($o['autocomplete']) ? ' autocomplete="' . e($o['autocomplete']) . '"' : '')
        . ($error ? ' aria-invalid="true" aria-describedby="' . $id . '-error"' : '')
        . (isset($o['hint']) && !$error ? ' aria-describedby="' . $id . '-hint"' : '')
        . (isset($o['attrs']) ? ' ' . $o['attrs'] : '');

    $control = match ($type) {
        'textarea' => '<textarea ' . $attrs . ' rows="' . (int) ($o['rows'] ?? 4) . '">' . e($value) . '</textarea>',
        'select' => (function () use ($attrs, $o, $value) {
            $html = '<select ' . $attrs . '>';
            if (isset($o['blank'])) {
                $html .= '<option value="">' . e($o['blank']) . '</option>';
            }
            foreach ($o['options'] ?? [] as $optValue => $optLabel) {
                $selected = (string) $optValue === (string) $value ? ' selected' : '';
                $html .= '<option value="' . e($optValue) . '"' . $selected . '>' . e($optLabel) . '</option>';
            }
            return $html . '</select>';
        })(),
        default => '<input type="' . e($type) . '" ' . $attrs . ' value="' . ($type === 'password' ? '' : e($value)) . '">',
    };

    $html = '<div class="field' . (!empty($o['wide']) ? ' field-wide' : '') . ($error ? ' has-error' : '') . '">';
    $html .= '<label for="' . $id . '">' . e($label) . ($required ? '' : ' <span class="optional">optional</span>') . '</label>';
    $html .= $control;
    if ($error) {
        $html .= '<p class="field-error" id="' . $id . '-error">' . e($error) . '</p>';
    } elseif (isset($o['hint'])) {
        $html .= '<p class="field-hint" id="' . $id . '-hint">' . e($o['hint']) . '</p>';
    }
    return $html . '</div>';
}

function form_errors_summary(array $errors): string
{
    if (!$errors) {
        return '';
    }
    $count = count($errors);
    return '<div class="alert alert-error" role="alert">'
        . ($count === 1 ? 'Fix the highlighted field and save again.' : "Fix the $count highlighted fields and save again.")
        . '</div>';
}

// Up to this many people, the person picker includes everyone in the page,
// so it works without JavaScript. Above it, only the chosen person is
// included and the picker searches the register as you type.
const PERSON_PRELOAD_LIMIT = 300;

function person_label(array $c): string
{
    return $c['last_name'] . ', ' . $c['first_name'] . ' (' . $c['person_code'] . ')';
}

/** Options for a "choose a person" select: id => "Last, First (code)". */
function civilian_options(?int $selected = null): array
{
    $sql = 'SELECT id, first_name, last_name, person_code FROM civilians';
    $all = (int) db_value('SELECT COUNT(*) FROM civilians') <= PERSON_PRELOAD_LIMIT;
    $rows = $all
        ? db_all("$sql ORDER BY last_name, first_name")
        : ($selected ? db_all("$sql WHERE id = ?", [$selected]) : []);
    $options = [];
    foreach ($rows as $c) {
        $options[$c['id']] = person_label($c);
    }
    return $options;
}

/** A person picker: a select that JavaScript turns into a search box. */
function person_field(string $name, string $label, array $o = []): string
{
    $selected = filter_var(form_context()['values'][$name] ?? null, FILTER_VALIDATE_INT) ?: null;
    return field($name, $label, $o + [
        'type' => 'select',
        'blank' => 'Choose a person',
        'options' => civilian_options($selected),
        'attrs' => 'data-person-picker="' . e(url('api/people.php')) . '"',
    ]);
}

function person_link(array $row, string $prefix = '', string $idKey = 'civilian_id'): string
{
    $name = e(full_name($row, $prefix));
    if (can('civilians.view') && !empty($row[$idKey])) {
        return '<a href="' . e(url('registry/civilian.php', ['id' => $row[$idKey]])) . '">' . $name . '</a>';
    }
    return $name;
}

// ---------------------------------------------------------------------------
// Icons (24px stroke icons, drawn to match each other)
// ---------------------------------------------------------------------------

function icon(string $name): string
{
    $paths = [
        'home' => '<path d="M4 10.5 12 4l8 6.5V20h-5v-6H9v6H4z"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.8 2.9-6 6.5-6s6.5 2.2 6.5 6"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14.3c2.2.7 3.5 2.6 3.5 5.7"/>',
        'card' => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="11" r="2"/><path d="M5.5 16c.6-1.5 1.7-2.2 3-2.2s2.4.7 3 2.2M14 10h4M14 13.5h3"/>',
        'building' => '<path d="M4 21V5l8-2v18M12 8l8 2.5V21M3 21h18M7.5 8h1M7.5 12h1M7.5 16h1M15.5 13h1M15.5 17h1"/>',
        'flag' => '<path d="M5 21V4M5 4h11l-2 4 2 4H5"/>',
        'radio' => '<circle cx="12" cy="12" r="2"/><path d="M8.5 15.5a5 5 0 0 1 0-7M15.5 8.5a5 5 0 0 1 0 7M5.6 18.4a9 9 0 0 1 0-12.8M18.4 5.6a9 9 0 0 1 0 12.8"/>',
        'stamp' => '<path d="M9 13V9.5a3 3 0 1 1 6 0V13M5 13h14l1 4H4zM6 20h12"/>',
        'shield' => '<path d="M12 3 4.5 6v5.5c0 4.6 3.1 8.2 7.5 9.5 4.4-1.3 7.5-4.9 7.5-9.5V6z"/>',
        'target' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3.5"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/>',
        'folder' => '<path d="M3 6.5A1.5 1.5 0 0 1 4.5 5H9l2 2.5h8.5A1.5 1.5 0 0 1 21 9v9.5a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18.5z"/>',
        'file' => '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 12h6M9 16h6"/>',
        'key' => '<circle cx="8" cy="15" r="4"/><path d="m11 12 8-8M16 7l2.5 2.5M18.5 4.5 21 7"/>',
        'list' => '<path d="M9 6h11M9 12h11M9 18h11M4 6h.01M4 12h.01M4 18h.01"/>',
        'search' => '<circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.4-4.4"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'logout' => '<path d="M14 4h5v16h-5M10 8l-4 4 4 4M6 12h10"/>',
        'lock' => '<rect x="5" y="10.5" width="14" height="10" rx="2"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/>',
        'printer' => '<path d="M7 9V3h10v6M7 17H4v-7h16v7h-3M7 14h10v7H7z"/>',
        'edit' => '<path d="M4 20h4L19 9l-4-4L4 16zM13.5 6.5l4 4"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'arrow-left' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
    ];
    return '<svg class="icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . ($paths[$name] ?? '') . '</svg>';
}
