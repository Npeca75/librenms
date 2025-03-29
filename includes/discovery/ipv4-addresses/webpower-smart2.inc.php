<?php
/**
 * webpower2.inc.php
 *
 * IPv4 address discovery file for WebPower2 / USHA
 *
 *
 * @package    LibreNMS
 * @link       https://www.librenms.org
 *
 * @copyright  2024 Peca Nesovanovic
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */

$tmp = SnmpQuery::cache()->hideMib()->options(['-OQUs'])->walk('IP-MIB::ipAddrTable')->table(2);
if (! empty($tmp)) {
    unset($valid_v4);
    foreach ($tmp as $key => $entry) {
        discover_process_ipv4($valid_v4, $device, $entry['ipAdEntIfIndex'], $entry['ipAdEntAddr'], $entry['ipAdEntNetMask'], $context_name);
    }
}
