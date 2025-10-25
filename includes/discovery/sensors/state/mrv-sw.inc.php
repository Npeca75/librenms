<?php

/*
 * LibreNMS fan status sensor for Mrv-sw OS
 *
 *
 * @package    LibreNMS
 * @link       https://www.librenms.org
 *
 * @copyright  2022 Peca Nesovanovic
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */
$oids = snmp_walk($device, '.1.3.6.1.4.1.629.6.10.84.1.1.9.1.3', '-OsQn', '');
foreach (explode("\n", (string) $oids) as $data) {
    $data = trim($data);
    if ($data) {
        [$oid, $value] = explode(' = ', $data);
        $expand = explode('.', $oid);
        $fan = $expand[max(array_keys($expand))];
        $unit = $expand[max(array_keys($expand)) - 1];
        $index = $unit . $fan;
        $descr = 'Fan #' . $fan;

        $states = [
            ['value' => 0, 'generic' => 3, 'graph' => 0, 'descr' => 'unknown'],
            ['value' => 1, 'generic' => 0, 'graph' => 1, 'descr' => 'good'],
            ['value' => 2, 'generic' => 2, 'graph' => 1, 'descr' => 'bad'],
        ];
        create_state_index($state_name, $states);

        discover_sensor(
            null,
            'state',
            $device,
            $oid,
            $index,
            'mrw-sw-fan-stat',
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
        //Create Sensor To State Index
        create_state_index($device, 'mrw-sw-fan-stat');
    }//end if
}//end foreach
