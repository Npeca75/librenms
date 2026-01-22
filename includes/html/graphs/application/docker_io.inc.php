<?php

$name = 'docker';
$polling_type = 'app';

$container = $vars['container'] ?? '';

$unit_text = 'Bytes';

$ds_in = 'io_read';
$in_text = 'Read';
$ds_out = 'io_write';
$out_text = 'Write';

$print_total = false;

$colour_line_in = $colour_area_in = '66FF66';
$colour_line_out = $colour_area_out = 'FF3333';

$colour_area_in_max = '0066FF';
$colour_area_out_max = 'FF9966';

$rrd_filename = Rrd::name($device['hostname'], [
    $polling_type,
    $name,
    $app->app_id,
    $container,
]);

if (! Rrd::checkRrdExists($rrd_filename)) {
    d_echo('RRD ' . $rrd_filename . ' not found');
}
require 'includes/html/graphs/generic_duplex.inc.php';
