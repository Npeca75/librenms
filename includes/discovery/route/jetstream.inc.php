<?php
/*
 * LibreNMS discovery module for Jetstream IPv4/IPv6 Routes
 *
 *
 * @package    LibreNMS
 * @link       https://www.librenms.org
 *
 * @copyright  2022 Peca Nesovanovic
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */
use App\Models\Port;
use LibreNMS\Util\IPv4;
use LibreNMS\Util\IPv6;

$oids = SnmpQuery::walk('TPLINK-STATICROUTE-MIB::tpStaticRouteConfigTable')->table(3);
if (! empty($oids)) {
    $oids = array_shift($oids);
    $oids = array_shift($oids);

    foreach ($oids as $data) {
        $inetCidrRouteDest = $data['TPLINK-STATICROUTE-MIB::tpStaticRouteItemDesIp'];
        $inetCidrRoutePfxLen = IPv4::netmask2cidr($data['TPLINK-STATICROUTE-MIB::tpStaticRouteItemMask']); //CONVERT
        $inetCidrRouteNextHop = $data['TPLINK-STATICROUTE-MIB::tpStaticRouteItemNextIp'];
        d_echo('ROUTE: Jetstream IPv4 from MIB: ' . $inetCidrRouteDest);

        unset($entryClean);
        $entryClean['device_id'] = $device['device_id'];
        $entryClean['inetCidrRouteDestType'] = 'ipv4';
        $entryClean['inetCidrRouteDest'] = $inetCidrRouteDest;
        $entryClean['inetCidrRoutePfxLen'] = $inetCidrRoutePfxLen;
        $entryClean['inetCidrRouteNextHopType'] = 'ipv4';
        $entryClean['inetCidrRouteNextHop'] = $inetCidrRouteNextHop;
        $entryClean['inetCidrRouteNextHopAS'] = '0';
        $entryClean['inetCidrRouteProto'] = '3';
        $entryClean['inetCidrRouteType'] = '4';
        $entryClean['context_name'] = '';
        $entryClean['updated_at'] = $update_timestamp;

        if (preg_match('/^vlan([\d]+)$/i', $data['TPLINK-STATICROUTE-MIB::tpStaticRouteItemInterfaceName'], $intName)) { //other TP-LINKs
            $metric = $data['TPLINK-STATICROUTE-MIB::tpStaticRouteItemDistance'];
        } else {
            preg_match('/^vlan([\d]+)$/i', $data['TPLINK-STATICROUTE-MIB::tpStaticRouteItemDistance'], $intName); //T1600-g28
            $metric = $data['TPLINK-STATICROUTE-MIB::tpStaticRouteItemInterfaceName'];
        }

        if (! empty($intName)) {
            $ifIndex = $intName[1];
            $entryClean['inetCidrRouteMetric1'] = $metric;
            $entryClean['inetCidrRouteIfIndex'] = $ifIndex;

            $portId = get_port_by_index_cache($device['device_id'], $ifIndex)['port_id'];
            $entryClean['port_id'] = $portId;
            $entryClean['inetCidrRoutePolicy'] = $inetCidrRoutePolicy = 'zeroDotZero.' . $ifIndex;

            $current = $mixed['']['ipv4'][$inetCidrRouteDest][$inetCidrRoutePfxLen][$inetCidrRoutePolicy]['ipv4'][$inetCidrRouteNextHop];
            if (isset($current) && isset($current['db']) && count($current['db']) > 0 && $delete_row[$current['db']['route_id']] != 1) {
                //we already have a row in DB
                $entryClean['route_id'] = $current['db']['route_id'];
                $update_row[] = $entryClean;
            } else {
                $entry['created_at'] = ['NOW()'];
                $create_row[] = $entryClean;
            }
        }
    }
}

$oids = snmpwalk_cache_oid($device, 'TPLINK-IPV6STATICROUTE-MIB::tpIPv6StaticRouteConfigTable', [], 'TPLINK-IPV6STATICROUTE-MIB');
if (isset($oids)) {
    foreach ($oids as $data) {
        $inetCidrRouteDest = normalize_snmp_ip_address(str_replace(' ', ':', trim($data['tpIPv6StaticRouteItemDesIp'])));
        $inetCidrRouteNextHop = normalize_snmp_ip_address(str_replace(' ', ':', trim($data['tpIPv6StaticRouteItemNexthop'])));
        $inetCidrRoutePfxLen = $data['tpIPv6StaticRouteItemPrefixLen'];
        d_echo('ROUTE: Jetstream IPv6 from MIB: ' . $inetCidrRouteDest);

        unset($entryClean);
        $entryClean['device_id'] = $device['device_id'];
        $entryClean['inetCidrRouteDestType'] = 'ipv6';
        $entryClean['inetCidrRouteDest'] = $inetCidrRouteDest;
        $entryClean['inetCidrRoutePfxLen'] = $inetCidrRoutePfxLen;
        $entryClean['inetCidrRouteNextHopType'] = 'ipv6';
        $entryClean['inetCidrRouteNextHop'] = $inetCidrRouteNextHop;
        $entryClean['inetCidrRouteNextHopAS'] = '0';
        $entryClean['inetCidrRouteProto'] = '3';
        $entryClean['inetCidrRouteType'] = '4';
        $entryClean['inetCidrRouteMetric1'] = intval($data['tpIPv6StaticRouteItemDistance']);
        $entryClean['context_name'] = '';
        $entryClean['updated_at'] = $update_timestamp;

        if (preg_match('/\d+/', $data['tpIPv6StaticRouteItemInterfaceName'], $out)) {
            $ifIndex = $out[0];
            $entryClean['inetCidrRouteIfIndex'] = $ifIndex;
            $portId = get_port_by_index_cache($device['device_id'], $ifIndex)['port_id'];
            $entryClean['port_id'] = $portId;
            $entryClean['inetCidrRoutePolicy'] = $inetCidrRoutePolicy = 'zeroDotZero.' . $ifIndex;

            $current = $mixed['']['ipv6'][$inetCidrRouteDest][$inetCidrRoutePfxLen][$inetCidrRoutePolicy]['ipv6'][$inetCidrRouteNextHop];
            if (isset($current) && isset($current['db']) && count($current['db']) > 0 && $delete_row[$current['db']['route_id']] != 1) {
                //we already have a row in DB
                $entryClean['route_id'] = $current['db']['route_id'];
                $update_row[] = $entryClean;
            } else {
                $entry['created_at'] = ['NOW()'];
                $create_row[] = $entryClean;
            }
        }
    }
}

//emulate RouteTable
$portIds = Port::where('device_id', $device['device_id'])
    ->select('ports.port_id', 'ports.ifIndex', 'ipv4_addresses.ipv4_address', 'ipv4_addresses.ipv4_prefixlen')
    ->join('ipv4_addresses', 'ports.port_id', '=', 'ipv4_addresses.port_id')
    ->get();

if (isset($portIds)) {
    foreach ($portIds as $fromDb) {
        $ipv4 = new IPv4($fromDb['ipv4_address'] . '/' . $fromDb['ipv4_prefixlen']);
        $inetCidrRouteDest = $ipv4->getNetworkAddress();
        $inetCidrRoutePfxLen = $fromDb['ipv4_prefixlen'];
        $inetCidrRouteNextHop = '0.0.0.0';
        $inetCidrRoutePolicy = 'zeroDotZero.' . $fromDb['ifIndex'];

        d_echo('ROUTE: Jetstream IPv4 calculated: ' . $inetCidrRouteDest);

        unset($entryClean);
        $entryClean['device_id'] = $device['device_id'];
        $entryClean['inetCidrRouteDestType'] = 'ipv4';
        $entryClean['inetCidrRouteDest'] = $inetCidrRouteDest;
        $entryClean['inetCidrRoutePfxLen'] = $inetCidrRoutePfxLen;
        $entryClean['inetCidrRouteNextHopType'] = 'ipv4';
        $entryClean['inetCidrRouteNextHop'] = $inetCidrRouteNextHop;
        $entryClean['inetCidrRouteNextHopAS'] = '0';
        $entryClean['inetCidrRouteProto'] = '2';
        $entryClean['inetCidrRouteType'] = '3';
        $entryClean['inetCidrRoutePolicy'] = $inetCidrRoutePolicy;
        $entryClean['inetCidrRouteMetric1'] = '1';
        $entryClean['context_name'] = '';
        $entryClean['updated_at'] = $update_timestamp;
        $entryClean['port_id'] = $fromDb['port_id'];
        $entryClean['inetCidrRouteIfIndex'] = $fromDb['ifIndex'];

        $current = $mixed['']['ipv4'][$inetCidrRouteDest][$inetCidrRoutePfxLen][$inetCidrRoutePolicy]['ipv4'][$inetCidrRouteNextHop];
        if (isset($current) && isset($current['db']) && count($current['db']) > 0 && $delete_row[$current['db']['route_id']] != 1) {
            //we already have a row in DB
            $entryClean['route_id'] = $current['db']['route_id'];
            $update_row[] = $entryClean;
        } else {
            $entry['created_at'] = ['NOW()'];
            $create_row[] = $entryClean;
        }
    }
}

//emulate RouteTable
$portIds = Port::where('device_id', $device['device_id'])
    ->select('ports.port_id', 'ports.ifIndex', 'ipv6_addresses.ipv6_compressed', 'ipv6_addresses.ipv6_prefixlen')
    ->join('ipv6_addresses', 'ports.port_id', '=', 'ipv6_addresses.port_id')
    ->get();

if (isset($portIds)) {
    foreach ($portIds as $fromDb) {
        if (str_starts_with($fromDb['ipv6_address'], 'fe80')) {
            continue;
        }
        $ipv6 = new IPv6($fromDb['ipv6_compressed'] . '/' . $fromDb['ipv6_prefixlen']);
        $inetCidrRouteDest = $ipv6->getNetworkAddress();
        $inetCidrRoutePfxLen = $fromDb['ipv6_prefixlen'];
        $inetCidrRoutePolicy = 'zeroDotZero.' . $fromDb['ifIndex'];
        $inetCidrRouteNextHop = '0000:0000:0000:0000:0000:0000:0000:0000';
        d_echo('ROUTE: Jetstream IPv6 calculated: ' . $inetCidrRouteDest);

        unset($entryClean);
        $entryClean['device_id'] = $device['device_id'];
        $entryClean['inetCidrRouteDestType'] = 'ipv6';
        $entryClean['inetCidrRouteDest'] = $inetCidrRouteDest;
        $entryClean['inetCidrRoutePfxLen'] = $inetCidrRoutePfxLen;
        $entryClean['inetCidrRouteNextHopType'] = 'ipv6';
        $entryClean['inetCidrRouteNextHop'] = $inetCidrRouteNextHop;
        $entryClean['inetCidrRouteNextHopAS'] = '0';
        $entryClean['inetCidrRouteProto'] = '2';
        $entryClean['inetCidrRouteType'] = '3';
        $entryClean['inetCidrRoutePolicy'] = $inetCidrRoutePolicy;
        $entryClean['inetCidrRouteMetric1'] = '1';
        $entryClean['context_name'] = '';
        $entryClean['updated_at'] = $update_timestamp;
        $entryClean['port_id'] = $fromDb['port_id'];
        $entryClean['inetCidrRouteIfIndex'] = $fromDb['ifIndex'];

        $current = $mixed['']['ipv6'][$inetCidrRouteDest][$inetCidrRoutePfxLen][$inetCidrRoutePolicy]['ipv6'][$inetCidrRouteNextHop];
        if (isset($current) && isset($current['db']) && count($current['db']) > 0 && $delete_row[$current['db']['route_id']] != 1) {
            //we already have a row in DB
            $entryClean['route_id'] = $current['db']['route_id'];
            $update_row[] = $entryClean;
        } else {
            $entry['created_at'] = ['NOW()'];
            $create_row[] = $entryClean;
        }
    }
}
