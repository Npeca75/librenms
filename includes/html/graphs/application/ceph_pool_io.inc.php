<?php

$ds_in = 'rbytes';
$in_text = 'Read';
$ds_out = 'wrbytes';
$out_text = 'Write';

$format = 'bytes';

$rrd_filename = Rrd::name($device['hostname'], ['app', 'ceph', $app->app_id, 'pool', $vars['pool']]);

$colour_area_in = 'FF3300';
$colour_line_in = 'FF0000';
$colour_area_out = 'FF6633';
$colour_line_out = 'CC3300';

$colour_area_in_max = 'FF6633';
$colour_area_out_max = 'FF9966';

$unit_text = 'Bytes I/O';

require 'includes/html/graphs/generic_duplex.inc.php';
