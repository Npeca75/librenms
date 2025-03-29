<?php
/**
 * Ligoos.php
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2023 Peca Nesovanovic
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */

namespace LibreNMS\OS;

use LibreNMS\Device\WirelessSensor;
use LibreNMS\Interfaces\Discovery\Sensors\WirelessClientsDiscovery;
use LibreNMS\Interfaces\Discovery\Sensors\WirelessFrequencyDiscovery;
use LibreNMS\Interfaces\Discovery\Sensors\WirelessNoiseFloorDiscovery;
use LibreNMS\Interfaces\Discovery\Sensors\WirelessQualityDiscovery;
use LibreNMS\OS;

class Ligoos extends OS implements
    WirelessClientsDiscovery,
    WirelessFrequencyDiscovery,
    WirelessNoiseFloorDiscovery,
    WirelessQualityDiscovery
{
    public function discoverWirelessClients()
    {
        $sensors = [];
        $data = $this->getCacheTable('LIGO-WIRELESS-MIB::ligoWiIfConfTable');

        foreach ($data as $index => $entry) {
            $freq = $entry['ligoWiIfFrequency'] ? substr($entry['ligoWiIfFrequency'], 0, 1) . 'G' : 'SSID';
            $sensors[] = new WirelessSensor(
                'clients',
                $this->getDeviceId(),
                '.1.3.6.1.4.1.32750.3.10.1.2.1.1.16.' . $index,
                'ligoos',
                $index,
                "$freq: " . $entry['ligoWiIfESSID'],
                $entry['ligoWiIfAssocNodeCount']
            );
        }

        return $sensors;
    }

    public function discoverWirelessFrequency()
    {
        $sensors = [];
        $data = $this->getCacheTable('LIGO-WIRELESS-MIB::ligoWiIfConfTable');

        foreach ($data as $index => $entry) {
            $freq = substr($entry['ligoWiIfFrequency'], 0, 1) . 'G';
            $sensors[] = new WirelessSensor(
                'frequency',
                $this->getDeviceId(),
                '.1.3.6.1.4.1.32750.3.10.1.2.1.1.6.' . $index,
                'ligoos',
                $index,
                "$freq: " . $entry['ligoWiIfESSID'],
                $entry['ligoWiIfFrequency']
            );
        }

        return $sensors;
    }

    public function discoverWirelessNoiseFloor()
    {
        $sensors = [];
        $data = $this->getCacheTable('LIGO-WIRELESS-MIB::ligoWiIfConfTable');

        foreach ($data as $index => $entry) {
            $freq = $entry['ligoWiIfFrequency'] ? substr($entry['ligoWiIfFrequency'], 0, 1) . 'G' : 'SSID';
            $sensors[] = new WirelessSensor(
                'noise-floor',
                $this->getDeviceId(),
                '.1.3.6.1.4.1.32750.3.10.1.2.1.1.15.' . $index,
                'ligoos',
                $index,
                "$freq: " . $entry['ligoWiIfESSID'],
                $entry['ligoWiIfNoiseLevel']
            );
        }

        return $sensors;
    }

    public function discoverWirelessQuality()
    {
        $sensors = [];
        $data = $this->getCacheTable('LIGO-WIRELESS-MIB::ligoWiIfConfTable');

        foreach ($data as $index => $entry) {
            $freq = $entry['ligoWiIfFrequency'] ? substr($entry['ligoWiIfFrequency'], 0, 1) . 'G' : 'SSID';
            $sensors[] = new WirelessSensor(
                'quality',
                $this->getDeviceId(),
                '.1.3.6.1.4.1.32750.3.10.1.2.1.1.12.' . $index,
                'ligoos',
                $index,
                "$freq: " . $entry['ligoWiIfESSID'],
                $entry['ligoWiIfLinkQuality']
            );
        }

        return $sensors;
    }
}
