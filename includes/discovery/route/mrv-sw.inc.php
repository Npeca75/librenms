<?php
/**
 * mrv-sw.inc.php
 *
 * IPv6 route discovery file for mrv-sw OS
 *
 *
 * @package    LibreNMS
 * @link       https://www.librenms.org
 *
 * @copyright  2022 Peca Nesovanovic
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */
d_echo('MRV: IPv6 route');

use LibreNMS\Util\IPv6;

$oidt = SnmpQuery::hideMib()->walk('IPV6-MIB::ipv6IfTable')->table(2);
$oidr = SnmpQuery::hideMib()->walk('IPV6-MIB::ipv6RouteTable')->table(3);

if (! empty($oidr) && ! empty($oidt)) {
    foreach ($oidt as $fakeIndex => $portData) {
        if (isset($portData['ipv6IfPhysicalAddress'])) {
            $mac = implode(array_map('zeropad', explode(':', $portData['ipv6IfPhysicalAddress'])));
            $port_id = find_port_id(null, null, $device['device_id'], $mac);
            if ($port_id) {
                $portId[$fakeIndex] = $port_id;
            }
        } else {
            continue; //probably loopback interface
        }
    }

    foreach ($oidr as $dst => $tmpData) {
        foreach ($tmpData as $pfLen => $dstData) {
            $dstData = array_shift($dstData);
            if (isset($portId[$dstData['ipv6RouteIfIndex']])) {
                $port_id = $portId[$dstData['ipv6RouteIfIndex']];
                $ifIndex = get_port_by_id($port_id)['ifIndex'];

                unset($entryClean); // prepare array
                $entryClean['device_id'] = $device['device_id'];
                $entryClean['updated_at'] = $update_timestamp;
                $entryClean['context_name'] = '';
                $entryClean['inetCidrRouteNextHopAS'] = '0';
                $entryClean['port_id'] = $port_id;
                $entryClean['inetCidrRouteIfIndex'] = $ifIndex;
                $entryClean['inetCidrRoutePolicy'] = 'zeroDotZero.' . $ifIndex;
                $entryClean['inetCidrRouteType'] = $dstData['ipv6RouteType'];
                $entryClean['inetCidrRouteMetric1'] = $dstData['ipv6RouteMetric'];
                $entryClean['inetCidrRouteDest'] = $dst;
                $entryClean['inetCidrRouteDestType'] = 'ipv6';
                $entryClean['inetCidrRouteNextHop'] = $dstData['ipv6RouteNextHop'];
                $entryClean['inetCidrRouteNextHopType'] = 'ipv6';
                $entryClean['inetCidrRoutePfxLen'] = $pfLen;
                $entryClean['inetCidrRouteProto'] = $dstData['ipv6RouteProtocol'];

                $current = $mixed['']['ipv6'][$entryClean['$inetCidrRouteDest']][$entryClean['inetCidrRoutePfxLen']][$entryClean['inetCidrRoutePolicy']]['ipv6'][$entryClean['inetCidrRouteNextHop']];
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
}
