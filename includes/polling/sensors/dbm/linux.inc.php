<?php
use LibreNMS\OS;
use LibreNMS\OS\Linux;

if (empty($os)) {
    $os = OS::make($device);
}

if ($os instanceof Linux && $sensor['sensor_type'] == 'ethtool') {
    $sensor_value = $os->pollTransceiverSensors($sensor);
}

