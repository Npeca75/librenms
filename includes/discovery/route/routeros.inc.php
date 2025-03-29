<?php

/*
 * LibreNMS discovery module for RouterOS IPv6 Routes introduced in ROSv7
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @package    LibreNMS
 * @link       https://www.librenms.org
 *
 * @copyright  2025 Peca Nesovanovic
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */

use LibreNMS\Util\IPv6;
use LibreNMS\Exceptions\InvalidIpException;

$oids = SnmpQuery::hidemib()->walk('IPV6-MIB::ipv6RouteTable')->table(1);

foreach ($oids as $dst => $data) {

    if (empty($data['ipv6RouteIfIndex'])) {
        continue;
    }

    try {
        //route destination
        $ipv6dst = new IPv6($dst);
        $inetCidrRouteDest = $ipv6dst->uncompressed();

        $inetCidrRoutePfxLen = array_key_first($data['ipv6RouteIfIndex']);
        $ipv6RouteIfIndex = array_key_first($data['ipv6RouteIfIndex'][$inetCidrRoutePfxLen]);

        //next hop
        $ipv6hop = new IPv6($data['ipv6RouteNextHop'][$inetCidrRoutePfxLen][$ipv6RouteIfIndex]);
        $inetCidrRouteNextHop = $ipv6hop->uncompressed();

        //portId from ifIndex
        $ifIndex = $data['ipv6RouteIfIndex'][$inetCidrRoutePfxLen][$ipv6RouteIfIndex];
        $portId = \App\Facades\PortCache::getIdFromIfIndex($ifIndex, $device['device_id']);

        //route policy
        $inetCidrRoutePolicy = $data['ipv6RoutePolicy'][$inetCidrRoutePfxLen][$ipv6RouteIfIndex];

        //populate array with data
        unset($entryClean);
        $entryClean['updated_at'] = $update_timestamp;
        $entryClean['device_id'] = $device['device_id'];
        $entryClean['port_id'] = $portId;
        $entryClean['context_name'] = '';
        $entryClean['inetCidrRouteIfIndex'] = $ifIndex;
        $entryClean['inetCidrRouteType'] = $data['ipv6RouteType'][$inetCidrRoutePfxLen][$ipv6RouteIfIndex];
        $entryClean['inetCidrRouteProto'] = $data['ipv6RouteProtocol'][$inetCidrRoutePfxLen][$ipv6RouteIfIndex];
        $entryClean['inetCidrRouteNextHopAS'] = '0';
        $entryClean['inetCidrRouteMetric1'] = $data['ipv6RouteMetric'][$inetCidrRoutePfxLen][$ipv6RouteIfIndex];
        $entryClean['inetCidrRouteDestType'] = 'ipv6';
        $entryClean['inetCidrRouteDest'] = $inetCidrRouteDest;
        $entryClean['inetCidrRouteNextHopType'] = 'ipv6';
        $entryClean['inetCidrRouteNextHop'] = $inetCidrRouteNextHop;
        $entryClean['inetCidrRouteNextHopType'] = 'ipv6';
        $entryClean['inetCidrRoutePolicy'] = $inetCidrRoutePolicy;
        $entryClean['inetCidrRoutePfxLen'] = $inetCidrRoutePfxLen;

        $current = $mixed['']['ipv6'][$inetCidrRouteDest][$inetCidrRoutePfxLen][$inetCidrRoutePolicy]['ipv6'][$inetCidrRouteNextHop];
        if (isset($current) && isset($current['db']) && count($current['db']) > 0 && $delete_row[$current['db']['route_id']] != 1) {
            //we already have a row in DB
            $entryClean['route_id'] = $current['db']['route_id'];
            $update_row[] = $entryClean;
        } else {
            $entry['created_at'] = ['NOW()'];
            $create_row[] = $entryClean;
        }

    } catch (InvalidIpException $e) {
        \Illuminate\Support\Facades\Log::debug('Invalid IPV6: ' . $dst);
    }
}
