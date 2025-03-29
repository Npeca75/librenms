<?php
/**
 * hpe-ilo.inc.php
 *
 * IP route discovery file for HPE iLO OS
 *
 *
 * @package    LibreNMS
 * @link       https://www.librenms.org
 *
 * @copyright  2022 Peca Nesovanovic
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */
use LibreNMS\Util\IPv4;

if (! is_array($pre_cache['hpe-ilo']['nicConfig'])) {
    $pre_cache['hpe-ilo']['nicConfig'] = SnmpQuery::hidemib()->walk('CPQSM2-MIB::cpqSm2NicConfigTable')->table(1);
}

$nicConfig = $pre_cache['hpe-ilo']['nicConfig'];
if ($nicConfig) {
    d_echo('ROUTE: hpe-ilo IPv4' . PHP_EOL);
    foreach ($nicConfig as $ifName => $ifData) {
        if (isset($ifData['cpqSm2NicGatewayIpAddress'])) {
            $ifIndex = 1000000 + $ifData['cpqSm2NicLocation'];
            $portId = get_port_by_index_cache($device['device_id'], $ifIndex)['port_id'];
            $inetCidrRouteDest = '0.0.0.0';
            $inetCidrRoutePfxLen = '0';
            $inetCidrRouteNextHop = $ifData['cpqSm2NicGatewayIpAddress'];

            unset($entryClean); // prepare array
            $entryClean['device_id'] = $device['device_id'];
            $entryClean['updated_at'] = $update_timestamp;
            $entryClean['context_name'] = '';
            $entryClean['inetCidrRouteNextHopAS'] = '0';
            $entryClean['port_id'] = $portId;
            $entryClean['inetCidrRouteIfIndex'] = $ifIndex;
            $entryClean['inetCidrRoutePolicy'] = 'zeroDotZero.' . $ifIndex;

            $entryClean['inetCidrRouteType'] = '4'; // remote
            $entryClean['inetCidrRouteMetric1'] = '2';
            $entryClean['inetCidrRouteDest'] = $inetCidrRouteDest;
            $entryClean['inetCidrRouteDestType'] = 'ipv4';
            $entryClean['inetCidrRouteNextHop'] = $inetCidrRouteNextHop;
            $entryClean['inetCidrRouteNextHopType'] = 'ipv4';
            $entryClean['inetCidrRoutePfxLen'] = $inetCidrRoutePfxLen;
            $entryClean['inetCidrRouteProto'] = '3'; // netmgm

            $current = $mixed['']['ipv4'][$inetCidrRouteDest][$inetCidrRoutePfxLen][$entryClean['inetCidrRoutePolicy']]['ipv4'][$inetCidrRouteNextHop];
            if (isset($current) && isset($current['db']) && count($current['db']) > 0 && $delete_row[$current['db']['route_id']] != 1) {
                //we already have a row in DB
                $entryClean['route_id'] = $current['db']['route_id'];
                $update_row[] = $entryClean;
            } else {
                $entry['created_at'] = ['NOW()'];
                $create_row[] = $entryClean;
            }

            $cidr = IPv4::netmask2cidr($ifData['cpqSm2NicIpSubnetMask']);
            $ipv4 = new IPv4($ifData['cpqSm2NicIpAddress'] . '/' . $cidr);
            $inetCidrRouteDest = $ipv4->getNetworkAddress();
            $inetCidrRoutePfxLen = '0';
            $inetCidrRouteNextHop = '0.0.0.0';

            $entryClean['inetCidrRouteType'] = '3'; // local
            $entryClean['inetCidrRouteMetric1'] = '1';
            $entryClean['inetCidrRouteDest'] = $inetCidrRouteDest;
            $entryClean['inetCidrRouteDestType'] = 'ipv4';
            $entryClean['inetCidrRouteNextHop'] = $inetCidrRouteNextHop;
            $entryClean['inetCidrRouteNextHopType'] = 'ipv4';
            $entryClean['inetCidrRoutePfxLen'] = $inetCidrRoutePfxLen;
            $entryClean['inetCidrRouteProto'] = '2'; // local

            $current = $mixed['']['ipv4'][$inetCidrRouteDest][$inetCidrRoutePfxLen][$entryClean['inetCidrRoutePolicy']]['ipv4'][$inetCidrRouteNextHop];
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
