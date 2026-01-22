<?php

echo '<table class="table table-hover table-condensed">
    <thead>
        <tr>
            <th>Status</th>
            <th>Port</th>
            <th>Address</th>
            <th>Description</th>
        </tr>
    </thead>';

foreach (['ipv4', 'ipv6'] as $adType) {
    $selCol = ($adType == 'ipv4') ? $adType . '_address' : $adType . '_compressed';
    $sortPrefix = ($adType == 'ipv4') ? 'INET_ATON(' : 'INET6_ATON(';
    $fromDB = dbFetchRows('
        SELECT ports.port_id, ports.ifName, ports.ifAlias, ports.ifOperStatus, ports.ifAdminStatus, ' . $adType . '_addresses.' . $selCol . ', ' . $adType . '_addresses.' . $adType . '_prefixlen
        FROM ports, ' . $adType . '_addresses, devices
        WHERE ports.device_id=devices.device_id AND ' . $adType . '_addresses.port_id=ports.port_id AND devices.device_id = ?
        ORDER BY ports.ifName,' . $sortPrefix . $adType . '_addresses.' . $selCol . ')', [$device['device_id']]);

    foreach ($fromDB as $data) {
        $port[$data['port_id']]['status'] = ($data['ifAdminStatus'] == 'down') ? 'admindown' : (($data['ifOperStatus'] == 'up') ? 'up' : 'down');
        $port[$data['port_id']]['name'] = $data['ifName'];
        $port[$data['port_id']]['alias'] = $data['ifAlias'];
        $port[$data['port_id']][$adType][$data[$selCol]] = $data[$adType . '_prefixlen'];
    }
}

foreach ($port as $portId => $row) {
    $label = $row['status'] == 'admindown' ? 'warning' : (($row['status'] == 'down') ? 'danger' : 'success');
    echo '<tr><td class="text-left" style><span class="alert-status label-' . $label . '" style="float:left;margin-right:10px;"></span>' . $row['status'] . '</td>';
    echo '<td class="interface-' . $row['status'] . '">' . generate_port_link(cleanPort(get_port_by_id($portId))) . '</td>';
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
