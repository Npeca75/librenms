<?php
/**
 * hpe-ilo.inc.php
 *
 * IPv4 address discovery file for HPE iLO OS
 *
 *
 * @package    LibreNMS
 * @link       https://www.librenms.org
 *
 * @copyright  2022 Peca Nesovanovic
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */
$oids = SnmpQuery::cache()->hidemib()->walk('CPQSM2-MIB::cpqSm2NicConfigTable')->table(1);
print_r($$oids);

if (! empty($oids)) {
    d_echo('IPv4: hpe-ilo Addressess' . PHP_EOL);
    foreach ($oids as $ifName => $ifData) {
        if (isset($ifData['cpqSm2NicIpAddress'])) {
            $ifIndex = 1000000 + $ifData['cpqSm2NicLocation'];
            discover_process_ipv4($valid_v4, $device, $ifIndex, $ifData['cpqSm2NicIpAddress'], $ifData['cpqSm2NicIpSubnetMask'], $context_name);
        }
    }
}