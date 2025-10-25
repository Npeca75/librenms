<?php

/**
 * VlanSearch.inc.php
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2025  Peca Nesovanovic
 * @author  Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */
global $plugin_name;
$plugin_name = 'VlanSearch';

$query_params = [];
if (! Auth::user()->hasGlobalRead()) {
    $device_ids = Permissions::devicesForUser()->toArray() ?: [0];
    $whereDev = ' AND `devices`.`device_id` IN ' . '(' . implode(',', array_fill(0, $device_ids, '?')) . ')';
    $whereVlan = ' WHERE `vlans`.`device_id` IN ' . '(' . implode(',', array_fill(0, $device_ids, '?')) . ')';
    $query_params = array_merge($query_params, $device_ids);
}

$portsSelected = 'All';
if ($_POST) {
    $vlanSelected = $_POST['vlan_to_search'] ?? 0;
    $groupSelected = $_POST['group_to_search'] ?? 0;
    $vlan_to_search_statement = ' AND ports_vlans.vlan="' . $vlanSelected . '"';

    $portsSelected = $_POST['ports_to_search'];
    $exclude_statement = '';

    if ($portsSelected == 'Access') {
        $exclude_statement = ' AND ports_vlans.untagged !="0"';
    } elseif ($portsSelected == 'Trunk') {
        $exclude_statement = ' AND ports_vlans.untagged !="1"';
    }

    if (! empty($groupSelected)) {
        $whereDev = ' AND `devices`.`device_id` IN (SELECT device_id FROM device_group_device WHERE device_group_id=' . $groupSelected . ')';
    }
    $query = '
    SELECT
        ports_vlans.device_id, ports_vlans.port_id, ports_vlans.vlan, ports_vlans.untagged,
        devices.sysName, devices.hostname,
        ports.ifName, ports.ifAlias, ports.ifSpeed, ports.ifOperStatus, ports.ifAdminStatus,
        vlans.vlan_name
    FROM
        ports_vlans, devices, ports, vlans
    WHERE
        ports_vlans.device_id=devices.device_id AND ports_vlans.port_id=ports.port_id AND vlans.device_id=devices.device_id AND vlans.vlan_vlan=ports_vlans.vlan'
        . $exclude_statement
        . $vlan_to_search_statement
        . $whereDev . '
    ORDER BY
        devices.sysName, ports.ifName;';

    $fromDB = array_map(fn ($x) => (array) $x, DB::select($query, $query_params));
    $result = [];
    foreach ($fromDB as $row) {
        $device_id = $row['device_id'];
        $port_id = $row['port_id'];
        $vlan = $row['vlan'];
        $untagged = $row['untagged'];
        $vlan_name = $row['vlan_name'];
        $sysName = $row['sysName'];
        $hostname = $row['hostname'];
        $ifName = $row['ifName'];
        $ifAlias = $row['ifAlias'];
        $ifSpeed = $row['ifSpeed'];
        $ifOperStatus = $row['ifOperStatus'];
        $ifAdminStatus = $row['ifAdminStatus'];

        $result[$device_id]['sysName'] = $sysName;
        $result[$device_id]['hostname'] = $hostname;
        $result[$device_id]['vlan'][$vlan]['vlan_name'] = $vlan_name;
        $result[$device_id]['vlan'][$vlan]['ports'][$port_id]['untagged'] = $untagged;
        $result[$device_id]['vlan'][$vlan]['ports'][$port_id]['ifName'] = $ifName;
        $result[$device_id]['vlan'][$vlan]['ports'][$port_id]['ifAlias'] = $ifAlias;
        $result[$device_id]['vlan'][$vlan]['ports'][$port_id]['ifSpeed'] = $ifSpeed;
        $result[$device_id]['vlan'][$vlan]['ports'][$port_id]['ifOperStatus'] = $ifOperStatus;
        $result[$device_id]['vlan'][$vlan]['ports'][$port_id]['ifAdminStatus'] = $ifAdminStatus;
    }
}

$form = '<form action="/plugin/v1/' . $plugin_name . '" method="post">';
$form .= '&emsp;<label for="vlan_to_search">Vlan ID: </label><select name="vlan_to_search">';

$query = 'SELECT vlan_vlan FROM vlans ' . $whereVlan . ' GROUP BY vlan_vlan ORDER BY vlan_vlan';
$fromDB = array_map(fn ($x) => (array) $x, DB::select($query, $query_params));

foreach ($fromDB as $row) {
    $optSel = ($vlanSelected == $row['vlan_vlan']) ? 'selected' : '';
    $form .= '<option ' . $optSel . ' value=' . $row['vlan_vlan'] . '>' . $row['vlan_vlan'] . '</option>';
}
$form .= '</select>';

$form .= '&emsp;<label for="ports_to_search">Type:</label><select name="ports_to_search">';
foreach (['All', 'Trunk', 'Access'] as $portType) {
    $optSel = ($portsSelected == $portType) ? 'selected' : '';
    $form .= '<option ' . $optSel . ' value=' . $portType . '>' . $portType . '</option>';
}
$form .= '</select>';

if (Auth::user()->hasGlobalRead()) {
    $form .= '&emsp;<label for="group_to_search">Group:</label><select name="group_to_search">';
    $query = 'SELECT `id`, `name` FROM device_groups ORDER BY `name`';
    $fromDB = array_map(fn ($x) => (array) $x, DB::select($query, $query_params));

    $form .= '<option value=0>All</option>';
    foreach ($fromDB as $row) {
        $optSel = ($groupSelected == $row['id']) ? 'selected' : '';
        $form .= '<option ' . $optSel . ' value=' . $row['id'] . '>' . $row['name'] . '</option>';
    }
    $form .= '</select>';
}

$form .= csrf_field();
$form .= '&emsp;<input style="padding: 5px 5px;background-color:#e0e0e0;" name="search" value="search" type="submit"><br><br><br>';
echo $form;

if ($result) {
    echo '<style type="text/css">
    .tg  {border-collapse:collapse;border-spacing:0;}
    .tg td{font-family:Arial, sans-serif;font-size:14px;padding:3px 3px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
    .tg th{font-family:Arial, sans-serif;font-size:14px;font-weight:normal;padding:8px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
    .tg .tg-device{background-color:black;color:white;font-weight:bold;vertical-align:center;text-align:right;}
    .tg .tg-left{background-color:#e0e0e0;vertical-align:center}
    .tg .tg-rowOk{background-color:#ffffff;vertical-align:center}
    .tg .tg-rowNok{background-color:#ffe0e0;vertical-align:center}
    a {color:inherit; text-decoration:underline;}
    </style>';

    foreach ($result as $device_id => $device_data) {
        $sysName = $device_data['sysName'];
        $hostname = $device_data['hostname'];

        $table = '<table width=50% class="tg"><tr><th colspan=6 class="tg-device">' . $sysName . ' :: <A HREF="/device/device=' . $device_id . '">' . $hostname . '</A></th></tr>';
        foreach ($device_data['vlan'] as $vlan_data) {
            $vlan_name = $vlan_data['vlan_name'];
            $numinterfaces = count($vlan_data['ports']) + 1;
            $table .= '<tr class="tg-left"><td width="15%" rowspan=' . $numinterfaces . '><A HREF="/device/' . $device_id . '/vlans">' . $vlan_name . '</A></td></tr>';
            $n = 0;
            foreach ($vlan_data['ports'] as $port_id => $port_data) {
                $ifName = \LibreNMS\Util\Rewrite::shortenIfName(\LibreNMS\Util\Rewrite::normalizeIfName($port_data['ifName']));
                $ifAlias = $port_data['ifAlias'];
                $rowPName = (strtoupper($ifName) != strtoupper((string) $ifAlias)) ? $ifName . '</br>' . $ifAlias : $ifName;
                $formatRow = ($port_data['ifOperStatus'] == 'down') ? 'tg-rowNok' : 'tg-rowOk';
                $rowPType = $port_data['untagged'] ? 'access' : 'trunk';
                $rowPStatus = ($port_data['ifAdminStatus'] == 'down') ? 'admin down' : (($port_data['ifOperStatus'] == 'up') ? 'up' : 'down');
                $ifSpeed = ($port_data['ifSpeed'] / 1000000) . ' Mbit';
                $table .= '<tr class="' . $formatRow . '">';
                $table .= '<td><A HREF="/device/device=' . $device_id . '/tab=port/port=' . $port_id . '">' . $rowPName . '</A>';
                $table .= '<td width="7%">' . $rowPType . '</td><td width="10%" align="center">' . $rowPStatus . '</td><td width="12%">' . $ifSpeed . '</td>';
                $table .= '</tr>';
            }
        }
        $table .= '</table><br>';
        echo $table;
    }
}
