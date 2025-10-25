#/*
# *
# * LibreNMS Mikrotik Wireguard interface poller script
# *
# * @package    LibreNMS
# * @link       https://www.librenms.org
# *
# * @author     Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
# * @copyright  2023 Peca Nesovanovic
# */

:local wgInts;
:local wgIntName;
:local wgPeers;
:local wgPeerName;
:local tx;
:local rx;
:local handshake;
:local out "{\"errorString\": \"\", \"error\": 0, \"version\": 1, \"data\": {"
:local flcl 0;
:local weekend 0;
:local dayend 0;
:local weeks 0;
:local days 0;
:local time 0;
:local hours 0;
:local minutes;
:local seconds;
:local handshakeMinutes 0;

:foreach wgInts in=[/interface/wireguard find ] do={
    :set wgIntName [/interface/wireguard get $wgInts name]
    :if ($flcl = 1) do={
        :set out "$out }, "
    }
    :set flcl 0;
    :set out "$out\"$wgIntName\": {"

    :foreach wgPeers in=[/interface/wireguard/peers find where interface="$wgIntName"] do={
        :set wgPeerName [/interface/wireguard/peers get $wgPeers comment]
        if ($wgPeerName ="") do={
            :set wgPeerName [/interface/wireguard/peers get $wgPeers public-key]
        }
        :set tx [/interface/wireguard/peers get $wgPeers tx]
        :set rx [/interface/wireguard/peers get $wgPeers rx]
        :set handshake [/interface/wireguard/peers get $wgPeers last-handshake]
        :if ([:find $handshake "w" -1] > 0) do={
            :set weekend [:find $handshake "w" -1];
            :set weeks [:pick $handshake 0 $weekend];
            :set weekend ($weekend+1);
        };
        :if ([:find $handshake "d" -1] > 0) do={
            :set dayend [:find $handshake "d" -1];
            :set days [:pick $handshake $weekend $dayend];
        };
        :set time [:pick $handshake ([:len $handshake]-8) [:len $handshake]];
        :set hours [:pick $time 0 2];
        :set minutes [:pick $time 3 5];
        :set seconds [:pick $time 6 8];
        :set handshakeMinutes [($weeks*10080*7+$days*1440+$hours*60+$minutes)];

        :if ($flcl = 1) do={
            :set out "$out, ";
        }
        :set out "$out\"$wgPeerName\": {\"minutes_since_last_handshake\": $handshakeMinutes, \"bytes_rcvd\": $rx, \"bytes_sent\": $tx}"
        :set flcl 1;
    }
}
:set out "$out}}}";
:put $out
