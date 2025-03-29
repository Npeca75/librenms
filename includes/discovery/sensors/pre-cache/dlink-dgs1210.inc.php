<?php
/*
 * LibreNMS pre-cache module for Dlink-dgs1210 OS
 *
 *
 * @package    LibreNMS
 * @link       https://www.librenms.org
 *
 * @copyright  2022 Peca Nesovanovic
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */
echo 'ddmStatusTable ';
$oids20 = SnmpQuery::hideMib()->walk('DGS-1210-20ME-AX::ddmStatusTable')->table(1);
$oids28 = SnmpQuery::hideMib()->walk('DGS-1210-28ME-AX::ddmStatusTable')->table(1, $oids20);
echo 'ddmThresholdMgmtEntry ';
$oidm20 = SnmpQuery::hideMib()->walk('DGS-1210-20ME-AX::ddmThresholdMgmtEntry')->table(2, $oids28);
$pre_cache['dgs1210-ddm'] = SnmpQuery::hideMib()->walk('DGS-1210-28ME-AX::ddmThresholdMgmtEntry')->table(2, $oidm20);
