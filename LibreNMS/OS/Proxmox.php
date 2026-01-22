<?php

/*
 * Proxmox.php
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2025 Peca Nesovanovic
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */

namespace LibreNMS\OS;

use App\Models\Storage;
use App\Models\Vminfo;
use Illuminate\Support\Collection;
use LibreNMS\Enum\PowerState;
use LibreNMS\Enum\Severity;
use LibreNMS\Interfaces\Data\DataStorageInterface;
use LibreNMS\Interfaces\Discovery\StorageDiscovery;
use LibreNMS\Interfaces\Polling\OSPolling;
use LibreNMS\Interfaces\Polling\StoragePolling;
use LibreNMS\OS;
use LibreNMS\OS\Shared\Unix;
use LibreNMS\Util\Oid;

class Proxmox extends Unix implements OSPolling, StorageDiscovery, StoragePolling
{
    public function pollOS(DataStorageInterface $datastore): void
    {
        $oslist = [
            'l26' => 'Linux >= 2.6', 'l24' => 'Linux 2.4',
            'wxp' => 'Windows XP/2003', 'w2k8' => 'Windows Vista/2008', 'win7' => 'Windows 7/2008r2', 'win8' => 'Windows 8/2012', 'win10' => 'Windows 10/2016/2019', 'win11' => 'Windows 11/2022',
            'solaris' => 'Solaris OS',
            'other' => 'Other OS',
        ];

        $output = \SnmpQuery::hideMib()->get('NET-SNMP-EXTEND-MIB::' . 'nsExtendOutputFull.' . Oid::encodeString('proxmox_vminfo')->oid)
            ->values()['nsExtendOutputFull[proxmox_vminfo]'];
        if (! empty($output)) {
            $parsed_json = json_decode(stripslashes((string) $output), true);
            if (json_last_error() !== JSON_ERROR_NONE || empty($parsed_json)) {
                echo 'Proxmox VMs: Invalid SNMP data' . PHP_EOL;
            }
            d_echo($parsed_json);
            $vmlist = [];
            foreach ($parsed_json['data'] as $data) {
                $vmlist[] = $data['id'];
                $vmData['vm_type'] = $data['type'];
                $vmData['vmwVmDisplayName'] = $data['name'];
                $vmData['vmwVmGuestOS'] = $oslist[trim((string) $data['os'])] ?? trim((string) $data['os']); // QEMU translated OS names || LXC raw OS names
                $vmData['vmwVmMemSize'] = $data['ram'] ?? 0;
                $vmData['vmwVmCpus'] = $data['vcpu'] ?? 0;
                $vmData['vmwVmState'] = PowerState::STATES[strtolower((string) $data['status'])] ?? PowerState::UNKNOWN;

                $dbData = Vminfo::firstOrNew([
                    'device_id' => $this->getDeviceId(),
                    'vmwVmVMID' => $data['id'],
                ], [
                    'vm_type' => $vmData['vm_type'],
                    'vmwVmDisplayName' => $vmData['vmwVmDisplayName'],
                    'vmwVmGuestOS' => $vmData['vmwVmGuestOS'],
                    'vmwVmMemSize' => $vmData['vmwVmMemSize'],
                    'vmwVmCpus' => $vmData['vmwVmCpus'],
                    'vmwVmState' => $vmData['vmwVmState'],
                ]);

                foreach ($vmData as $key => $value) {
                    $dbData->{$key} = $value;
                }

                $dbData->save();
            }

            $badIds = Vminfo::where('device_id', $this->getDeviceId())->whereNotIn('vmwVmVMID', $vmlist)->get()->toArray();
            if (! empty($badIds)) {
                foreach ($badIds as $badData) {
                    Vminfo::where('id', $badData['id'])->delete();
                    \App\Models\Eventlog::log('Virtual Machine removed: ' . $badData['vmwVmDisplayName'], $this->getDeviceId(), 'vm', Severity::Warning);
                }
            }
        }
    }

    public function pollStorage(Collection $storages): Collection
    {
        $snmpData = $this->getSnmpData();

        /** @var Storage $storage */
        foreach ($storages as $storage) {
            foreach ($snmpData as $data) {
                if ($storage->storage_descr == $data['Name'] && $storage->storage_type == $data['Type']) {
                    $storage->storage_used = $data['Used'];
                    $storage->storage_free = $data['Available'];
                    $storage->fillUsage($data['Used'], $data['Total']);
                }
            }
        }

        return $storages;
    }

    public function discoverStorage(): Collection
    {
        $storage = new Collection;
        $snmpData = $this->getSnmpData();

        foreach ($snmpData as $index => $data) {
            if (strtolower((string) $data['Status']) == 'active') {
                foreach (['Total', 'Used', 'Available'] as $key) {
                    $data[$key] *= 1024 * 1024;
                }

                $storage->push((new Storage([
                    'type' => 'proxmox',
                    'storage_type' => $data['Type'],
                    'storage_descr' => $data['Name'],
                    'storage_units' => 1,
                    'storage_index' => $index,
                    'storage_used' => $data['Used'],
                    'storage_free' => $data['Available'],
                ]))->fillUsage(total: $data['Total'], free: $data['Available']));
            }
        }

        return $storage;
    }

    private function getSnmpData(): array
    {
        $outData = [];

        $oidDisk = Oid::encodeString('proxmox_disks')->oid;
        $out = \SnmpQuery::hideMib()->get('NET-SNMP-EXTEND-MIB::nsExtendOutputFull.' . $oidDisk)->values()['nsExtendOutputFull[proxmox_disks]'];

        if (! empty($out)) {
            $outData = json_decode(stripslashes((string) $out), true);

            if (json_last_error() !== JSON_ERROR_NONE || empty($outData)) {
                echo 'Proxmox storage: Invalid SNMP data' . PHP_EOL;
            }
        }

        return $outData['data'] ?? [];
    }
}
