<?php

use App\Facades\PortCache;
use App\Models\Port;
use LibreNMS\Enum\IfOperStatus;

echo '<table class="table table-hover table-condensed">
    <thead>
        <tr>
            <th>Status</th>
            <th>Port</th>
            <th>Address</th>
            <th>Description</th>
        </tr>
    </thead>';

foreach (['ipv4', 'ipv6'] as $addType) {
    $selCol = ($addType == 'ipv4') ? $addType . '_address' : $addType . '_compressed';
    $sort = ($addType == 'ipv4') ? 'INET_ATON(' : 'INET6_ATON(';
    $sort = $sort . $addType . '_addresses.' . $selCol . ')';

    $fromDB = Port::where('device_id', $device['device_id'])
            ->select(
                'ports.port_id', 'ports.ifName', 'ports.ifAlias', 'ports.ifOperStatus', 'ports.ifAdminStatus',
                $addType . '_addresses.' . $selCol, $addType . '_addresses.' . $addType . '_prefixlen'
            )->join($addType . '_addresses', 'ports.port_id', '=', $addType . '_addresses.port_id')
            ->orderBy('ports.ifName')->orderByRaw($sort)
            ->get()->toArray();
    foreach ($fromDB as $data) {
        $port[$data['port_id']]['status'] = ($data['ifAdminStatus'] == 'down') ? 'admindown' : (($data['ifOperStatus'] == IfOperStatus::Up) ? 'up' : 'down');
        $port[$data['port_id']]['name'] = $data['ifName'];
        $port[$data['port_id']]['alias'] = $data['ifAlias'];
        $port[$data['port_id']][$addType][$data[$selCol]] = $data[$addType . '_prefixlen'];
    }
}

foreach ($port as $portId => $row) {
    $label = $row['status'] == 'admindown' ? 'warning' : (($row['status'] == 'down') ? 'danger' : 'success');
    echo '<tr><td class="text-left" style><span class="alert-status label-' . $label . '" style="float:left;margin-right:10px;"></span>' . $row['status'] . '</td>';
    echo '<td class="interface-' . $row['status'] . '">' . generate_port_link(cleanPort(PortCache::get($portId)->toArray())) . '</td>';
    echo '<td class="interface-' . $row['status'] . '">';
    foreach ($row['ipv4'] as $address => $prefix) {
        echo $address . '/' . $prefix . '</br>';
    }
    foreach ($row['ipv6'] as $address => $prefix) {
        echo $address . '/' . $prefix . '</br>';
    }
    echo '</td><td class="interface-' . $row['status'] . '">' . $row['alias'] . '</td>';
}

echo '</table>';
