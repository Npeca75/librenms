<?php

/**
 * VlanSearch.php
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2022  Peca Nesovanovic
 * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 */

namespace LibreNMS\Plugins;

class VlanSearch
{
    public static function menu()
    {
        echo '<li><a href="plugin/p=VlanSearch">VlanSearch</a></li>';
    }
}
