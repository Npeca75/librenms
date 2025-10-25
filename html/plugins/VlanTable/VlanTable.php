<?php

/**
 * VlanTable.php
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2022  Peca Nesovanovic
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */

namespace LibreNMS\Plugins;

class VlanTable
{
    public static function menu()
    {
        echo '<li><a href="plugin/v1/VlanTable">VlanTable</a></li>';
    }
}
