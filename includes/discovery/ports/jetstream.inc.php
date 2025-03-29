<?php
/**
 * jetstream.inc.php
 *
 * LibreNMS Jetstream port rewite rule
 *
 *
 * @package    LibreNMS
 * @link       https://www.librenms.org
 *
 * @copyright  2022 Peca Nesovanovic
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */
foreach ($port_stats as $index => $port_data) {
    if (preg_match('/gigabitethernet\s(\d+\/\d+\/\d+)\s\:\s(fiber|copper)/i', $port_data['ifName'], $jsArray)) {
        $base = 'Ethernet' . $jsArray[1];
        $name = ($jsArray[2] == 'fiber') ? 'Fiber' . $base : 'Gigabit' . $base;
        $port_stats[$index]['ifName'] = $port_stats[$index]['ifDescr'] = $name;
    }
}
