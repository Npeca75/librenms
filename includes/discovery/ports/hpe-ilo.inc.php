<?php
/**
 * hpe-ilo.inc.php
 *
 * LibreNMS discovery module for HPE iLO Ports
 *
 *
 * @package    LibreNMS
 * @link       https://www.librenms.org
 *
 * @copyrigh   2022 Peca Nesovanovic
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */

$oids = SnmpQuery::cache()->hidemib()->walk('CPQSM2-MIB::cpqSm2NicConfigTable')->table(1);
if (! empty($oids)) {
    d_echo('PORTS: hpe-ilo');
    foreach ($oids as $ifName => $ifData) {
        if ($ifData['cpqSm2NicEnabledStatus'] == 2) {
            $ifIndex = 1000000 + $ifData['cpqSm2NicLocation'];
            $port_stats[$ifIndex]['ifName'] = $ifName;
            $port_stats[$ifIndex]['ifDescr'] = $ifData['cpqSm2NicModel'];
            $port_stats[$ifIndex]['ifType'] = ($ifData['cpqSm2NicType'] == 2) ? 'ethernetCsmacd' : 'other';
            $port_stats[$ifIndex]['ifOperStatus'] = ($ifData['cpqSm2NicCondition'] == 2) ? 'up' : 'down';
            $port_stats[$ifIndex]['ifSpeed'] = 1000 * 1000 * $ifData['cpqSm2NicSpeed'];
            $port_stats[$ifIndex]['ifPhysAddress'] = str_replace(' ', '', strtolower($ifData['cpqSm2NicMacAddress']));
        }
    }
}
