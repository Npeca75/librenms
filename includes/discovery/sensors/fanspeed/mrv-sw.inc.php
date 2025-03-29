<?php
/*
 * LibreNMS fanspeed sensor for Mrv-sw OS
 *
 *
 * @package    LibreNMS
 * @link       https://www.librenms.org
 *
 * @copyright  2022 Peca Nesovanovic
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */
$oids = snmp_walk($device, '.1.3.6.1.4.1.629.6.10.84.1.1.9.1.6', '-OsQn', '');
foreach (explode("\n", $oids) as $data) {
    $data = trim($data);
    if ($data) {
        [$oid, $value] = explode(' = ', $data);
        $expand = explode('.', $oid);
        $fan = $expand[max(array_keys($expand))];
        $unit = $expand[max(array_keys($expand)) - 1];
        $index = $unit . $fan;
        $descr = 'Fan #' . $fan;
        discover_sensor(
            null,
            'fanspeed',
            $device,
            $oid,
            $index,
            'mrv-sw-fan-rpm',
            $descr,
            1,
            1,
            null,
            null,
            null,
            null,
            $value,
            'snmp',
            null,
            null,
            null,
            'Unit ' . $unit
        );
    }
}
