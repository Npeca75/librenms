<?php
/*
 * LibreNMS discovery module for Dlink-dgs1210 SFP Bias
 *
 *
 * @package    LibreNMS
 * @link       https://www.librenms.org
 *
 * @copyright  2022 Peca Nesovanovic
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */
$divisor = 1000;
$multiplier = 1;
$ID = explode('.', $device['sysObjectID'])[10];
if ($pre_cache['dgs1210-ddm']) {
    d_echo('Dlink-dgs1210 ddmBiasCurrent');
    foreach ($pre_cache['dgs1210-ddm'] as $ifIndex => $data) {
        if (! empty($data['ddmBiasCurrent'])) {
            $value = $data['ddmBiasCurrent'] / $divisor;
            $high_limit = $data['bias']['ddmHighAlarm'] / $divisor;
            $high_warn_limit = $data['bias']['ddmHighWarning'] / $divisor;
            $low_warn_limit = $data['bias']['ddmLowWarning'] / $divisor;
            $low_limit = $data['bias']['ddmLowAlarm'] / $divisor;
            $descr = get_port_by_index_cache($device['device_id'], $ifIndex)['ifName'];
            $oid = '.1.3.6.1.4.1.171.10.76.' . $ID . '.1.105.2.1.1.1.4.' . $ifIndex;
            discover_sensor(
                null,
                'current',
                $device,
                $oid,
                'SfpBias' . $ifIndex,
                'ddmBiasCurrent',
                'SfpBias-' . $descr,
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
