<?php
/**
 * hpe-ilo.inc.php
 *
 * LibreNMS poller module for HPE iLO Ports
 *
 *
 * @package    LibreNMS
 * @link       https://www.librenms.org
 *
 * @copyrigh   2021 Peca Nesovanovic
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */
$ctmib = SnmpQuery::hidemib()->walk('CPQSM2-MIB::cpqSm2NicConfigTable')->table(1);
$sm2mib = SnmpQuery::hidemib()->walk('CPQSM2-MIB::cpqSm2NicStatsTable')->table(1, $ctmib);
if (! empty($sm2mib)) {
    d_echo('PORTS: hpe-ilo');
    foreach ($sm2mib as $ifName => $ifData) {
        if ($ifData['cpqSm2NicEnabledStatus'] == 2) {
            $ifIndex = 1000000 + $ifData['cpqSm2NicLocation'];
            $port_stats[$ifIndex]['ifName'] = $ifName;
            $port_stats[$ifIndex]['ifDescr'] = $ifData['cpqSm2NicModel'];
            $port_stats[$ifIndex]['ifType'] = ($ifData['cpqSm2NicType'] == 2) ? 'ethernetCsmacd' : 'other';
            $port_stats[$ifIndex]['ifOperStatus'] = ($ifData['cpqSm2NicCondition'] == 2) ? 'up' : 'down';
            $port_stats[$ifIndex]['ifHighSpeed'] = $ifData['cpqSm2NicSpeed'];
            $port_stats[$ifIndex]['ifDuplex'] = ($ifData['cpqSm2NicDuplexState'] == 3 ? 'fullDuplex' : 'halfDuplex');
            $port_stats[$ifIndex]['ifMtu'] = $ifData['cpqSm2NicMtu'];
            $port_stats[$ifIndex]['ifPhysAddress'] = str_replace(' ', '', strtolower($ifData['cpqSm2NicMacAddress']));
        }
    }
}
