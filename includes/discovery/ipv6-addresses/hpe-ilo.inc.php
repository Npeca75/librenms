<?php
/**
 * hpe-ilo.inc.php
 *
 * LibreNMS discovery module for HPE iLO IPv6 Addresses
 *
 * @link       https://www.librenms.org
 *
 * @copyrigh   2022 Peca Nesovanovic
 *
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */
use App\Models\Port;
use LibreNMS\Util\IPv6;

$sm2mib = SnmpQuery::hidemib()->walk('CPQSM2-MIB::cpqSm2NicIpv6')->table(1);
if (isset($sm2mib)) {
    d_echo('IPv6: hpe-ilo');
    foreach ($sm2mib as $key => $v6Data) {
        unset($ifIndex);
        if (isset($v6Data['cpqSm2NicIpv6Slaac'])) {
            $ipv6 = new IPv6($v6Data['cpqSm2NicIpv6Slaac']);
            $pfxLen = explode('/', $v6Data['cpqSm2NicIpv6Slaac'])[1];
            $tmp6 = explode(':', $ipv6->uncompressed());
            $macEnd = explode(':', $tmp6[max(array_keys($tmp6))])[0];
            $ifIndex = Port::where('device_id', $device['device_id'])->where('ifPhysAddress', 'like', '%' . $macEnd)->value('ifIndex');
            if ($ifIndex) {
                discover_process_ipv6($valid, $ifIndex, $ipv6->uncompressed(), $pfxLen, 'slaac', $device['context_name']);
            }
        }
        if (isset($v6Data['cpqSm2NicIpv6Address']) && isset($ifIndex)) { //manual IPv6 address && reuse index from SLAAC
            $ipv6 = new IPv6($v6Data['cpqSm2NicIpv6Address']);
            $pfxLen = explode('/', $v6Data['cpqSm2NicIpv6Address'])[1];
            discover_process_ipv6($valid, $ifIndex, $ipv6->uncompressed(), $pfxLen, 'manual', $device['context_name']);
        }
    }
}
