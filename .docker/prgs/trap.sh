#!/bin/bash

function insert_trap {
    SQL="INSERT INTO \`eventlog\` SET device_id='${DEVID}',datetime='${DT}',message='${MSG}',type='${TYPE}',reference='${REF}',username='',severity='${SEV}'"
    mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" -P "$DB_PORT" "$DB_DATABASE" -e "$SQL"
#    echo "$SQL" >> /tmp/traps_sql
}

# shellcheck source=/dev/null
function process_txt {
    source /opt/librenms/.env
    DEVID=$(mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" -P "$DB_PORT" "$DB_DATABASE" -B -N -e "SELECT device_id FROM devices where hostname='${HOSTIP}' limit 1;")
    if [ "$DEVID" -gt 0 ]; then
        echo "$DT-> $HOSTIP $MSG $SEV $DEVID" >> /tmp/trap_txt
        REF=""; TYPE="trap";
        insert_trap
        exit
    fi
}

# shellcheck source=/dev/null
# shellcheck disable=SC2046
function process_ifUpDown {
    source /opt/librenms/.env
    SQL="SELECT port_id, replace(ifName,' ',''), devices.device_id FROM devices inner JOIN ports ON devices.device_id=ports.device_id WHERE devices.hostname='${HOSTIP}' AND ports.ifIndex='${IFIND}'"
    read -r "REF" "IFNAME" "DEVID" <<< $(mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" -P "$DB_PORT" "$DB_DATABASE" -se "$SQL")
    if [ "$SEV" = "2" ]; then MSG="trap Down $IFNAME"; SEV=5; fi
    if [ "$SEV" = "1" ]; then MSG="trap Up $IFNAME"; fi
    echo "$DT-> $HOSTIP $REF $MSG $DEVID" >> /tmp/traps_ifupdown
    TYPE='interface'
    insert_trap
}

DT=$(date '+%Y-%m-%d %H:%M:%S')


read -r host
read -r ip


echo "$DT || $host || $ip" >> /var/log/trap.log

HOSTIP=$(echo "$host"|xargs|cut -d'[' -f2|cut -d']' -f1)
MSG=

while read -r oid val
do
#    oid=$(echo "$oid" | xargs);
    val=$(echo "$val" | tr -d \" | tr -d %);
    echo "$HOSTIP: $oid => $val" >> /var/log/trap.log

#if up/down index
    if [ -z "${oid##.1.3.6.1.2.1.2.2.1.1.*}" ]; then
        IFIND="$val";
    fi

#if up/down state
    if [ -z "${oid##.1.3.6.1.2.1.2.2.1.8.*}" ]; then
        SEV="$val";
        process_ifUpDown
    fi

    if [ -z "${oid##.1.3.6.1.4.1.89.2.3.1.0}" ]; then
        MSG="$val";
    fi

    if [ -z "${oid##.1.3.6.1.4.1.89.2.3.2.0}" ]; then
        SEV="$val";
        if [ "$MSG" != "" ]; then process_txt; fi
    fi
done
