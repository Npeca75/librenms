<?php

/*
 * Linux.php
 *
 * -Description-
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    LibreNMS
 * @link       http://librenms.org
 * @copyright  2023 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

namespace LibreNMS\OS;

use App\Facades\PortCache;
use App\Models\Device;
use App\Models\EntPhysical;
use App\Models\Transceiver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use LibreNMS\Interfaces\Discovery\VminfoDiscovery;
use LibreNMS\OS;
use LibreNMS\OS\Traits\VminfoLibvirt;
use LibreNMS\OS\Traits\VminfoVmware;
use LibreNMS\OS\Traits\VminfoXcpNg;
use LibreNMS\Interfaces\Discovery\TransceiverDiscovery;
use LibreNMS\Util\Oid;
use LibreNMS\Util\StringHelpers;

use SnmpQuery;

class Linux extends Shared\Unix implements VminfoDiscovery, TransceiverDiscovery
{
    // NOTE: Only Linux specific stuff should go here, most things should be in Unix

    use VminfoLibvirt, VminfoVmware, VminfoXcpNg {
        VminfoLibvirt::discoverVminfo as discoverLibvirtVminfo;
        VminfoVmware::discoverVmInfo as discoverVmwareVminfo;
        VminfoXcpNg::discoverVmInfo as discoverXcpNgVminfo;
    }

    public function discoverTransceiverSensors($types = ['dbm', 'current', 'temperature', 'voltage']): void
    {
        foreach ($this->getEthtool() as $ifName => $ifData) {
            Log::debug('Processing interface: ' . $ifName);

            if (in_array('current', $types) && isset($ifData['laser_bias_current_ma'])) {
                app('sensor-discovery')->discover(new \App\Models\Sensor([
                    'poller_type' => 'snmp',
                    'sensor_class' => 'current',
                    'sensor_oid' => 0,
                    'sensor_index' => $ifData['ifIndex'],
                    'sensor_type' => 'ethtool',
                    'sensor_descr' => 'SfpBias-' . $ifName,
                    'sensor_divisor' => 1000,
                    'sensor_multiplier' => 1,
                    'sensor_limit_low' => $ifData['laser_bias_current_low_alarm_threshold_ma'] / 1000,
                    'sensor_limit_low_warn' => $ifData['laser_bias_current_low_warning_threshold_ma'] / 1000,
                    'sensor_limit_warn' => $ifData['laser_bias_current_high_warning_threshold_ma'] / 1000,
                    'sensor_limit' => $ifData['laser_bias_current_high_alarm_threshold_ma'] / 1000,
                    'sensor_current' => $ifData['laser_bias_current_ma'] / 1000,
                    'user_func' => null,
#                    'entPhysicalIndex' => $map[$ifIndex] ?? null,
#                    'entPhysicalIndex_measured' => 'port',
#                    'group' => 'transceiver',
                ]));
            }

            if (in_array('temperature', $types) && isset($ifData['module_temperature_celsius'])) {
                app('sensor-discovery')->discover(new \App\Models\Sensor([
                    'poller_type' => 'snmp',
                    'sensor_class' => 'temperature',
                    'sensor_oid' => 0,
                    'sensor_index' => $ifData['ifIndex'],
                    'sensor_type' => 'ethtool',
                    'sensor_descr' => 'SfpTemp-' . $ifName,
                    'sensor_divisor' => 1,
                    'sensor_multiplier' => 1,
                    'sensor_limit_low' => $ifData['module_temperature_low_alarm_threshold_celsius'],
                    'sensor_limit_low_warn' => $ifData['module_temperature_low_warning_threshold_celsius'],
                    'sensor_limit_warn' => $ifData['module_temperature_high_warning_threshold_celsius'],
                    'sensor_limit' => $ifData['module_temperature_high_alarm_threshold_celsius'],
                    'sensor_current' => $ifData['module_temperature_celsius'],
                    'user_func' => null,
                ]));
            }

            if (in_array('voltage', $types) && isset($ifData['module_voltage_v'])) {
                app('sensor-discovery')->discover(new \App\Models\Sensor([
                    'poller_type' => 'snmp',
                    'sensor_class' => 'voltage',
                    'sensor_oid' => 0,
                    'sensor_index' => $ifData['ifIndex'],
                    'sensor_type' => 'ethtool',
                    'sensor_descr' => 'SfpVolt-' . $ifName,
                    'sensor_divisor' => 1,
                    'sensor_multiplier' => 1,
                    'sensor_limit_low' => $ifData['module_voltage_low_alarm_threshold_v'],
                    'sensor_limit_low_warn' => $ifData['module_voltage_low_warning_threshold_v'],
                    'sensor_limit_warn' => $ifData['module_voltage_high_warning_threshold_v'],
                    'sensor_limit' => $ifData['module_voltage_high_alarm_threshold_v'],
                    'sensor_current' => $ifData['module_voltage_v'],
                    'user_func' => null,
                ]));
            }

            if (in_array('dbm', $types) && isset($ifData['laser_output_power_dbm'])) {
                app('sensor-discovery')->discover(new \App\Models\Sensor([
                    'poller_type' => 'snmp',
                    'sensor_class' => 'dbm',
                    'sensor_oid' => 0,
                    'sensor_index' => 'tx'. $ifData['ifIndex'],
                    'sensor_type' => 'ethtool',
                    'sensor_descr' => 'SfpTx-' . $ifName,
                    'sensor_divisor' => 1,
                    'sensor_multiplier' => 1,
                    'sensor_limit_low' => $ifData['laser_output_power_low_alarm_threshold_dbm'],
                    'sensor_limit_low_warn' => $ifData['laser_output_power_low_warning_threshold_dbm'],
                    'sensor_limit_warn' => $ifData['laser_output_power_high_warning_threshold_dbm'],
                    'sensor_limit' => $ifData['laser_output_power_high_alarm_threshold_dbm'],
                    'sensor_current' => $ifData['laser_output_power_dbm'],
                    'user_func' => null,
                ]));
            }

            if (in_array('dbm', $types) && isset($ifData['receiver_signal_average_optical_power_dbm'])) {
                app('sensor-discovery')->discover(new \App\Models\Sensor([
                    'poller_type' => 'snmp',
                    'sensor_class' => 'dbm',
                    'sensor_oid' => 0,
                    'sensor_index' => 'rx' . $ifData['ifIndex'],
                    'sensor_type' => 'ethtool',
                    'sensor_descr' => 'SfpRx-' . $ifName,
                    'sensor_divisor' => 1,
                    'sensor_multiplier' => 1,
                    'sensor_limit_low' => $ifData['laser_rx_power_low_alarm_threshold_dbm'],
                    'sensor_limit_low_warn' => $ifData['laser_rx_power_low_warning_threshold_dbm'],
                    'sensor_limit_warn' => $ifData['laser_rx_power_high_warning_threshold_dbm'],
                    'sensor_limit' => $ifData['laser_rx_power_high_alarm_threshold_dbm'],
                    'sensor_current' => $ifData['receiver_signal_average_optical_power_dbm'],
                    'user_func' => null,
                ]));
            }
        }
    }

    public function pollTransceiverSensors($sensor = []): float
    {
        foreach ($this->getEthtool() as $ifName => $ifData) {
            [$type, $name] = explode('-', $sensor['sensor_descr']);
            Log::debug('Polling interface: ' . $ifName . ' for ' . $type);

            if ($type == 'SfpBias' && $ifName == $name && isset($ifData['laser_bias_current_ma'])) {
                return (float) $ifData['laser_bias_current_ma'];
            }

            if ($type == 'SfpTemp' && $ifName == $name  && isset($ifData['module_temperature_celsius'])) {
                return (float) $ifData['module_temperature_celsius'];
            }

            if ($type == 'SfpVolt' && $ifName == $name && isset($ifData['module_voltage_v'])) {
                return (float) $ifData['module_voltage_v'];
            }

            if ($type == 'SfpRx' && $ifName == $name && isset($ifData['receiver_signal_average_optical_power_dbm'])) {
                return (float) $ifData['receiver_signal_average_optical_power_dbm'];
            }

            if ($type == 'SfpTx' && $ifName == $name && isset($ifData['laser_output_power_dbm'])) {
                return (float) $ifData['laser_output_power_dbm'];
            }
        }

        return 0;
    }

    public function discoverTransceivers(): Collection
    {
        $trans = new Collection;

        foreach ($this->getEthtool() as $ifName => $ifData) {
            Log::debug('Processing interface: ' . $ifName);
            if (! empty($ifData['ifIndex'])) {
                $lengthValues = array_filter($ifData, fn($key) => str_contains($key, 'length'), ARRAY_FILTER_USE_KEY);
                $length = $lengthValues ? max(array_map('intval', $lengthValues)) : null;
                if (preg_match('/\((.*?)\)/', $ifData['connector'], $matches)) {
                    $connector = $matches[1];
                }
                $trans->push(new Transceiver([ 
                    'port_id' => (int) PortCache::getIdFromIfIndex($ifData['ifIndex'], $this->getDevice()),
                    'index' => $ifData['ifIndex'],
                    'connector' => $connector ?? null,
                    'distance' => $length,
                    'model' => $ifData['vendor_pn'] ?? null,
                    'revision' => $ifData['vendor_rev'] ?? null,
                    'vendor' => $ifData['vendor_name'] ?? null,
                    'serial' => $ifData['vendor_sn'] ?? null,
                    'date' => $ifData['date_code'] ?? null,
                    'ddm' => (($ifData['optical_diagnostics_support'] ?? '') === 'Yes') ? 1 : null,
                    'wavelength' => $ifData['wavelength'] ?? null,
                    'type' => $ifData['transceiver_type'] ?? null,
                    'entity_physical_index' => $ifData['ifIndex'],
                ]));
            }
        }

        return $trans;
    }

    public function discoverVmInfo(): Collection
    {
        if (preg_match('/^XCP-ng/', (string) $this->getDevice()->features)) {
            return $this->discoverXcpNgVminfo();
        }

        $vms = $this->discoverLibvirtVminfo();

        if ($vms->isNotEmpty()) {
            return $vms;
        }

        return $this->discoverVmwareVminfo();
    }

    public function discoverEntityPhysical(): Collection
    {
        return $this->discoverLsiMegaRaidInventory();
    }

    private function discoverLsiMegaRaidInventory(): Collection
    {
        $inventory = new Collection;

        $controller_array = SnmpQuery::hideMib()->walk('LSI-MegaRAID-SAS-MIB::adapterInfoTable')->table(1);

        if (empty($controller_array)) {
            return $inventory; // no controllers, skip the rest.
        }

        foreach ($controller_array as $controller) {
            $inventory->push(new EntPhysical([
                'entPhysicalIndex' => 200 + $controller['adapterID-AIT'],
                'entPhysicalParentRelPos' => $controller['adapterID-AIT'] ?? -1,
                'entPhysicalDescr' => '/C' . $controller['adapterID-AIT'],
                'entPhysicalClass' => 'port',
                'entPhysicalModelName' => $controller['productName'],
                'entPhysicalSerialNum' => $controller['serialNo'],
                'entPhysicalContainedIn' => '0',
                'entPhysicalVendorType' => $controller['adapterVendorID'],
                'entPhysicalFirmwareRev' => $controller['firmwareVersion'],
            ]));
        }

        $bbus = SnmpQuery::hideMib()->walk('LSI-MegaRAID-SAS-MIB::bbuTable')->table(1);
        foreach ($bbus as $bbu) {
            $inventory->push(new EntPhysical([
                'entPhysicalIndex' => 1000 + ($bbu['pdIndex'] ?? null),
                'entPhysicalClass' => 'charge',
                'entPhysicalModelName' => $bbu['deviceName'],
                'entPhysicalSerialNum' => $bbu['serialNumber'],
                'entPhysicalContainedIn' => 200 + $bbu['adpID'],
                'entPhysicalIsFRU' => 'true',
                'entPhysicalFirmwareRev' => $bbu['firmwareStatus'],
            ]));
        }

        $enclosures = SnmpQuery::hideMib()->walk('LSI-MegaRAID-SAS-MIB::enclosureTable')->table(1);
        foreach ($enclosures as $enclosure) {
            $inventory->push(new EntPhysical([
                'entPhysicalIndex' => 210 + $enclosure['deviceId'],
                'entPhysicalVendorType' => $enclosure['slotCount'],
                'entPhysicalParentRelPos' => $enclosure['deviceId'] ?? -1,
                'entPhysicalDescr' => '/C' . ($enclosure['adapterID-ET'] ?? '0') . '/E' . $enclosure['deviceId'],
                'entPhysicalClass' => 'chassis',
                'entPhysicalModelName' => $enclosure['productID'],
                'entPhysicalSerialNum' => $enclosure['enclSerialNumber'],
                'entPhysicalContainedIn' => 200 + $enclosure['adapterID-ET'],
                'entPhysicalMfgName' => $enclosure['vendorID'],
                'entPhysicalFirmwareRev' => $this->handleHex($enclosure['enclFirmwareVersion']),
            ]));
        }

        $drives = SnmpQuery::hideMib()->walk('LSI-MegaRAID-SAS-MIB::physicalDriveTable')->table(1);
        foreach ($drives as $drive) {
            $enclDeviceId = $drive['enclDeviceId'] ?? 0;
            $inventory->push(new EntPhysical([
                'entPhysicalIndex' => 500 + $enclDeviceId * 100 + $drive['physDevID'],
                'entPhysicalParentRelPos' => $drive['slotNumber'] ?? -1,
                'entPhysicalDescr' => '/C' . ($drive['adpID-PDT'] ?? '0') . '/E' . $enclDeviceId . '/S' . ($drive['slotNumber'] ?? '0'),
                'entPhysicalClass' => 'drive',
                'entPhysicalModelName' => $drive['pdProductID'] ?? null,
                'entPhysicalSerialNum' => $drive['pdSerialNumber'] ?? null,
                'entPhysicalContainedIn' => 210 + $enclDeviceId,
                'entPhysicalIsFRU' => 'true',
                'entPhysicalFirmwareRev' => $drive['pdFwversion'] ?? null, // missing
            ]));
        }

        return $inventory;
    }

    private function handleHex(string $string): string
    {
        $string = str_replace("\n", '', $string);
        if (StringHelpers::isHex($string, ' ')) {
            $ascii = StringHelpers::hexToAscii($string, ' ');

            return preg_split('/[^ -~]/', $ascii)[0] ?? $ascii;
        }

        return $string;
    }

    private function getEthtool(): array
    {
        $output = SnmpQuery::cache()->hideMib()
            ->get('NET-SNMP-EXTEND-MIB::nsExtendOutputFull.' . Oid::encodeString('transceivers')->oid)
            ->values()['nsExtendOutputFull[transceivers]'] ?? '';

        if (empty($output)) {
            return [];
        }

        $parsed_json = json_decode((string) $output, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($parsed_json)) {
            Log::debug('Invalid SNMP data');
            return [];
        }

        return $parsed_json['data'] ?? [];
    }

}
