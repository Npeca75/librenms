<?php

use LibreNMS\Util\IPv6;

$param = [];

if (! Auth::user()->hasGlobalRead()) {
    $device_ids = Permissions::devicesForUser()->toArray() ?: [0];
    $where .= ' AND `D`.`device_id` IN ' . dbGenPlaceholders(count($device_ids));
    $param = array_merge($param, $device_ids);
}

$sType = substr($vars['search_type'], 0, -1); // ipv4r ipv4
[$route, $prefix] = explode('/', $vars['route']);

if ($sType == 'ipv6' && ! empty($route)) {
    $route6 = new IPv6($route);
    $route = $route6->uncompressed();
}
$sql = ' FROM `route` AS R, `ports` AS P, `devices` AS D';
$sql .= " WHERE P.port_id = R.port_id AND D.device_id = R.device_id AND inetCidrRouteDestType='$sType' $where";

if (! empty($route)) {
    $sql .= ' AND inetCidrRouteDest LIKE ?';
    $param[] = '%' . $route . '%';
}
if (! empty($prefix)) {
    $sql .= " AND inetCidrRoutePfxLen='$prefix'";
}

if (is_numeric($vars['device_id'])) {
    $sql .= ' AND P.device_id = ?';
    $param[] = $vars['device_id'];
}

if ($vars['interface']) {
    $sql .= ' AND P.ifDescr LIKE ?';
    $param[] = $vars['interface'];
}

$count_sql = "SELECT COUNT(`route_id`) $sql";
$total = dbFetchCell($count_sql, $param);
if (empty($total)) {
    $total = 0;
}

if (! isset($sort) || empty($sort)) {
    $sort = '`hostname` ASC';
}

$sql .= " ORDER BY $sort";

if (isset($current)) {
    $limit_low = (($current * $rowCount) - $rowCount);
    $limit_high = $rowCount;
}

if ($rowCount != -1) {
    $sql .= " LIMIT $limit_low,$limit_high";
}

$sql = "SELECT *,`P`.`ifDescr` AS `interface`, `R`.`inetCidrRouteDest` $sql";

foreach (dbFetchRows($sql, $param) as $interface) {
    $speed = \LibreNMS\Util\Number::formatSi($interface['ifSpeed'], 2, 3, 'bps');
    $type = \LibreNMS\Util\Rewrite::normalizeIfType($interface['ifType']);

    if ($sType == 'ipv6') {
        $dst6 = new IPv6($interface['inetCidrRouteDest'] . '/' . $interface['inetCidrRoutePfxLen']);
        $dst = $dst6->compressed() . '/' . $interface['inetCidrRoutePfxLen'];
        $hop6 = new IPv6($interface['inetCidrRouteNextHop']);
        $hop = $hop6->compressed() . '</br>' . $interface['ifAlias'];
    } else {
        $dst = $interface['inetCidrRouteDest'] . '/' . $interface['inetCidrRoutePfxLen'];
        $hop = $interface['inetCidrRouteNextHop'] . '</br>' . $interface['ifAlias'];
    }

    if ($interface['in_errors'] > 0 || $interface['out_errors'] > 0) {
        $error_img = generate_port_link($interface, "<i class='fa fa-flag fa-lg' style='color:red' aria-hidden='true'></i>", 'errors');
    } else {
        $error_img = '';
    }

    if (port_permitted($interface['port_id'])) {
        $interface = cleanPort($interface, $interface);
        $row = [
            'hostname'  => generate_device_link($interface),
            'interface' => generate_port_link($interface) . ' ' . $error_img,
            'dst'       => $dst,
            'hop'       => $hop,
        ];
        $response[] = $row;
    }
}//end foreach

$output = [
    'current'  => $current,
    'rowCount' => $rowCount,
    'rows'     => $response,
    'total'    => $total,
];
echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
