#/*
# *
# * LibreNMS Mikrotik VLAN discovery script
# *
# * @package    LibreNMS
# * @link       https://www.librenms.org
# *
# * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
# * @copyright  2022 Peca Nesovanovic
# * modified 2023.09  - add vlan names from bridge->vlan comment
# * modified 2024.06  - do not use dynamic entries for vlan names
# *
# */


:global vlanst [:toarray ""]
:global vlansu [:toarray ""]
:global vlansn [:toarray ""]

:foreach i in [/interface bridge vlan find] do={
    :local intf [/interface bridge vlan get $i bridge]
    :local vlid [/interface bridge vlan get $i vlan-ids]
    :local descr [/interface bridge vlan get $i comment]
    :local d [/interface bridge vlan get $i dynamic]

    :if ($descr != "" && $d = false) do={
        :set $vlansn ($vlansn, "$vlid,$descr")
    }

    :foreach t in [/interface bridge vlan get $i tagged] do={
        :set $vlanst ($vlanst, "$vlid,$t")
    }

    :foreach u in [/interface bridge vlan get $i current-untagged] do={
        :set $vlansu ($vlansu, "$vlid,$u")
    }

    :foreach u in [/interface bridge port find where bridge=$intf and pvid=$vlid] do={
        :local iu [/interface bridge port get $u interface]
        :local fl 0
        :foreach tmp in $vlansu do={
            :local ar [:toarray $tmp]
            :if ((($ar->0) = $vlid) && (($ar->1) = $iu))  do={
                :set fl 1
            }
        }
        :if ( $fl != 1 ) do={
            :set $vlansu ($vlansu, "$vlid,$iu")
        }
    }
}

:foreach vl in [/interface vlan find ] do={
    :local intf [/interface vlan get $vl interface]
    :local vlid [/interface vlan get $vl vlan-id]
    :local intu [/interface vlan get $vl name]
    :local fl 0

    :foreach tmp in $vlanst do={
        :local ar [:toarray $tmp]
        :if ( (($ar->0) = $vlid) && (($ar->1) = $intf)) do={
            :set fl 1
        }
    }
    :if ( $fl != 1 ) do={
        :set $vlanst ($vlanst, "$vlid,$intf")
    }
    :set $vlansu ($vlansu, "$vlid,$intu")
}

:foreach tmp in $vlansn do={
    :put "N,$tmp"
}


:foreach tmp in $vlanst do={
    :put "T,$tmp"
}

:foreach tmp in $vlansu do={
    :put "U,$tmp"
}
