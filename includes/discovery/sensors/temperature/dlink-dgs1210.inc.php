<?php
/*
 * LibreNMS discovery module for Dlink-dgs1210 SFP Temperature
 *
 *
 * @package    LibreNMS
 * @link       https://www.librenms.org
 *
 * @copyright  2022 Peca Nesovanovic
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */
$divisor = 1;
$multiplier = 1;
$ID = explode('.', $device['sysObjectID'])[10];
if ($pre_cache['dgs1210-ddm']) {
    d_echo('Dlink-dgs1210 ddmTemperature');
    foreach ($pre_cache['dgs1210-ddm'] as $ifIndex => $data) {
        if (! empty($data['ddmTemperature'])) {
            $value = $data['ddmTemperature'] / $divisor;
            $high_limit = $data['temperature']['ddmHighAlarm'] / $divisor;
            $high_warn_limit = $data['temperature']['ddmHighWarning'] / $divisor;
            $low_warn_limit = $data['temperature']['ddmLowWarning'] / $divisor;
            $low_limit = $data['temperature']['ddmLowAlarm'] / $divisor;
            $descr = get_port_by_index_cache($device['device_id'], $ifIndex)['ifName'];
            $oid = '.1.3.6.1.4.1.171.10.76.' . $ID . '.1.105.2.1.1.1.2.' . $ifIndex;
            discover_sensor(
                null,
                'temperature',
                $device,
                $oid,
                'SfpTemp' . $ifIndex,
                'ddmTemperature',
                'SfpTemp-' . $descr,
                $divisor,
                $multiplier,
                $low_limit,
                $low_warn_limit,
                $high_warn_limit,
                $high_limit,
                $value,
                'snmp',
                null,
                null,
                null,
                'Transceiver'
            );
        }
    }
}
