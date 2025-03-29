<?php
/*
 *
 * LibreNMS storage discovery module for Proxmox OS
 *
 * @package    LibreNMS
 * @link       https://www.librenms.org
 *
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 * @copyright  2023 Peca Nesovanovic
 */

if ($device['os'] == 'proxmox') {
    $output = snmp_get($device, 'nsExtendOutputFull.' . \LibreNMS\Util\Oid::of('proxmox_disks'), '-Oqv', 'NET-SNMP-EXTEND-MIB')->oid;
    if (! empty($output)) {
        $parsed_json = json_decode(stripslashes($output), true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($parsed_json)) {
            echo 'Proxmox storage: Invalid SNMP data' . PHP_EOL;
        }
        d_echo($parsed_json);
        foreach ($parsed_json['data'] as $index => $data) {
            if (strtolower($data['Status']) == 'active') {
                $units = 4096;
                foreach (['Total', 'Used', 'Available'] as $key) {
                    $data[$key] = $data[$key] * 1024;
                }
                discover_storage($valid_storage, $device, ($index + 1000), $data['type'], 'proxmox_disks', $data['Name'], $data['Total'], $units, $data['Used'], $data['Available']);
            }
        }
    }
}
