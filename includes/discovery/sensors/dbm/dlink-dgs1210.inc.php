<?php
/*
 * LibreNMS discovery module for Dlink-dgs1210 SFP rx/tx power
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
$user_func = 'mw_to_dbm';
$ID = explode('.', $device['sysObjectID'])[10];
if ($pre_cache['dgs1210-ddm']) {
    d_echo('Dlink-dgs1210 ddm Rx/Tx Power');
    foreach ($pre_cache['dgs1210-ddm'] as $ifIndex => $data) {
        //tx Power
        if (! empty($data['ddmTxPower'])) {
            $value = round(10 * log10($data['ddmTxPower'] / $divisor), 2); // mw2dbm
            $high_limit = $data['txPower']['ddmHighAlarm'] / $divisor;
            $high_warn_limit = $data['txPower']['ddmHighWarning'] / $divisor;
            $low_warn_limit = $data['txPower']['ddmLowWarning'] / $divisor;
            $low_limit = $data['txPower']['ddmLowAlarm'] / $divisor;
            $descr = get_port_by_index_cache($device['device_id'], $ifIndex)['ifName'];
            $oid = '.1.3.6.1.4.1.171.10.76.' . $ID . '.1.105.2.1.1.1.5.' . $ifIndex;
            discover_sensor(
                null,
                'dbm',
                $device,
                $oid,
                'SfpTx' . $ifIndex,
                'ddmTxPower',
                'SfpTx-' . $descr,
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
                $user_func,
                'Transceiver TX'
            );
        }
        //rx Power
        if (! empty($data['ddmRxPower'])) {
            $value = round(10 * log10($data['ddmRxPower'] / $divisor), 2); // mw2dbm
            $high_limit = $data['rxPower']['ddmHighAlarm'] / $divisor;
            $high_warn_limit = $data['rxPower']['ddmHighWarning'] / $divisor;
            $low_warn_limit = $data['rxPower']['ddmLowWarning'] / $divisor;
            $low_limit = $data['rxPower']['ddmLowAlarm'] / $divisor;
            $descr = get_port_by_index_cache($device['device_id'], $ifIndex)['ifName'];
            $oid = '.1.3.6.1.4.1.171.10.76.' . $ID . '.1.105.2.1.1.1.6.' . $ifIndex;
            discover_sensor(
                $valid['sensor'],
                'dbm',
                $device,
                $oid,
                'SfpRx' . $ifIndex,
                'ddmRxPower',
                'SfpRx-' . $descr,
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
                $user_func,
                'Transceiver RX'
            );
        }
    }
}
