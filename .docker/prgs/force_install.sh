#!/bin/bash

cd /opt/librenms || exit

source .env
if [ -z $DB_PORT ]; then DB_PORT=3306; fi

echo --------------------------
echo override ENV
echo USER PASS HOST PORT DBNAME
echo --------------------------

if [ -n "$1" ] && [ -n "$2" ] && [ -n "$3" ] && [ -n "$4" ] && [ -n "$5" ]; then
    DB_USERNAME=$1; DB_PASSWORD=$2; DB_HOST=$3; DB_PORT=$4; DB_DATABASE=$5;
fi

echo "$DB_USERNAME" "$DB_PASSWORD" "$DB_HOST" "$DB_PORT" "$DB_DATABASE"
echo

read -p "Are you sure? " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    exit 1
fi


echo "USE ${DB_DATABASE};" > /tmp/zero.sql
echo "SET FOREIGN_KEY_CHECKS = 0;" >> /tmp/zero.sql

OUT=$(mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" -h "${DB_HOST}" -P "${DB_PORT}" -Bse "SELECT table_name FROM information_schema.tables WHERE table_schema = \"${DB_DATABASE}\";"|xargs)

for t in $OUT; do
    echo "DROP TABLE $t;" >> /tmp/zero.sql
    echo "$t"
done

echo "SET FOREIGN_KEY_CHECKS = 1;" >> /tmp/zero.sql

mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" -h "${DB_HOST}" -P "${DB_PORT}" < /tmp/zero.sql

echo "APP_KEY=" > .env
echo "INSTALL=true" >> .env
lnms key:generate
