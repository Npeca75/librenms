<?php
/*
 * Proxmox.php
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2023 Peca Nesovanovic
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */

namespace LibreNMS\OS;

use App\Models\Storage;
use App\Models\Vminfo;
use Illuminate\Support\Collection;
use LibreNMS\Enum\PowerState;
use LibreNMS\Enum\Severity;
use LibreNMS\Interfaces\Data\DataStorageInterface;
use LibreNMS\Interfaces\Polling\OSPolling;
use LibreNMS\OS;
use LibreNMS\OS\Shared\Unix;

class Proxmox extends Unix implements OSPolling
{
    public function pollOS(DataStorageInterface $datastore): void
    {
        $oslist = [
            'l26' => 'Linux >= 2.6', 'l24' => 'Linux 2.4',
            'wxp' => 'Windows XP/2003', 'w2k8' => 'Windows Vista/2008', 'win7' => 'Windows 7/2008r2', 'win8' => 'Windows 8/2012', 'win10' => 'Windows 10/2016/2019', 'win11' => 'Windows 11/2022',
            'solaris' => 'Solaris OS',
            'other' => 'Other OS',
        ];

        $output = \SnmpQuery::hideMib()->get(['NET-SNMP-EXTEND-MIB::' . 'nsExtendOutputFull.' . \LibreNMS\Util\Oid::encodeString('proxmox_vminfo')->oid])->values()['nsExtendOutputFull[proxmox_vminfo]'];
        if (! empty($output)) {
            $parsed_json = json_decode(stripslashes($output), true);
            if (json_last_error() !== JSON_ERROR_NONE || empty($parsed_json)) {
                echo 'Proxmox VMs: Invalid SNMP data' . PHP_EOL;
            }
            d_echo($parsed_json);
            $vmlist = [];
            foreach ($parsed_json['data'] as $index => $data) {
                $vmlist[] = $data['id'];
                $vmData['vm_type'] = $data['type'];
                $vmData['vmwVmDisplayName'] = $data['name'];
                $vmData['vmwVmGuestOS'] = $oslist[trim($data['os'])] ?? trim($data['os']); // QEMU translated OS names || LXC raw OS names
                $vmData['vmwVmMemSize'] = $data['ram'] ?? 0;
                $vmData['vmwVmCpus'] = $data['vcpu'] ?? 0;
                $vmData['vmwVmState'] = PowerState::STATES[strtolower($data['status'])] ?? PowerState::UNKNOWN;

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

/*
    public function discoverStorage(): Collection
    {
        $storage = new Collection;
#        $oid = Oid::of('NET-SNMP-EXTEND-MIB::nsExtendOutputFull.proxmox_disks')->toNumeric();
#d_echo($oid);
#        $output = snmp_get($this->getDeviceArray(), $oid, '-Oqv', '');
        $output = snmp_get($this->getDeviceArray(), 'nsExtendOutputFull.' . \LibreNMS\Util\Oid::encodeString('proxmox_disks')->oid, '-Oqv', 'NET-SNMP-EXTEND-MIB');

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
                    $storage->push((new Storage([
                        'type' => 'proxmox',
                        'storage_type' => 'disk',
                        'storage_descr' => 'Proxmox Storage',
                        'storage_units' => 1024,
                        'storage_index' => 0,
                        'storage_free_oid' => '.1.3.6.1.4.1.5624.1.2.49.1.3.1.1.5.3.3.0',
                    ]))->fillUsage(total: $total, free: $free));
        }

                    discover_storage($valid_storage, $device, ($index + 1000), $data['type'], 'proxmox_disks', $data['Name'], $data['Total'], $units, $data['Used'], $data['Available']);
                }
            }
        }
    }

*/

}
