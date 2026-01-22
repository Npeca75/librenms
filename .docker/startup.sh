#!/bin/bash

DEFCFG="/opt/librenms/.docker/config/"
EXTCFG="/config/"
PHP=$(/usr/bin/php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")

### => timezone
cp /usr/share/zoneinfo/Europe/Belgrade /etc/localtime

### => setup php
sed -i "s/;date.timezone =.*/date.timezone = Europe\/Belgrade/" /etc/php/"${PHP}"/fpm/php.ini
sed -i "s/;date.timezone =.*/date.timezone = Europe\/Belgrade/" /etc/php/"${PHP}"/cli/php.ini
mv /etc/php/"${PHP}"/fpm/pool.d/www.conf /etc/php/"${PHP}"/fpm/pool.d/librenms.conf
sed -i "s/user = .*/user = librenms/" /etc/php/"${PHP}"/fpm/pool.d/librenms.conf
sed -i "s/group = .*/group = librenms/" /etc/php/"${PHP}"/fpm/pool.d/librenms.conf
sed -i "s|listen = .*|listen = /run/php-fpm-librenms.sock|" /etc/php/"${PHP}"/fpm/pool.d/librenms.conf
cp /etc/init.d/php"${PHP}"-fpm /etc/init.d/php-fpm

### => stupid workarrounds for missing folder
mkdir -p /run/php
mkdir -p /var/log/snmptrap
mkdir -p /config

### => services
SFILES=$(ls -t /opt/librenms/.docker/services)
for CF in ${SFILES}; do
    cp -f /opt/librenms/.docker/services/"${CF}" /etc/init.d/"${CF}"
    echo "${CF} INIT created"
done

echo
echo "=== preparing config ==="

### => .ENV file
CF=".env"; DF="/opt/librenms/.env"; echo "|file:  ${DF} || ${CF}"
if test -f "${EXTCFG}${CF}"; then
    cp -f ${EXTCFG}${CF} ${DF}; echo "using EXT"
else
    cat <<EOF > ${DF}
#
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}
#
DB_TEST_DATABASE="testnms"
DB_TEST_USERNAME="testnms"
DB_TEST_PASSWORD="testnms"
DB_TEST_HOST=0.0.2.19
#
LIBRENMS_USER=librenms
APP_URL=/
APP_KEY=${APP_KEY}
NODE_ID=${NODE_ID}
EOF
    cp -f ${DF} ${EXTCFG}${CF}; echo "* copy ${DF} to EXT ${CF}"
fi
chown librenms:librenms ${DF}


### => config.php
CF="config.php"; DF="/opt/librenms/config.php"; echo -e "\nfile:  ${DF} || ${CF}"
if test -f "${EXTCFG}${CF}"; then
    cp -f ${EXTCFG}${CF} ${DF}; echo "using EXT"
else
    cat <<EOF > ${DF}
<?php
#
\$config['uptime_warning'] = 3600;
#
\$config['update'] = 0;
\$config['enable_sysylog'] = 1;
#
\$config['os']['routeros']['oids']['no_bulk'][] = 'LLDP-MIB::lldpRemEntry';
\$config['os']['routeros']['oids']['no_bulk'][] = 'lldpRemEntry';
\$config['os']['vmware-esxi']['good_if'][] = 'vmk';
\$config['os']['vmware-esxi']['good_if'][] = 'vswitch';
\$config['os']['linux']['bad_if'][] = 'veth';
\$config['os']['routeros']['oids']['no_bulk'][] = 'LLDP-MIB::lldpRemEntry';
\$config['os']['routeros']['oids']['no_bulk'][] = 'lldpRemEntry';
\$config['os']['vmware-esxi']['good_if'][] = 'vmk';
\$config['os']['vmware-esxi']['good_if'][] = 'vswitch';
\$config['os']['linux']['oids']['no_bulk'][] = 'NET-SNMP-EXTEND-MIB::nsExtendStatus';
\$config['os']['linux']['oids']['no_bulk'][] = 'nsExtendStatus';
\$config['os']['linux']['bad_if'][] = 'veth';

\$config['enable_billing'] = 0;
\$config['show_services'] = 1;

\$config['autodiscovery']['xdp'] = false;
\$config['network_map_items'] = array('xdp');
\$config['discovery_by_ip'] = true;
\$config['force_ip_to_sysname'] = true;
\$config['force_hostname_to_sysname'] = true;
EOF
    cp -f ${DF} ${EXTCFG}${CF}; echo "* copy ${DF} to EXT ${CF}"
fi
chown librenms:librenms ${DF}

### => git-credentials
CF="git-credentials"; DF="/opt/librenms/.git-credentials"; echo -e "\nfile:  ${DF} || ${CF}"
if test -f "${EXTCFG}${CF}"; then
    cp -f ${EXTCFG}${CF} ${DF}; echo "using EXT"
else
    echo "" > ${DF}; echo "using BLANK"
    cp -f ${DF} ${EXTCFG}${CF}; echo "* copy ${DF} to EXT ${CF}"
fi
chown librenms:librenms ${DF}

### => cron
CF="librenms.cron"; DF="/etc/cron.d/librenms"; echo -e "\nfile:  ${DF} || ${CF}"
#if test -f "${EXTCFG}${CF}"; then
#    cp -f ${EXTCFG}${CF} ${DF}; echo "using EXT"
#else
    cp ${DEFCFG}${CF} ${DF}; echo "using DEF"
    cp -f ${DF} ${EXTCFG}${CF}; echo "* copy ${DF} to EXT ${CF}"
#fi

### => HTTP Basic Auth
CF="nginx.htpasswd"; DF="/etc/nginx/htpasswd"; echo -e "\nfile:  ${DF} || ${CF}"
if test -f "${EXTCFG}${CF}"; then
    cp -f ${EXTCFG}${CF} ${DF}; echo "using EXT"
else
    echo "${HTPASSWD}" > ${DF}; echo "using DEF env"
    cp -f ${DF} ${EXTCFG}${CF}; echo "* copy ${DF} to EXT ${CF}"
fi
chown root:root ${DF}

### => HTTP (nginx) config
CF="librenms.vhost"; DF="/etc/nginx/sites-enabled/librenms.vhost"; echo -e "\nfile:  ${DF} || ${CF}"
if test -f "${EXTCFG}${CF}"; then
    cp -f ${EXTCFG}${CF} ${DF}; echo "using EXT"
else
    cp -f ${DEFCFG}${CF} ${DF}; echo "using DEF"
    sed -i "s/server_name #/server_name ${DEF_IP}/g" ${DF};
    cp -f ${DF} ${EXTCFG}${CF}; echo "* copy ${DF} to EXT ${CF}"
fi
chown root:root ${DF}

### => rsyslog
CF="rsyslog.conf"; DF="/etc/rsyslog.conf"; echo -e "\nfile:  ${DF} || ${CF}"
# if test -f "${EXTCFG}${CF}"; then
#    cp -f ${EXTCFG}${CF} ${DF}; echo "using EXT"
#else
    cp -f ${DEFCFG}${CF} ${DF}; echo "using DEF"
    cp -f ${DF} ${EXTCFG}${CF}; echo "* copy ${DF} to EXT ${CF}"
#fi
chown root:root ${DF}

### => snmpd
CF="snmpd.conf"; DF="/etc/snmp/snmpd.conf"; echo -e "\nfile:  ${DF} || ${CF}"
if test -f "${EXTCFG}${CF}"; then
    cp -f ${EXTCFG}${CF} ${DF}; echo "using EXT"
else
    cp -f ${DEFCFG}${CF} ${DF}; echo "using DEF"
    cp -f ${DF} ${EXTCFG}${CF}; echo "* copy ${DF} to EXT ${CF}"
fi
chown root:root ${DF}

### => snmptrapd
DF="/etc/default/snmptrapd";
echo "TRAPDRUN=yes" > $DF
echo "TRAPDOPTS='-t -n -On -Ln -C -c /etc/snmp/snmptrapd.conf'" >> $DF
chown root:root ${DF}

CF="snmptrapd.conf"; DF="/etc/snmp/snmptrapd.conf"; echo -e "\nfile:  ${DF} || ${CF}"
#if test -f "${EXTCFG}${CF}"; then
#    cp -f ${EXTCFG}${CF} ${DF}; echo "using EXT"
#else
    cp -f ${DEFCFG}${CF} ${DF}; echo "using DEF"
    cp -f ${DF} ${EXTCFG}${CF}; echo "* copy ${DF} to EXT ${CF}"
#fi
chown root:root ${DF}

echo "=== config end ==="

### => local path
echo "export PATH=/opt/librenms/.local/bin:\$PATH" > /opt/librenms/.bashrc
echo "export PATH=/opt/librenms/.local/bin:\$PATH" > /opt/librenms/.bash_profile

### => enabled services
[ "${NMS_ROLE}" = 'poller' ] && echo "cron snmptrapd rsyslog" > /tmp/services
[ "${NMS_ROLE}" = 'normal' ] && echo "cron snmptrapd rsyslog nginx php-fpm" > /tmp/services
[ "${NMS_ROLE}" = 'devel' ] && echo "cron snmptrapd rsyslog nginx php-fpm snmpd ssh" > /tmp/services

ln -s /opt/librenms/lnms /usr/bin/lnms
cp /opt/librenms/misc/lnms-completion.bash /etc/bash_completion.d/
cp /opt/librenms/misc/librenms.logrotate /etc/logrotate.d/librenms

AK="ssh-rsa AAAAB3NzaC1yc2EAAAABJQAAAgEAr8XtdPI8Fhxpkgc3Mbcc8PTA0r5Hpd5Aaob2IBZYs3vFzMSFZa+99ohpVbfkDTRL9ZPVd0B/bOxg+++8DiVQN6Ql/tdBSbhX0Vei6Dvc+IUqHxZ0qEjvZpsJAuu6riiX+aA2tLxqxyPD1zBzkZ/Z11CpZYzsgTzRFUQGlhKqMXIzHexcDo++/jR7WhVeQ90lMrEa0S4w6Mh9OwKpCuCSqE85CnigE2/bXaN/pQ9DLIj8091MWuri9Q3Ei4LXcXFP6inYHAIGl+KRpeowlFJoU8DxGwv0a5Mbfxiy61HmkMmrRD3PflYtMUV9W7J+pjPO5CdU/4mFkvZLMlopMC39hI4pF/7ExFYOI1W+vSXshmpheaUz1FCHwI2OPMAxuQxiYFG+w8IHmII7v01W8r2e7FqBuD31RGD2l7VV+sDrnXNr+9uEjbqLrKoztZYo0d/7CyN7LhTDG3pgp4Jz+5ojwVP86S5u7ZJn0qKsPnJhB809auxsGVXn25Li8+vEYaPu52CuKZAZmz2dy7OttbolAvFrWfGLLmb6HuiFIKAeU9jPVFYcdT4MC9dPHSBeaV1YU/82qFu3PgkBlXj4XxB8/SeWahfUDH4NMr9XbFrnQz/Dj3Asv5yvuN4d2wUZ6+0U3u5CR3trmyKT9vLh+gGcsowksIJsAz3LE5vNJzpuWPU= rsa-key-20220806"

mkdir -p /root/.ssh
chmod 0700 /root/.ssh
echo "$AK" > /root/.ssh/authorized_keys
chmod 0600 /root/.ssh/authorized_keys
chown -R root:root /root/.ssh

setcap cap_net_raw+ep /usr/bin/fping6
setcap cap_net_raw+ep /usr/bin/fping

### => force DB migrate
cd /opt/librenms || exit
sudo -u librenms lnms migrate --force -n

### => show enabled services (docker run -it ...)
xargs < /tmp/services
sleep 5

### => loop
while :
do
    ps -A > /tmp/ps
    SRV=$(xargs < /tmp/services)
    for s in $SRV; do
        if ! grep -q "${s}" /tmp/ps; then tini -s service "${s}" restart; fi
    done
sleep 10
done
