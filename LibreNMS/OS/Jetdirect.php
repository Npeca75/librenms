<?php

/*
 * Jetdirect.php
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @package    LibreNMS
 * @link       https://www.librenms.org
 * @copyright  2024 Peca Nesovanovic
 * @author     peca.nesovanovic@sattrakt.com
 */

namespace LibreNMS\OS;

use App\Models\Device;
use App\Models\Location;
use Illuminate\Support\Str;
use LibreNMS\Interfaces\Data\DataStorageInterface;
use LibreNMS\Util\StringHelpers;

class Jetdirect extends Shared\Printer
{
    public function discoverOS(Device $device): void
    {
        parent::discoverOS($device); // yaml

        $snmpGet = [
            'version1' => 'HP-LASERJET-COMMON-MIB::fw-rom-revision.0',
            'version2' => 'HP-LASERJET-COMMON-MIB::fw-rom-datecode.0',
            'serial1' => 'HP-LASERJET-COMMON-MIB::serial-number.0',
            'hardware1' => 'HP-LASERJET-COMMON-MIB::model-name.0',
            'hardware2' => '1.3.6.1.2.1.43.5.1.1.16.1',
            'hardware3' => '1.3.6.1.4.1.236.11.5.1.1.1.1.0',
            'contact1' => 'HP-LASERJET-COMMON-MIB::system-contact.0',
            'contact2' => 'Printer-MIB::prtGeneralCurrentOperator.1',
            'features1' => 'HP-LASERJET-COMMON-MIB::mio1-manufacturing-info.0',
            'features2' => 'HP-LASERJET-COMMON-MIB::mio2-manufacturing-info.0',
            'features3' => 'HP-LASERJET-COMMON-MIB::mio3-manufacturing-info.0',
            'features4' => 'HP-LASERJET-COMMON-MIB::mio4-manufacturing-info.0',
        ];

        foreach ($snmpGet as $name => $oid) {
            $values[$name] = StringHelpers::trimHexGarbage(\SnmpQuery::get($oid)->value());
        }

        $ver1 = explode(' ', $values['version1'])[0];
        $device->version = (empty($ver1)) ? $values['version2'] : $ver1;

        $device->serial = $values['serial1'];

        if (Str::contains($values['hardware2'], '107')) {
            $device->hardware = $values['hardware2'];
        } elseif (Str::contains($values['hardware3'], '137')) {
            $device->hardware = $values['hardware3'];
        } else {
            $device->hardware = empty($values['hardware1']) ? $values['hardware1'] : $values['hardware2'];
        }

        $device->sysContact = $device->sysContact ?: $values['contact1'];
        $device->sysContact = $device->sysContact ?: $values['contact2'];

        //features
        for ($f = 1; $f < 5; $f++) {
            if (! empty($values['features' . $f])) {
                $tmp = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', (string) hex2str(str_replace(' ', '', trim(strtoupper($values['features' . $f])))));
                if (Str::contains($tmp, 'Revision')) {
                    $device->features = $tmp;
                    break;
                }
            }
        }

        $this->customSysName($device);
    }

    public function pollOS(DataStorageInterface $datastore): void
    {
        $this->customSysName($this->getDevice());
    }

    public function fetchLocation(): Location
    {
        $location = parent::fetchLocation();
        if (empty($location->location)) {
            $value = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', (string) \SnmpQuery::get('HP-LASERJET-COMMON-MIB::device-location.0')->value());
            preg_match('/^(\S+)\s\[{0,1}(\d+\.\d+),\s{0,1}(\d+\.\d+)\]{0,1}/', (string) $value, $tmp);
            $location->location = str_replace(', ', ',', $tmp[0]);
            $location->lat = (float) $tmp[2];
            $location->lng = (float) $tmp[3];
        }

        return $location;
    }

    private function customSysName(Device $device): void
    {
        $domain = '';

        $snmpGet = [
            'location1' => 'HP-LASERJET-COMMON-MIB::device-location.0',
            'domain1' => '.1.3.6.1.4.1.11.2.4.3.5.16.0',
            'domain2' => '.1.3.6.1.4.1.11.2.4.3.5.49.0',
            'domain3' => '.1.3.6.1.4.1.236.11.5.1.12.2.21.0',
        ];

        foreach ($snmpGet as $name => $oid) {
            $values[$name] = trim((string) preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', (string) \SnmpQuery::get($oid)->value()));
        }

        if ($device->hardware == 'HP LaserJet Pro MFP M26nw') {
            $device->sysName = $values['location1'];
        } else {
            for ($f = 1; $f < 4; $f++) {
                if (! empty($values['domain' . $f])) {
                    $domain = $values['domain' . $f];
                    break;
                }
            }
            // last resort
            if (empty($domain)) {
                $domain = 'prn.' . strtolower(explode(' ', $device->location)[0]);
            }

            $device->sysName = $device->sysName . '.' . $domain;
        }
    }
}
