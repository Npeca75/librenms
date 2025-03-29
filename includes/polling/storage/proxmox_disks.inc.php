<?php
/*
 *
 * LibreNMS storage poller module for Proxmox_disks
 *
 * @package    LibreNMS
 * @link       https://www.librenms.org
 *
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 * @copyright  2023 Peca Nesovanovic
 */

if (empty($storage_cache['proxmox_disks'])) {
    $output = snmp_get($device, 'nsExtendOutputFull.' . \LibreNMS\Util\Oid::encodeString('proxmox_disks')->oid, '-Oqv', 'NET-SNMP-EXTEND-MIB');
    if (! empty($output)) {
        $parsed_json = json_decode(stripslashes($output), true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($parsed_json)) {
            echo 'Proxmox storage: Invalid SNMP data' . PHP_EOL;
        }
        $storage_cache['proxmox_disks'] = $parsed_json;
    }
}

$index = $storage['storage_index'] - 1000;
$storage['units'] = 4096;
$storage['free'] = $storage_cache['proxmox_disks']['data'][$index]['Available'] * 1024;
$storage['size'] = $storage_cache['proxmox_disks']['data'][$index]['Total'] * 1024;
$storage['used'] = $storage_cache['proxmox_disks']['data'][$index]['Used'] * 1024;
