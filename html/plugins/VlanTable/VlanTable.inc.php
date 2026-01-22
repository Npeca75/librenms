<?php

/**
 * VlanTable.inc.php
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2025  Peca Nesovanovic
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */
global $plugin_name;
$plugin_name = 'VlanTable';

echo '<style type="text/css">
.tg  {border-collapse:collapse;border-spacing:0;}
.tg td{font-family:Arial, sans-serif;font-size:14px;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
.tg th{font-family:Arial, sans-serif;font-size:14px;font-weight:normal;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
.tg .tg-header{background-color:#282828;color:white;font-weight:bold;vertical-align:top;text-align:center}
.tg .tg-white{background-color:#ffffff;vertical-align:top}
.tg .tg-gray{background-color:#e0e0e0;vertical-align:top}
</style>
';

//limit to USER accessible devices
$query_params = [];
if (! Auth::user()->hasGlobalRead()) {
    $device_ids = Permissions::devicesForUser()->toArray() ?: [0];
    $whereDev = ' AND `device_group_device`.`device_id` IN ' . '(' . implode(',', array_fill(0, $device_ids, '?')) . ')';
    $whereVlan = ' AND `vlans`.`device_id` IN ' . '(' . implode(',', array_fill(0, $device_ids, '?')) . ')';
    $query_params = array_merge($query_params, $device_ids);
}

$vlan_nr_to_exclude_from_report = [];
$device_groups = [];
$device_groups_members = [];
$device_members_groups = [];
$group_ids_to_exclude = [];

if ($_POST) {
    foreach ($_POST['group_selected'] as $gid) {
        $group_ids_to_exclude[$gid] = 1;
    }
    if ($_POST['vlans_to_exclude']) {
        $vlans_to_exclude = $_POST['vlans_to_exclude'];
    }
} else {
    $vlans_to_exclude = '';
}
foreach (explode(',', (string) $vlans_to_exclude) as $vlan) {
    if (preg_match("/(\d+)-(\d+)/", $vlan, $matches)) {
        $first_vlan = $matches[1];
        $last_vlan = $matches[2];
        if ($last_vlan > $first_vlan) {
            for ($counter = $first_vlan; $counter <= $last_vlan; $counter++) {
                $vlan_nr_to_exclude_from_report[$counter] = 1;
            }
        } else {
            for ($counter = $first_vlan; $counter >= $last_vlan; $counter--) {
                $vlan_nr_to_exclude_from_report[$counter] = 1;
            }
        }
    } else {
        $vlan_nr_to_exclude_from_report[intval($vlan)] = 1;
    }
}
$query = 'SELECT
    device_group_device.device_id,
    device_group_device.device_group_id,
    device_groups.name
FROM
    device_groups,
    device_group_device
WHERE
    device_group_device.device_group_id=device_groups.id'
    . $whereDev . '
ORDER BY name;';

$fromDB = array_map(fn ($x) => (array) $x, DB::select($query, $query_params));

foreach ($fromDB as $line) {
    $device_id = $line['device_id'];
    $device_group_id = $line['device_group_id'];
    $device_group_name = $line['name'];
    if (! array_key_exists($device_group_id, $device_groups)) {
        $device_groups[$device_group_id] = $device_group_name;
    }
    $device_groups_members[$device_group_id][$device_id] = 1;
    $device_members_groups[$device_id][$device_group_id] = 1;
}

$form = '<form action="/plugin/v1/' . $plugin_name . '" method="post">';
$form .= '<table class="tg">';
$form .= '<tr><th colspan=2 class="tg-header">Exclude groups</th>';
foreach ($device_groups as $gid => $gname) {
    $format = ($n++ % 2) ? 'tg-gray' : 'tg-white'; // Set row format
    $checked = '';
    if (array_key_exists($gid, $group_ids_to_exclude)) {
        $checked = ' checked ';
    }
    $form .= '<tr><td class=" ' . $format . '">';
    $form .= '<input type="checkbox" name="group_selected[]" value="' . $gid . '" ' . $checked . '>';
    $form .= '<td class="' . $format . '">' . $gname . '</tr>';
}

$format = ($n++ % 2) ? 'tg-gray' : 'tg-white'; // Set row format
$form .= '<tr><td colspan=2 class="' . $format . '">Exclude vlans:<input type="textbox" size=50 name="vlans_to_exclude" value="' . $vlans_to_exclude . '" ></tr>';
$form .= '<tr><td colspan=2 class="tg-header"><input name="exclude" value="Search" type="submit"></th></tr>';
$form .= csrf_field();
$form .= '</table></form><br>';
echo $form;

$vlans = [];
$data = [];
$query = 'SELECT
    vlans.vlan_id,
    vlans.vlan_vlan,
    vlans.vlan_name,
    devices.sysName,
    vlans.device_id,
    vlans.vlan_type
FROM
    vlans,
    devices
WHERE
    devices.device_id = vlans.device_id'
    . $whereVlan . '
ORDER BY vlan_vlan,vlan_name';

$fromDB = array_map(fn ($x) => (array) $x, DB::select($query, $query_params));

foreach ($fromDB as $row) {
    $device_id = $row['device_id'];
    $device_is_excluded = 0;
    foreach ($device_members_groups[$device_id] as $gid => $foo) {
        if (array_key_exists($gid, $group_ids_to_exclude)) {
            $device_is_excluded = 1;
        }
    }
    if (empty($device_is_excluded)) {
        if (! array_key_exists($row['vlan_vlan'], $vlan_nr_to_exclude_from_report)) {
            $data[$row['vlan_vlan']][$row['vlan_name']][" <A HREF='/device/device=$row[device_id]'>$row[sysName]</A>"]++;
            $key = $row['sysName'] . $row['vlan_vlan'];
            $vlans[$key]['name'] = $row['vlan_name'];
            $vlans[$key]['vlan'] = $row['vlan_vlan'];
            $vlans[$key]['device'] = $row['sysName'];
            $vlans[$key]['type'] = $row['type'];
        }
    }
}

echo '<table class="tg"><tr><th class="tg-header">vlan</th><th class="tg-header" width=10%>name</th><th class="tg-header">devices</th></tr>';
$n = 0;
foreach ($data as $vlan_nr => $vlan_name_array) {
    $format = ($n++ % 2) ? 'tg-gray' : 'tg-white'; // Set row format
    echo '<tr><td class="' . $format . '" rowspan="' . count($vlan_name_array) . '">' . $vlan_nr . '</td>';
    foreach ($vlan_name_array as $vlan_name => $device_array) {
        echo "<td class=\"$format\">$vlan_name</td><td  class=\"$format\"> ";
        $names = [];
        foreach ($device_array as $device => $tmp) {
            $names[] = $device;
        }
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);
        echo implode(', ', $names);
        echo '</td></tr><tr>';
    }
    echo '</tr>';
}
echo '</table>';
