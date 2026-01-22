<?php

namespace LibreNMS\OS;

use App\Facades\PortCache;
use App\Models\Ipv4Address;
use App\Models\Ipv6Address;
use App\Models\Port;
use App\Models\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use LibreNMS\Exceptions\InvalidIpException;
use LibreNMS\Interfaces\Discovery\Ipv4AddressDiscovery;
use LibreNMS\Interfaces\Discovery\Ipv6AddressDiscovery;
use LibreNMS\Interfaces\Discovery\RouteDiscovery;
use LibreNMS\OS;
use LibreNMS\Util\IPv4;
use LibreNMS\Util\IPv6;
use SnmpQuery;

class HpeIlo extends OS implements Ipv4AddressDiscovery, Ipv6AddressDiscovery, RouteDiscovery
{
    public function discoverIpv4Addresses(): Collection
    {
        $ips = new Collection;
        $oids = SnmpQuery::cache()->hideMib()->walk('CPQSM2-MIB::cpqSm2NicConfigTable')->table(1);
        foreach ($oids as $ifData) {
            if (isset($ifData['cpqSm2NicIpAddress'])) {
                $ifIndex = 1000000 + $ifData['cpqSm2NicLocation'];
                $ips->push(new Ipv4Address([
                    'ipv4_address' => $ifData['cpqSm2NicIpAddress'],
                    'ipv4_prefixlen' => $ifData['cpqSm2NicIpSubnetMask'],
                    'port_id' => PortCache::getIdFromIfIndex($ifIndex, $this->getDevice()),
                    'context_name' => '',
                ]));
            }
        }

        return $ips->filter();
    }

    public function discoverIpv6Addresses(): Collection
    {
        $ret = new Collection;
        $types = ['cpqSm2NicIpv6Slaac' => 'slaac', 'cpqSm2NicIpv6Address' => 'manual', 'cpqSm2NicIpv6Dhcp' => 'dhcp'];

        $sm2mib = SnmpQuery::cache()->hideMib()->walk('CPQSM2-MIB::cpqSm2NicIpv6')->table(1);
        foreach ($sm2mib as $v6Data) {
            unset($ifIndex);
            if (isset($v6Data['cpqSm2NicIpv6Slaac'])) {
                $ifIndex = Port::where('device_id', $this->getDeviceId())->where('ifPhysAddress', $this->slaac2mac($v6Data['cpqSm2NicIpv6Slaac']))->value('ifIndex');
                if ($ifIndex) {
                    foreach ($types as $type => $origin) {
                        if (! empty($v6Data[$type])) {
                            $prefixLen = explode('/', (string) $v6Data[$type])[1];
                            try {
                                $ip = new IPv6($v6Data[$type] ?? '');
                                $ret->push(new Ipv6Address([
                                    'ipv6_address' => $ip->uncompressed(),
                                    'ipv6_compressed' => $ip->compressed(),
                                    'ipv6_prefixlen' => $prefixLen,
                                    'ipv6_origin' => $origin,
                                    'port_id' => PortCache::getIdFromIfIndex($ifIndex, $this->getDevice()),
                                    'context_name' => '',
                                ]));
                            } catch (InvalidIpException $e) {
                                Log::error('Failed to parse IP: ' . $e->getMessage());
                                $ret->push(null);
                            }
                        }
                    }
                }
            }
        }

        return $ret->filter();
    }

    public function discoverRoutes(): Collection
    {
        $routes = new Collection;

        $offset = 1000000;
        $hop = $slaac = '';

        $oids = SnmpQuery::cache()->hideMib()->walk('CPQSM2-MIB::cpqSm2NicIpv6')->table(1);
        foreach ($oids as $data) {
            $hop = $data['cpqSm2NicIpv6Gateway'] ?? $hop;
            $slaac = $data['cpqSm2NicIpv6Slaac'] ?? $slaac;
        }
        if ($hop && $slaac) {
            $mac = $this->slaac2mac($slaac);
            $ifIndex = Port::where('device_id', $this->getDeviceId())->where('ifPhysAddress', $this->slaac2mac($slaac))->value('ifIndex');
            if ($ifIndex) {
                $routes->push(new Route([
                    'port_id' => PortCache::getIdFromIfIndex($ifIndex, $this->getDevice()) ?? 0,
                    'context_name' => '',
                    'inetCidrRouteIfIndex' => $ifIndex,
                    'inetCidrRouteNextHopAS' => '0',
                    'inetCidrRouteType' => '4', // remote
                    'inetCidrRouteProto' => '3', // netmgm
                    'inetCidrRouteMetric1' => '1',
                    'inetCidrRouteDestType' => 'ipv6',
                    'inetCidrRouteDest' => '0000:0000:0000:0000:0000:0000:0000:0000',
                    'inetCidrRouteNextHopType' => 'ipv6',
                    'inetCidrRouteNextHop' => $hop,
                    'inetCidrRoutePfxLen' => '0',
                    'inetCidrRoutePolicy' => 'zeroDotZero.' . $ifIndex,
                ]));
            }
        }

        $routes = $routes->merge(\SnmpQuery::hideMib()->walk(['CPQSM2-MIB::cpqSm2NicConfigTable'])
        ->mapTable(function ($data, $ifName = '') use ($offset) {
            $ifIndex = $offset + $data['cpqSm2NicLocation'];

            return new Route([
                'port_id' => PortCache::getIdFromIfIndex($ifIndex, $this->getDevice()) ?? 0,
                'context_name' => '',
                'inetCidrRouteIfIndex' => $ifIndex,
                'inetCidrRouteNextHopAS' => '0',
                'inetCidrRouteType' => '4', // remote
                'inetCidrRouteProto' => '3', // netmgm
                'inetCidrRouteMetric1' => '1',
                'inetCidrRouteDestType' => 'ipv4',
                'inetCidrRouteDest' => '0.0.0.0',
                'inetCidrRouteNextHopType' => 'ipv4',
                'inetCidrRouteNextHop' => $data['cpqSm2NicGatewayIpAddress'],
                'inetCidrRoutePfxLen' => '0',
                'inetCidrRoutePolicy' => 'zeroDotZero.' . $ifIndex,
            ]);
        }));

        //emulate RouteTable
        $portIds = Port::where('device_id', $this->getDeviceId())
            ->select('ports.port_id', 'ports.ifIndex', 'ipv4_addresses.ipv4_address', 'ipv4_addresses.ipv4_prefixlen')
            ->join('ipv4_addresses', 'ports.port_id', '=', 'ipv4_addresses.port_id')->get();

        foreach ($portIds as $fromDb) {
            $ipv4 = new IPv4($fromDb['ipv4_address'] . '/' . $fromDb['ipv4_prefixlen']);
            $routes->push(new Route([
                'port_id' => $fromDb['port_id'],
                'context_name' => '',
                'inetCidrRouteIfIndex' => $fromDb['ifIndex'],
                'inetCidrRouteType' => '3',
                'inetCidrRouteProto' => '2',
                'inetCidrRouteNextHopAS' => '0',
                'inetCidrRouteMetric1' => '2',
                'inetCidrRouteDestType' => 'ipv4',
                'inetCidrRouteDest' => $ipv4->getNetworkAddress(),
                'inetCidrRouteNextHopType' => 'ipv4',
                'inetCidrRouteNextHop' => '0.0.0.0',
                'inetCidrRoutePolicy' => 'zeroDotZero.' . $fromDb['ifIndex'],
                'inetCidrRoutePfxLen' => $fromDb['ipv4_prefixlen'],
            ]));
        }

        $portIds = Port::where('device_id', $this->getDeviceId())
            ->select('ports.port_id', 'ports.ifIndex', 'ipv6_addresses.ipv6_address', 'ipv6_addresses.ipv6_prefixlen')
            ->join('ipv6_addresses', 'ports.port_id', '=', 'ipv6_addresses.port_id')->get();

        foreach ($portIds as $fromDb) {
            $ipv6 = new IPv6($fromDb['ipv6_address'] . '/' . $fromDb['ipv6_prefixlen']);
            $routes->push(new Route([
                'port_id' => $fromDb['port_id'],
                'context_name' => '',
                'inetCidrRouteIfIndex' => $fromDb['ifIndex'],
                'inetCidrRouteType' => '3',
                'inetCidrRouteProto' => '2',
                'inetCidrRouteNextHopAS' => '0',
                'inetCidrRouteMetric1' => '2',
                'inetCidrRouteDestType' => 'ipv6',
                'inetCidrRouteDest' => $fromDb['ipv6_address'],
                'inetCidrRouteNextHopType' => 'ipv6',
                'inetCidrRouteNextHop' => $ipv6->getNetworkAddress(),
                'inetCidrRoutePolicy' => 'zeroDotZero.' . $fromDb['ifIndex'],
                'inetCidrRoutePfxLen' => $fromDb['ipv6_prefixlen'],
            ]));
        }

        return $routes->filter();
    }

    private function slaac2mac(string $ipv6ll): string
    {
        $ipv6ll = explode('/', $ipv6ll)[0];
        $ll = unpack('H*hex', inet_pton($ipv6ll))['hex'];
        $mac = substr((string) $ll, 16, 1);
        $mac .= dechex(hexdec(substr((string) $ll, 17, 1)) ^ 2);
        $mac .= substr((string) $ll, 18, 4);
        $mac .= substr((string) $ll, 26, 6);

//        return wordwrap($mac, 2, ":", true);
        return $mac;
    }
}
