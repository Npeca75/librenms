<?php
/*
 * LibreNMS discovery module for RouterOs inventory items
 *
 *
 * @package    LibreNMS
 * @link       https://www.librenms.org
 *
 * @copyright  2022 Peca Nesovanovic
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */

echo "\nCaching OIDs:";

$entity_array = [];
echo ' RouterOS';

$entIndex = DeviceCache::getPrimary()->entityPhysical()->where('entPhysicalClass', 'chassis')->value('entPhysicalIndex');
$entDescr = DeviceCache::getPrimary()->entityPhysical()->where('entPhysicalClass', 'chassis')->value('entPhysicalDescr');
$entRam = DeviceCache::getPrimary()->mempools()->where('mempool_descr', 'main memory')->value('mempool_total');
$entFlash = DeviceCache::getPrimary()->storage()->where('storage_descr', 'system disk')->value('storage_size');
$entCpu = DeviceCache::getPrimary()->entityPhysical()->where('entPhysicalClass', 'chassis')->value('entPhysicalName');
$rosSfp = SnmpQuery::cache()->hideMib()->walk('MIKROTIK-MIB::mtxrOpticalTable')->table(1);
$rosSystem = SnmpQuery::cache()->hideMib()->walk('MIKROTIK-MIB::mtxrSystem')->table(1);

dbUpdate([
    'entPhysicalName' => $rosSystem[0]['mtxrBoardName'] ?? '',
    'entPhysicalSerialNum' => $rosSystem[0]['mtxrSerialNumber'] ?? '',
    'entPhysicalHardwareRev' => $rosSystem[0]['mtxrDisplayName'] ?? '',
    'entPhysicalFirmwareRev' => $rosSystem[0]['mtxrFirmwareVersion'] ?? '',
    'entPhysicalSoftwareRev' => explode(' ', $entDescr)[1],
], 'entPhysical', 'device_id=? AND entPhysicalClass=?', [$device['device_id'], 'chassis']);

$boardIndex = $entIndex + 1;
$entity_array[] = [
    'entPhysicalIndex'        => $boardIndex,
    'entPhysicalDescr'        => explode(' ', $entDescr)[array_key_last(explode(' ', $entDescr))],
    'entPhysicalClass'        => 'module',
    'entPhysicalName'         => 'Board',
    'entPhysicalContainedIn'  => $entIndex,
    'entPhysicalMfgName'      => 'MikroTik',
    'entPhysicalSerialNum'    => $rosSystem[0]['mtxrLicSoftwareId'] ?? '',

];

$cpuIndex = $boardIndex + 1;
$entity_array[] = [
    'entPhysicalIndex'        => $cpuIndex,
    'entPhysicalDescr'        => $entCpu,
    'entPhysicalClass'        => 'other',
    'entPhysicalName'         => 'CPU',
    'entPhysicalContainedIn'  => $boardIndex,
    'entPhysicalMfgName'      => 'MikroTik',
    'entPhysicalParentRelPos' => 1,
];

$ramIndex = $boardIndex + 2;
$entity_array[] = [
    'entPhysicalIndex'        => $ramIndex,
    'entPhysicalDescr'        => intval($entRam / 1024),
    'entPhysicalClass'        => 'other',
    'entPhysicalName'         => 'RAM',
    'entPhysicalContainedIn'  => $boardIndex,
    'entPhysicalMfgName'      => 'MikroTik',
    'entPhysicalParentRelPos' => 2,
];

$flashIndex = $boardIndex + 3;
$entity_array[] = [
    'entPhysicalIndex'        => $flashIndex,
    'entPhysicalDescr'        => intval($entFlash / 1024),
    'entPhysicalClass'        => 'other',
    'entPhysicalName'         => 'FLASH',
    'entPhysicalContainedIn'  => $boardIndex,
    'entPhysicalMfgName'      => 'MikroTik',
    'entPhysicalParentRelPos' => 3,
];

if (! empty($rosSfp)) {
    foreach ($rosSfp as $ifIndex => $sfpData) {
        $mfgName = $sfpData['mtxrOpticalVendorName'] ?? '';
        $mfgSerial = $sfpData['mtxrOpticalVendorSerial'] ?? '';
        $port = get_port_by_index_cache($device['device_id'], $ifIndex);
        $entity_array[] = [
            'entPhysicalIndex'        => $entIndex + 100 + $ifIndex,
            'entPhysicalDescr'        => 'SFP: ' . strtoupper($mfgName),
            'entPhysicalClass'        => 'sfp-cage',
            'entPhysicalName'         => $port['ifName'],
            'entPhysicalContainedIn'  => $entIndex, //chassis
            'entPhysicalMfgName'      => strtoupper($mfgName),
            'entPhysicalSerialNum'    => strtoupper($mfgSerial),
            'entPhysicalParentRelPos' => $ifIndex,
            'entPhysicalIsFRU'        => 'true',
            'ifIndex'                 => $ifIndex,
        ];
    }
}

foreach ($entity_array as $entPhysicalIndex => $entry) {
    $entPhysicalIndex = $entry['entPhysicalIndex'] ?? 0;
    $entPhysicalDescr = $entry['entPhysicalDescr'] ?? '';
    $entPhysicalClass = $entry['entPhysicalClass'] ?? '';
    $entPhysicalName = $entry['entPhysicalName'] ?? '';
    $entPhysicalModelName = $entry['entPhysicalModelName'] ?? '';
    $entPhysicalSerialNum = $entry['entPhysicalSerialNum'] ?? '';
    $entPhysicalContainedIn = $entry['entPhysicalContainedIn'] ?? '';
    $entPhysicalMfgName = $entry['entPhysicalMfgName'] ?? '';
    $entPhysicalParentRelPos = $entry['entPhysicalParentRelPos'] ?? 0;
    $entPhysicalVendorType = $entry['entPhysicalVendorType'] ?? '';
    $entPhysicalHardwareRev = $entry['entPhysicalHardwareRev'] ?? '';
    $entPhysicalFirmwareRev = $entry['entPhysicalFirmwareRev'] ?? '';
    $entPhysicalSoftwareRev = $entry['entPhysicalSoftwareRev'] ?? '';
    $entPhysicalIsFRU = $entry['entPhysicalIsFRU'] ?? '';
    $entPhysicalAlias = $entry['entPhysicalAlias'] ?? '';
    $entPhysicalAssetID = $entry['entPhysicalAssetID'] ?? '';
    $ifIndex = $entry['ifIndex'] ?? 0;

    discover_entity_physical(
        $valid,
        $device,
        $entPhysicalIndex,
        $entPhysicalDescr,
        $entPhysicalClass,
        $entPhysicalName,
        $entPhysicalModelName,
        $entPhysicalSerialNum,
        $entPhysicalContainedIn,
        $entPhysicalMfgName,
        $entPhysicalParentRelPos,
        $entPhysicalVendorType,
        $entPhysicalHardwareRev,
        $entPhysicalFirmwareRev,
        $entPhysicalSoftwareRev,
        $entPhysicalIsFRU,
        $entPhysicalAlias,
        $entPhysicalAssetID,
        $ifIndex
    );
}//end foreach

echo "\n";
unset(
    $modules_array,
    $entry,
    $entity_array,
    $trans,
    $mapping
);
