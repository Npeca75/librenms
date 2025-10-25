#!/bin/bash

cd /opt/librenms
source .env

if [ -z $DB_PORT ]; then DB_PORT=3306; fi

echo --------------------------
echo override ENV.
echo USER PASS HOST PORT DBNAME
echo --------------------------

if [ ! -z $1 ] && [ ! -z $2 ] && [ ! -z $3 ] && [ ! -z $4 ] && [ ! -z $5 ]; then
    DB_USERNAME=$1; DB_PASSWORD=$2; DB_HOST=$3; DB_PORT=$4; DB_DATABASE=$5;
fi

echo $DB_USERNAME $DB_PASSWORD $DB_HOST $DB_PORT $DB_DATABASE
echo

read -p "Are you sure? " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    exit 1
fi

echo "USE ${DB_DATABASE};" > /tmp/zero.sql
echo "SET TIME_ZONE='+00:00';" >> /tmp/zero.sql
echo "ALTER TABLE \`notifications\` CHANGE \`datetime\` \`datetime\` timestamp NOT NULL DEFAULT '1970-01-02 00:00:00' ;" >> /tmp/zero.sql
echo "ALTER TABLE \`users\` CHANGE \`created_at\` \`created_at\` timestamp NOT NULL DEFAULT '1970-01-02 00:00:01' ;" >> /tmp/zero.sql
mysql -u ${DB_USERNAME} -p${DB_PASSWORD} -h ${DB_HOST} -P ${DB_PORT} < /tmp/zero.sql
