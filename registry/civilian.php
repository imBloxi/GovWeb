<?php
require dirname(__DIR__) . '/app/bootstrap.php';

require_can('civilians.view');

$person = db_one('SELECT * FROM civilians WHERE id = ?', [input_int('id') ?? 0]) ?? abort(404);

page_start(full_name($person), 'civilians');
echo '<p class="crumbs"><a href="' . e(url('registry/civilians.php')) . '">' . icon('arrow-left') . 'Civilians</a></p>';
$self = false;
include APP_ROOT . '/app/views/person_record.php';
page_end();
