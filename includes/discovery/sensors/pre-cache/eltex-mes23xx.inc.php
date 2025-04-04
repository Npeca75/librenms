<?php

/*
 * LibreNMS pre-cache module for Eltex-mes23xx OS
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
 *
 * @copyright  2021 Peca Nesovanovic
 *
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */

echo 'rlPethPsePortPowerLimit ';
$oidpl = snmpwalk_cache_multi_oid($device, 'MARVELL-POE-MIB::rlPethPsePortPowerLimit', [], 'MARVELL-POE-MIB');
echo 'rlPethPsePortOutputPower ';
$pre_cache['eltex-mes23xx-poe'] = snmpwalk_cache_multi_oid($device, 'MARVELL-POE-MIB::rlPethPsePortOutputPower', $oidpl, 'MARVELL-POE-MIB');
