<?php

namespace LibreNMS\Modules;

use App\Facades\PortCache;
use App\Models\Device;
use App\Models\PortVlan;
use App\Models\Vlan;
use App\Observers\ModuleModelObserver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use LibreNMS\DB\SyncsModels;
use LibreNMS\Interfaces\Data\DataStorageInterface;
use LibreNMS\Interfaces\Discovery\VlanDiscovery;
use LibreNMS\Interfaces\Module;
use LibreNMS\OS;
use LibreNMS\Polling\ModuleStatus;
use SnmpQuery;

class Vlans implements Module
{
    use SyncsModels;

    /**
     * @inheritDoc
     */
    public function dependencies(): array
    {
        return ['ports'];
    }

    /**
     * @inheritDoc
     */
    public function shouldDiscover(OS $os, ModuleStatus $status): bool
    {
        return $status->isEnabledAndDeviceUp($os->getDevice());
    }

    /**
     * @inheritDoc
     */
    public function shouldPoll(OS $os, ModuleStatus $status): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function discover(OS $os): void
    {
        //vlans db to array, avoid excessive database query
        $vlansDb = $os->getDevice()->vlans()->get()->keyBy('vlan_vlan')->toArray();

        $vlans = new Collection;
        $ports = new Collection;

        if ($os instanceof VlanDiscovery) {
            $vlanArray = $os->discoverVlans();
        }

        if (empty($vlanArray)) {
            $vlanArray = $this->discoverVlans8021($os->getDevice());
        }

        if (empty($vlanArray)) {
            $vlanArray = $this->discoverVlans($os->getDevice());
        }

        foreach ($vlanArray['vlans'] ?? [] as $key => $data) {
            $data['vlan_name'] = (empty($data['vlan_name'])) ? 'VLAN ' . $data['vlan_vlan'] : $data['vlan_name'];
            $vlans->push(new Vlan($data));
        }

        Log::info(PHP_EOL . 'Basic Vlan data:');
        ModuleModelObserver::observe(\App\Models\Vlan::class);
        $this->syncModels($os->getDevice(), 'vlans', $vlans);

        foreach ($vlanArray['ports'] ?? [] as $key => $data) {
            $data['port_id'] = PortCache::getIdFromIfIndex($data['baseport'], $os->getDeviceId()) ?? 0;
            $data['priority'] = $data['priority'] ?? 0;
            $data['state'] = $data['state'] ?? 'unknown';
            $data['cost'] = $data['cost'] ?? 0;
            if (! empty($data['port_id']) && ! empty($data['vlan'])) {
                $ports->push(new PortVlan($data));
            } else {
//                dump($data);
            }
        }

        Log::info(PHP_EOL . 'Ports Vlan data:');
        ModuleModelObserver::observe(\App\Models\PortVlan::class);
        $this->syncModels($os->getDevice(), 'portsVlan', $ports);
    }

    /**
     * @inheritDoc
     */
    public function poll(OS $os, DataStorageInterface $datastore): void
    {
        $this->discover($os);
    }

    /**
     * @inheritDoc
     */
    public function dataExists(Device $device): bool
    {
        return $device->vlans()->exists();
    }

    /**
     * @inheritDoc
     */
    public function cleanup(Device $device): int
    {
        $deleted = $device->vlans()->delete();
//        $deleted += $device->portsVlans()->delete();

        return $deleted;
    }

    /**
     * @inheritDoc
     */
    public function dump(Device $device, string $type): ?array
    {
        return [
            'vlans' => $device->vlans()->orderBy('vlan_vlan')
                ->get()->map->makeHidden(['device_id', 'vlan_id']),
            'ports_vlans' => $device->portsVlan()
                ->orderBy('vlan')->orderBy('baseport')
                ->get()->map->makeHidden(['port_vlan_id', 'created_at', 'updated_at', 'device_id', 'port_id']),
        ];
    }

    private function discoverVlans(Device $device): array
    {
        $vlanArray = [];

        $vlanVersion = SnmpQuery::get('Q-BRIDGE-MIB::dot1qVlanVersionNumber.0')->value();

        if ($vlanVersion < 1 || $vlanVersion > 2) {
            return $vlanArray;
        }

        $dot1dBasePortIfIndex = SnmpQuery::hideMib()->walk('BRIDGE-MIB::dot1dBasePortIfIndex')->table();
        $dot1dBasePortIfIndex = $dot1dBasePortIfIndex['dot1dBasePortIfIndex'] ?? [];

        // fetch vlan data
        $oids = SnmpQuery::hideMib()->walk('Q-BRIDGE-MIB::dot1qVlanCurrentUntaggedPorts')->table(2);
        $oids = SnmpQuery::hideMib()->walk('Q-BRIDGE-MIB::dot1qVlanCurrentEgressPorts')->table(2, $oids);
        if (empty($oids)) {
            // fall back to static
            $oids = SnmpQuery::hideMib()->walk('Q-BRIDGE-MIB::dot1qVlanStaticUntaggedPorts')->table(1, $oids);
            $oids = SnmpQuery::hideMib()->walk('Q-BRIDGE-MIB::dot1qVlanStaticEgressPorts')->table(1, $oids);
        } else {
            // collapse timefilter from dot1qVlanCurrentTable results to only the newest
            $oids = array_reduce($oids, function ($result, $time_data) {
                foreach ($time_data as $vlan_id => $vlan_data) {
                    $result[$vlan_id] = isset($result[$vlan_id]) ? array_merge($result[$vlan_id], $vlan_data) : $vlan_data;
                }

                return $result;
            }, []);
        }

        $oids = SnmpQuery::hideMib()->walk('Q-BRIDGE-MIB::dot1qVlanStaticName')->table(1, $oids);

        foreach ($oids as $vlan_id => $vlan) {
            $vlanArray['vlans'][] = [
                'vlan_vlan' => $vlan_id,
                'vlan_domain' => 1,
                'vlan_name' => $vlan['dot1qVlanStaticName'] ?? '',
            ];

            //portmap for untagged ports
            $untagged_ids = q_bridge_bits2indices($vlan['dot1qVlanCurrentUntaggedPorts'] ?? $vlan['dot1qVlanStaticUntaggedPorts'] ?? '');
            //portmap for members ports (might be tagged)
            $egress_ids = q_bridge_bits2indices($vlan['dot1qVlanCurrentEgressPorts'] ?? $vlan['Q-BRIDGE-MIB::dot1qVlanStaticEgressPorts'] ?? '');

            foreach ($egress_ids as $port_id) {
                $ifIndex = $dot1dBasePortIfIndex[$port_id] ?? 0;
                $vlanArray['ports'][] = [
                    'vlan' => $vlan_id,
                    'baseport' => $ifIndex,
                    'untagged' => (in_array($port_id, $untagged_ids) ? 1 : 0),
                ];
            }
        }

        return $vlanArray;
    }

    private function discoverVlans8021(Device $device): array
    {
        $vlanArray = [];

        $oids = SnmpQuery::hideMib()->walk('IEEE8021-Q-BRIDGE-MIB::ieee8021QBridgeVlanStaticUntaggedPorts')->table(2);
        $oids = SnmpQuery::hideMib()->walk('IEEE8021-Q-BRIDGE-MIB::ieee8021QBridgeVlanStaticEgressPorts')->table(2, $oids);
        $oids = SnmpQuery::hideMib()->walk('IEEE8021-Q-BRIDGE-MIB::ieee8021QBridgeVlanStaticName')->table(2, $oids);

        if (empty($oids)) {
            return $vlanArray;
        }

        $dot1dBasePortIfIndex = SnmpQuery::hideMib()->walk('BRIDGE-MIB::dot1dBasePortIfIndex')->table();
        $dot1dBasePortIfIndex = $dot1dBasePortIfIndex['dot1dBasePortIfIndex'] ?? [];

        foreach ($oids as $vlan_domain_id => $vlan_domains) {
            foreach ($vlan_domains as $vlan_id => $data) {
                $vlanArray['vlans'][] = [
                    'vlan_vlan' => $vlan_id,
                    'vlan_domain' => $vlan_domain_id,
                    'vlan_name' => $data['ieee8021QBridgeVlanStaticName'] ?? '',
                ];

                //portmap for untagged ports
                $untagged_ids = q_bridge_bits2indices($data['ieee8021QBridgeVlanStaticUntaggedPorts'] ?? '');

                //portmap for members ports (might be tagged)
                $egress_ids = q_bridge_bits2indices($data['ieee8021QBridgeVlanStaticEgressPorts'] ?? '');

                foreach ($egress_ids as $port_id) {
                    $ifIndex = $dot1dBasePortIfIndex[$port_id] ?? 0;
                    $vlanArray['ports'][] = [
                        'vlan' => $vlan_id,
                        'baseport' => $ifIndex,
                        'untagged' => (in_array($port_id, $untagged_ids) ? 1 : 0),
                    ];
                }
            }
        }

        return $vlanArray;
    }
}
