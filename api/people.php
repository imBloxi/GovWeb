<?php
// JSON search used by the person picker: GET api/people.php?q=ozo
require dirname(__DIR__) . '/app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!current_user()) {
    http_response_code(401);
    echo json_encode(['error' => 'Sign in first.']);
    exit;
}
if (!can('civilians.view')) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied.']);
    exit;
}

$q = mb_substr(input('q'), 0, 60);
$results = [];
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $rows = db_all(
        "SELECT id, first_name, last_name, person_code, dob FROM civilians
         WHERE CONCAT(first_name, ' ', last_name) LIKE ? OR CONCAT(last_name, ' ', first_name) LIKE ? OR person_code LIKE ?
         ORDER BY last_name, first_name LIMIT 12",
        [$like, $like, $like]
    );
    foreach ($rows as $r) {
        $results[] = [
            'id' => (int) $r['id'],
            'label' => person_label($r),
            'name' => full_name($r),
            'meta' => $r['person_code'] . ', born ' . format_date($r['dob']),
        ];
    }
}

echo json_encode(['results' => $results], JSON_UNESCAPED_UNICODE);
