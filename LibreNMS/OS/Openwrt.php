<?php

/**
 * Openwrt.php
 *
 * @copyright  2023 Peca Nesovanovic
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */

namespace LibreNMS\OS;

use App\Models\Device;
use LibreNMS\Device\WirelessSensor;
use LibreNMS\Enum\WirelessSensorType;
use LibreNMS\Interfaces\Discovery\OSDiscovery;
use LibreNMS\Interfaces\Discovery\Sensors\WirelessApCountDiscovery;
use LibreNMS\Interfaces\Discovery\Sensors\WirelessCcqDiscovery;
use LibreNMS\Interfaces\Discovery\Sensors\WirelessClientsDiscovery;
use LibreNMS\Interfaces\Discovery\Sensors\WirelessFrequencyDiscovery;
use LibreNMS\Interfaces\Discovery\Sensors\WirelessPowerDiscovery;
use LibreNMS\OS;
use LibreNMS\Util\Oid;

class Openwrt extends OS implements
    OSDiscovery,
    WirelessClientsDiscovery,
    WirelessFrequencyDiscovery,
    WirelessPowerDiscovery,
    WirelessCcqDiscovery,
    WirelessApCountDiscovery
{
    /**
     * Retrieve basic information about the OS / device
     */
    public function discoverOS(Device $device): void
    {
        $device->version = explode(' ', snmp_get($this->getDeviceArray(), 'NET-SNMP-EXTEND-MIB::nsExtendOutput1Line."distro"', '-Osqnv'))[1];
        $device->hardware = snmp_get($this->getDeviceArray(), 'NET-SNMP-EXTEND-MIB::nsExtendOutput1Line."hardware"', '-Osqnv');
        $device->features = snmp_get($this->getDeviceArray(), 'NET-SNMP-EXTEND-MIB::nsExtendOutput1Line."features"', '-Osqnv');
    }

    public function discoverWirelessClients()
    {
        $ret = [];
        $data = $this->getCacheTable('UCD-SNMP-MIB::ucdavis');
        if (! empty($data['ucdavis.7890.10.101.1'])) {
            $apsnr = $data['ucdavis.7890.10.101.1'];
            $base = 'ucdavis.7890.10.101.';
            for ($f = 0; $f < $apsnr; $f++) {
                $indexSsid = 2 + ($f * 5);
                $indexFreq = 4 + ($f * 5);
                $indexValue = 3 + ($f * 5);
                $freq = $data[$base . $indexFreq];
                $ssid = substr((string) $freq, 0, 1) . 'G: ' . $data[$base . $indexSsid];
                $value = $data[$base . $indexValue];
                $oid = Oid::of('UCD-SNMP-MIB::ucdavis.7890.10.101.' . $indexValue)->toNumeric();
                $ret[] = new WirelessSensor(WirelessSensorType::Clients, $this->getDeviceId(), $oid, 'openwrt', $indexValue, $ssid, $value);
            }
        }

        return $ret;
    }

    public function discoverWirelessFrequency()
    {
        $ret = [];
        $data = $this->getCacheTable('UCD-SNMP-MIB::ucdavis');
        if (! empty($data['ucdavis.7890.10.101.1'])) {
            $apsnr = $data['ucdavis.7890.10.101.1'];
            $base = 'ucdavis.7890.10.101.';
            for ($f = 0; $f < $apsnr; $f++) {
                $indexSsid = 2 + ($f * 5);
                $indexFreq = 4 + ($f * 5);
                $indexValue = 4 + ($f * 5);
                $freq = $data[$base . $indexFreq];
                $ssid = substr((string) $freq, 0, 1) . 'G: ' . $data[$base . $indexSsid];
                $value = $data[$base . $indexValue];
                $oid = Oid::of('UCD-SNMP-MIB::ucdavis.7890.10.101.' . $indexValue)->toNumeric();
                $ret[] = new WirelessSensor(WirelessSensorType::Frequency, $this->getDeviceId(), $oid, 'openwrt', $indexValue, $ssid, $value);
            }
        }

        return $ret;
    }

    public function discoverWirelessPower()
    {
        $ret = [];
        $data = $this->getCacheTable('UCD-SNMP-MIB::ucdavis');
        if (! empty($data['ucdavis.7890.10.101.1'])) {
            $apsnr = $data['ucdavis.7890.10.101.1'];
            $base = 'ucdavis.7890.10.101.';
            for ($f = 0; $f < $apsnr; $f++) {
                $indexSsid = 2 + ($f * 5);
                $indexFreq = 4 + ($f * 5);
                $indexValue = 5 + ($f * 5);
                $freq = $data[$base . $indexFreq];
                $ssid = substr((string) $freq, 0, 1) . 'G: ' . $data[$base . $indexSsid];
                $value = $data[$base . $indexValue];
                $oid = Oid::of('UCD-SNMP-MIB::ucdavis.7890.10.101.' . $indexValue)->toNumeric();
                $ret[] = new WirelessSensor(WirelessSensorType::Power, $this->getDeviceId(), $oid, 'openwrt', $indexValue, $ssid, $value);
            }
        }

        return $ret;
    }

    public function discoverWirelessCcq()
    {
        $ret = [];
        $data = $this->getCacheTable('UCD-SNMP-MIB::ucdavis');
        if (! empty($data['ucdavis.7890.10.101.1'])) {
            $apsnr = $data['ucdavis.7890.10.101.1'];
            $base = 'ucdavis.7890.10.101.';
            for ($f = 0; $f < $apsnr; $f++) {
                $indexSsid = 2 + ($f * 5);
                $indexFreq = 4 + ($f * 5);
                $indexValue = 6 + ($f * 5);
                $freq = $data[$base . $indexFreq];
                $ssid = substr((string) $freq, 0, 1) . 'G: ' . $data[$base . $indexSsid];
                $value = $data[$base . $indexValue];
                $oid = Oid::of('UCD-SNMP-MIB::ucdavis.7890.10.101.' . $indexValue)->toNumeric();
                $ret[] = new WirelessSensor(WirelessSensorType::Ccq, $this->getDeviceId(), $oid, 'openwrt', $indexValue, $ssid, $value);
            }
        }

        return $ret;
    }

    public function discoverWirelessApCount()
    {
        $ret = [];
        $data = $this->getCacheTable('UCD-SNMP-MIB::ucdavis');
        if (! empty($data['ucdavis.7890.10.101.1'])) {
            $apsnr = $data['ucdavis.7890.10.101.1'];
            $oid = Oid::of('UCD-SNMP-MIB::ucdavis.7890.10.101.1')->toNumeric();
            $ret[] = new WirelessSensor(WirelessSensorType::ApCount, $this->getDeviceId(), $oid, 'openwrt', 1, 'APs', $apsnr);
        }

        return $ret;
    }
}
