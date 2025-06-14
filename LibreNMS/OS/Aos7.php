<?php

/**
 * Aos6.php
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
 * @link       https://www.librenms.org
 *
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */

namespace LibreNMS\OS;

use LibreNMS\Interfaces\Discovery\VlanDiscovery;
use LibreNMS\OS;
use SnmpQuery;

class Aos7 extends OS implements VlanDiscovery
{
    public function discoverVlans(): array
    {
        $vlanArray = [];

        $vtpdomain_id = '1';
        $vlans = SnmpQuery::mibDir('nokia/aos7')->hideMib()->walk('ALCATEL-IND1-VLAN-MGR-MIB::vlanDescription')->table();

        foreach ($vlans['vlanDescription'] as $vlan_id => $vlan_name) {
            $vlanArray['vlans'][] = [
                'vlan_vlan' => $vlan_id,
                'vlan_name' => $vlan_name,
                'vlan_domain' => $vtpdomain_id,
                'vlan_type' => null,
            ];
        }

        $vlanstype = $vlans = SnmpQuery::mibDir('nokia/aos7')->hideMib()->walk('ALCATEL-IND1-VLAN-MGR-MIB::vpaType')->table();
        foreach ($vlanstype['vpaType'] ?? [] as $vlan_id => $data) {
            foreach ($data as $portidx => $porttype) {
                $vlanArray['ports'][] = [
                    'vlan' => $vlan_id,
                    'baseport' => $portidx,
                    'untagged' => ($porttype == 1 ? 1 : 0),
                ];
            }
        }

        return $vlanArray;
    }
}
