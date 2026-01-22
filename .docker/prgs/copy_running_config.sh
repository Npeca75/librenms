#!/bin/bash

DEFCFG="/opt/librenms/Docker/config/"
EXTCFG="/config/"

### => .ENV file
CF=".env"; DF="/opt/librenms/.env"; echo "|file:  ${DF} -> ${CF}"
cp -f ${DF} ${EXTCFG}${CF}

### => HTTP Basic Auth
CF="nginx.htpasswd"; DF="/etc/nginx/htpasswd"; echo "|file:  ${DF} -> ${CF}"
cp -f ${DF} ${EXTCFG}${CF}

### => HTTP (nginx) config
CF="nginx.librenms.conf"; DF="/etc/nginx/conf.d/librenms.conf"; echo "|file:  ${DF} -> ${CF}"
cp -f ${DF} ${EXTCFG}${CF}

### => config.php
CF="config.php"; DF="/opt/librenms/config.php"; echo "|file:  ${DF} -> ${CF}"
cp -f ${DF} ${EXTCFG}${CF}

### => cron
CF="librenms.nonroot.cron"; DF="/etc/cron.d/librenms"; echo "|file:  ${DF} -> ${CF}"
cp -f ${DF} ${EXTCFG}${CF}

CF="rsyslog.conf"; DF="/etc/rsyslog.conf"; echo "|file:  ${DF} -> ${CF}"
cp -f ${DF} ${EXTCFG}${CF}

### => snmpd
CF="snmpd.conf"; DF="/etc/snmp/snmpd.conf"; echo "|file:  ${DF} -> ${CF}"
cp -f ${DF} ${EXTCFG}${CF}

### => default/snmptrapd
CF="default.snmptrapd"; DF="/etc/default/snmptrapd"; echo "|file:  ${DF} -> ${CF}"
cp -f ${DF} ${EXTCFG}${CF}

### => snmptrapd
CF="snmptrapd.conf"; DF="/etc/snmp/snmptrapd.conf"; echo "|file:  ${DF} -> ${CF}"
cp -f ${DF} ${EXTCFG}${CF}
