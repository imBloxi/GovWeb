<?php
require __DIR__ . '/app/bootstrap.php';

$user = require_can('self.view');
$person = $user['civilian_id']
    ? db_one('SELECT * FROM civilians WHERE id = ?', [$user['civilian_id']])
    : null;

page_start('My record', 'me');

if (!$person) {
    page_header('My record');
    echo empty_state('Your account is not linked to a civil register entry yet. Ask a registrar to link it.');
} else {
    $self = true;
    include APP_ROOT . '/app/views/person_record.php';
}

page_end();
