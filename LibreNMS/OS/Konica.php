<?php

/*
 * Konica.php
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
use LibreNMS\Interfaces\Data\DataStorageInterface;

class Konica extends \LibreNMS\OS\Shared\Printer
{
    public function discoverOS(Device $device): void
    {
        parent::discoverOS($device); // yaml

        $this->customSysName($device);
    }

    public function pollOS(DataStorageInterface $datastore): void
    {
        $this->customSysName($this->getDevice());
    }

    private function customSysName(Device $device): void
    {
        if (empty($device->sysName)) {
            $snmpGet = [
                'sysname' => '.1.3.6.1.4.1.18334.1.1.2.1.5.7.1.1.1.12.1',
                'domain' => '.1.3.6.1.4.1.18334.1.1.2.1.5.7.1.1.1.13.1',
            ];
            foreach ($snmpGet as $name => $oid) {
                $values[$name] = trim((string) preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', (string) \SnmpQuery::get($oid)->value()));
            }
            $device->sysName = $values['sysname'] . '.' . $values['domain'];
        }
    }
}
