<?php
/*
 * LibreNMS discovery module for Dlink dgs1210 SFP inventory items
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
echo ' Dlink-dgs12xx';

$oid20 = SnmpQuery::hideMib()->walk('DGS-1210-20ME-AX::sfpVendorInfoEntry')->table(1);
$oids = SnmpQuery::hideMib()->walk('DGS-1210-28ME-AX::sfpVendorInfoEntry')->table(1, $oid20);

foreach ($oids as $index => $data) {
    $entIndex = DeviceCache::getPrimary()->entityPhysical()
    ->where('entPhysicalAlias', 'Slot0/' . $index)->value('entPhysicalIndex');

    $entity_array[] = [
        'entPhysicalIndex'       => $entIndex + 100,
        'entPhysicalModelName'   => $data['sfpConnectorType'],
        'entPhysicalClass'       => 'module',
        'entPhysicalName'        => hex2ascii($data['sfpVendorName']),
        'entPhysicalDescr'       => hex2ascii($data['sfpVendorPn']),
        'entPhysicalSerialNum'   => $data['sfpVendorSn'],
        'entPhysicalContainedIn' => $entIndex,
        'entPhysicalMfgName'     => hex2ascii($data['sfpVendorName']),
        'entPhysicalHardwareRev' => $data['sfpVendorRev'],
        'entPhysicalFirmwareRev' => $data['sfpDateCode'],
        'entPhysicalIsFRU'       => 'true',
    ];
}

foreach ($entity_array as $entPhysicalIndex => $entry) {
    $entPhysicalIndex = $entry['entPhysicalIndex'] ?? '';
    $entPhysicalDescr = $entry['entPhysicalDescr'] ?? '';
    $entPhysicalClass = $entry['entPhysicalClass'] ?? '';
    $entPhysicalName = $entry['entPhysicalName'] ?? '';
    $entPhysicalModelName = $entry['entPhysicalModelName'] ?? '';
    $entPhysicalSerialNum = $entry['entPhysicalSerialNum'] ?? '';
    $entPhysicalContainedIn = $entry['entPhysicalContainedIn'] ?? '';
    $entPhysicalMfgName = $entry['entPhysicalMfgName'] ?? '';
    $entPhysicalParentRelPos = $entry['entPhysicalParentRelPos'] ?? '';
    $entPhysicalVendorType = $entry['entPhysicalVendorType'] ?? '';
    $entPhysicalHardwareRev = $entry['entPhysicalHardwareRev'] ?? '';
    $entPhysicalFirmwareRev = $entry['entPhysicalFirmwareRev'] ?? '';
    $entPhysicalSoftwareRev = $entry['entPhysicalSoftwareRev'] ?? '';
    $entPhysicalIsFRU = $entry['entPhysicalIsFRU'] ?? '';
    $entPhysicalAlias = $entry['entPhysicalAlias'] ?? '';
    $entPhysicalAssetID = $entry['entPhysicalAssetID'] ?? '';
    $ifIndex = $entry['ifIndex'] ?? '';

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
