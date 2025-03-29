<?php
/**
 * mrv-sw.inc.php
 *
 * IPv6 address discovery file for mrv-sw OS
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2022 Peca Nesovanovic
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */
d_echo('MRV: IPv6 addressess');

use LibreNMS\Util\IPv6;

$ift = SnmpQuery::hideMib()->walk('IPV6-MIB::ipv6IfTable')->table(2);
$oids = SnmpQuery::hideMib()->walk('IPV6-MIB::ipv6AddrTable')->table(2, $ift);
if (! empty($oids)) {
    foreach ($oids as $fakeIndex => $portData) {
        if (isset($portData['ipv6IfPhysicalAddress'])) {
            $mac = implode(array_map('zeropad', explode(':', $portData['ipv6IfPhysicalAddress'])));
            $port_id = find_port_id(null, null, $device['device_id'], $mac);
            if ($port_id) {
                $ifIndex = get_port_by_id($port_id)['ifIndex'];
                foreach ($portData as $ipv6Addr => $ipv6Data) {
                    if (! is_array($ipv6Data)) {
                        continue;
                    }
                    $addrType = $ipv6Data['ipv6AddrType'] == 2 ? 'stateful' : 'stateless';
                    $ipv6 = new IPv6($ipv6Addr);
                    discover_process_ipv6(
                        $valid,
                        $ifIndex,
                        $ipv6->uncompressed(),
                        $ipv6Data['ipv6AddrPfxLength'],
                        $addrType,
                        $device['context_name']
                    );
                }
            }
        } else {
            continue; //probably loopback interface
        }
    }
}
