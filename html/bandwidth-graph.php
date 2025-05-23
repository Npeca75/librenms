<?php

/**
 * LibreNMS
 *
 *   This file is part of LibreNMS.
 *
 * @copyright  (C) 2006 - 2012 Adam Armstrong
 */
ini_set('allow_url_fopen', 0);

$init_modules = ['web', 'auth'];
require realpath(__DIR__ . '/..') . '/includes/init.php';

$urlargs = [
    'type' => 'bill_historictransfer',
    'id' => $_GET['bill_id'],
    'width' => $_GET['x'],
    'height' => $_GET['y'],
    'imgtype' => $_GET['type'],
];
if (is_numeric($_GET['bill_hist_id'])) {
    $urlargs['bill_hist_id'] = $_GET['bill_hist_id'];
} elseif (is_numeric($_GET['from']) && is_numeric($_GET['to'])) {
    $urlargs['from'] = $_GET['from'];
    $urlargs['to'] = $_GET['to'];
}

$url = Config::get('base_url') . 'graph.php?';
$i = 0;
foreach ($urlargs as $name => $value) {
    if ($i++ > 0) {
        $url .= '&';
    }
    $url .= "$name=$value";
}

header("Location: $url", false, 301);
exit;
