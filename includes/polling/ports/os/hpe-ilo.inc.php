<?php

/**
 * hpe-ilo.inc.php
 *
 * LibreNMS poller module for HPE iLO Ports
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
 * @link       https://www.librenms.org
 *
 * @copyrigh   2021 Peca Nesovanovic
 *
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */
$oids = SnmpQuery::hidemib()->walk('CPQSM2-MIB::cpqSm2NicConfigTable')->table(1);
$oids = SnmpQuery::hidemib()->walk('CPQSM2-MIB::cpqSm2NicStatsTable')->table(1, $oids);

$divider = (intval($device['version'] >= 3)) ? 1000000000 : 1;

foreach ($oids as $ifName => $ifData) {
    if ($ifData['cpqSm2NicEnabledStatus'] == 2) {
        $ifIndex = 1000000 + $ifData['cpqSm2NicLocation'];
        $port_stats[$ifIndex]['ifName'] = $ifName;
        $port_stats[$ifIndex]['ifDescr'] = $ifData['cpqSm2NicModel'];
        $port_stats[$ifIndex]['ifType'] = ($ifData['cpqSm2NicType'] == 2) ? 'ethernetCsmacd' : 'other';
        $port_stats[$ifIndex]['ifOperStatus'] = ($ifData['cpqSm2NicCondition'] == 2) ? 'up' : 'down';
        $port_stats[$ifIndex]['ifAdminStatus'] = ($ifData['cpqSm2NicCondition'] == 2) ? 'up' : 'down';
        $port_stats[$ifIndex]['ifSpeed'] = 1000000 * $ifData['cpqSm2NicSpeed'];
        $port_stats[$ifIndex]['ifPhysAddress'] = str_replace(' ', '', strtolower((string) $ifData['cpqSm2NicMacAddress']));
        $port_stats[$ifIndex]['ifDuplex'] = ($ifData['cpqSm2NicDuplexState'] == 3) ? 'fullDuplex' : 'halfDuplex';
        $port_stats[$ifIndex]['ifMtu'] = $ifData['cpqSm2NicMtu'];
        $port_stats[$ifIndex]['ifInOctets'] = $ifData['cpqSm2NicRecvBytes'] ? ($ifData['cpqSm2NicRecvBytes'] / $divider) : null;
        $port_stats[$ifIndex]['ifOutOctets'] = $ifData['cpqSm2NicXmitBytes'] ? ($ifData['cpqSm2NicXmitBytes'] / $divider) : null;
        $port_stats[$ifIndex]['ifInUcastPkts'] = $ifData['cpqSm2NicRecvUnicastPackets'] ? ($ifData['cpqSm2NicRecvUnicastPackets'] / $divider) : null;
        $port_stats[$ifIndex]['ifOutUcastPkts'] = $ifData['cpqSm2NicXmitUnicastPackets'] ? ($ifData['cpqSm2NicXmitUnicastPackets'] / $divider) : null;
    }
}
