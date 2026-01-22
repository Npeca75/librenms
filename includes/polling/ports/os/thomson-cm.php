<?php

/**
 * @copyrigh   2025 Peca Nesovanovic
 *
 * @author     peca.nesovanovic@sattrakt.com
 */
$oids = SnmpQuery::walk('DOCS-IF-MIB::docsIfSigQUncorrectables')->table(0);

foreach ($oids['DOCS-IF-MIB::docsIfSigQUncorrectables'] as $index => $stats) {
    $port_stats[$index]['ifInErrors'] += $stats;
    $port_stats[$index]['ifInDiscards'] = $stats;
}

unset($oids);
