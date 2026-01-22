<?php

use LibreNMS\Exceptions\JsonAppException;
use LibreNMS\Exceptions\JsonAppMissingKeysException;
use LibreNMS\RRD\RrdDefinition;
use LibreNMS\Util\Number;

$name = 'docker';
$version = 1;
$output = 'OK';

try {
    $result = json_app_get($device, $name, 1);
    $version = $result['version'];
    $docker_data = $result['data'];
} catch (JsonAppMissingKeysException $e) {
    $docker_data = $e->getParsedJson();
} catch (JsonAppException $e) {
    echo PHP_EOL . $name . ':' . $e->getCode() . ':' . $e->getMessage() . PHP_EOL;
    update_application($app, $e->getCode() . ':' . $e->getMessage(), []); // Set empty metrics and error message

    return;
}

$version = intval($version);

if ($version == 1) {
    $output = 'LEGACY';
} elseif ($version == 2) {
    $output = 'OK';
} else {
    $output = 'UNSUPPORTED';
}

$rrd_name = ['app', $name, $app->app_id];
$rrd_def = RrdDefinition::make()
    ->addDataset('cpu_usage', 'GAUGE', 0, 100)
    ->addDataset('pids', 'GAUGE', 0)
    ->addDataset('mem_perc', 'GAUGE', 0, 100)
    ->addDataset('mem_used', 'GAUGE', 0)
    ->addDataset('net_tx', 'GAUGE', 0)
    ->addDataset('net_rx', 'GAUGE', 0)
    ->addDataset('io_write', 'GAUGE', 0)
    ->addDataset('io_read', 'GAUGE', 0);

$totals = [
    'created' => 0,
    'restarting' => 0,
    'running' => 0,
    'removing' => 0,
    'paused' => 0,
    'exited' => 0,
    'dead' => 0,
];
$metrics = [];
$containerNames = [];
foreach ($docker_data as $data) {
    $containerNames[] = $container = $data['container'];

    $status = isset($data['state']['status']) ? strtolower($data['state']['status']) : null;
    if ($status) {
        $totals[$status] += 1;
    }

    $fields = [
        'cpu_usage' => (float) $data['cpu'],
        'pids' => $data['pids'],
        'mem_perc' => (float) $data['memory']['perc'],
        'mem_used' => (int) Number::toBytes($data['memory']['used']),
        'net_tx' => (int) Number::toBytes($data['net']['tx']),
        'net_rx' => (int) Number::toBytes($data['net']['rx']),
        'io_write' => (int) Number::toBytes($data['io']['w']),
        'io_read' => (int) Number::toBytes($data['io']['r']),
    ];

    $rrd_name = ['app', $name, $app->app_id, $container];
    $metrics[$container] = $fields;
    $tags = ['name' => $container, 'app_id' => $app->app_id, 'rrd_def' => $rrd_def, 'rrd_name' => $rrd_name];
    app('Datastore')->put($device, 'app', $tags, $fields);
}

$rrd_def = RrdDefinition::make();
foreach ($totals as $status => $value) {
    $rrd_def->addDataset($status, 'GAUGE', 0);
}
$rrd_name = ['app', $name, $app->app_id];
$tags = ['name' => $name, 'app_id' => $app->app_id, 'rrd_def' => $rrd_def, 'rrd_name' => $rrd_name];
app('Datastore')->put($device, 'app', $tags, $totals);

$metrics['total'] = $totals;

$app->data = ['containers' => $containerNames];

update_application($app, $output, $metrics);
