<?php
/**
 * mes23xx.inc.php
 *
 * IPv6 route discovery file for eltex-mes23xx OS
 *
 *
 * @package    LibreNMS
 * @link       https://www.librenms.org
 *
 * @copyright  2022 Peca Nesovanovic
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */

use App\Models\Ipv6Address;
use LibreNMS\Util\IP;

$oids = SnmpQuery::hidemib()->walk('RADLAN-IPv6::rlinetCidrRouteTable')->table(5);

if ($oids) {
    d_echo($oids);
    d_echo('ROUTE: Eltex-MES23xx RADLAN-IPv6::rlinetCidrRouteTable');
    $arr6 = $oids['ipv6'];

    foreach ($arr6 as $dst => $data) {
        $pfxLen = trim(key($data));
        $data = array_shift($data); //drop [pfxLen]
        $policy = trim(key($data));
        $data = array_shift($data); //drop [policy]
        $data = array_shift($data); //drop [ipv6]

        $ipv6dst = normalize_snmp_ip_address(str_replace(' ', ':', trim($dst)));
        $ipv6hop = normalize_snmp_ip_address(str_replace(' ', ':', trim(array_key_first($data['rlinetCidrRouteInfo']))));

        unset($entryClean); // prepare array
        unset($dbData);
        $fromDb = Ipv6Address::join('ports', 'ipv6_addresses.port_id', '=', 'ports.port_id')
            ->where('ports.device_id', $device['device_id'])
            ->where('ipv6_addresses.ipv6_address', $ipv6hop)
            ->get()->toArray();

        if ($fromDb) {
            // ipv6hop is local address
            $dbData = array_shift($fromDb); //drop [0]

            $entryClean['inetCidrRouteType'] = '3'; // local
            $entryClean['inetCidrRouteMetric1'] = '1';
            $entryClean['inetCidrRouteNextHop'] = '::';
            $entryClean['inetCidrRoutePfxLen'] = $pfxLen;
            $entryClean['inetCidrRouteProto'] = '2'; // local
            d_echo('ROUTE: Eltex-MES23xx IPv6 from MIB' . $ipv6hop);
        } else {
            // ipv6hop is remote
            $fromDb = Ipv6Address::join('ports', 'ipv6_addresses.port_id', '=', 'ports.port_id')
                ->where('ports.device_id', $device['device_id'])
                ->get()->toArray();

            foreach ($fromDb as $dbData) {
                if (IP::parse($ipv6hop)->inNetwork($dbData['ipv6_address'] . '/' . $dbData['ipv6_prefixlen'])) {
                    $entryClean['inetCidrRouteType'] = '4'; // remote
                    $entryClean['inetCidrRouteMetric1'] = '2';
                    $entryClean['inetCidrRouteNextHop'] = $ipv6hop;
                    $entryClean['inetCidrRoutePfxLen'] = '128';
                    $entryClean['inetCidrRouteProto'] = '3'; // netmgmn
                    d_echo('ROUTE: Eltex-MES23xx IPv6 from DB: ' . $ipv6hop);
                    break;
                }
            }
        }

        if ($entryClean['inetCidrRouteType']) {
            // we have a match
            $entryClean['updated_at'] = $update_timestamp;
            $entryClean['device_id'] = $device['device_id'];
            $entryClean['context_name'] = '';
            $entryClean['inetCidrRouteNextHopAS'] = '0';
            $entryClean['inetCidrRouteDestType'] = 'ipv6';
            $entryClean['inetCidrRouteNextHopType'] = 'ipv6';
            $entryClean['inetCidrRouteDest'] = $ipv6dst;
            $entryClean['port_id'] = $dbData['port_id'];
            $entryClean['inetCidrRouteIfIndex'] = $dbData['ifIndex'];
            $entryClean['inetCidrRoutePolicy'] = 'zeroDotZero.' . $dbData['ifIndex'];

            $current = $mixed['']['ipv6'][$entryClean['inetCidrRouteDest']][$entryClean['inetCidrRoutePfxLen']][$entryClean['inetCidrRoutePolicy']]['ipv6'][$entryClean['inetCidrRouteNextHop']];
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
