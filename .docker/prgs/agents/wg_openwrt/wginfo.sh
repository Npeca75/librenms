#!/bin/sh
wg show all dump | sort > /tmp/wg.txt
dt=$(date +%s)
out="{\"errorString\": \"\", \"error\": 0, \"version\": 1, \"data\": {"
while IFS= read -r line <&3; do
    tx=$(echo "$line"|xargs|cut -d' ' -f8)
    if [ "$tx" != "" ]; then
        rx=$(echo "$line" | xargs | cut -d' ' -f7)
        ts=$(echo "$line" | xargs | cut -d' ' -f6)
        peer=$(echo "$line" | xargs | cut -d' ' -f2)
        wgint=$(echo "$line" | xargs | cut -d' ' -f1)
        index=$(uci show network | grep "$peer" | cut -d'[' -f2 | cut -d']' -f1)
        name=$(uci get network.@wireguard_${wgint}[${index}].description)
        min=$(( ($dt - $ts) / 60 ))
        if [ "$wgint" != "$wgold" ]; then
            [ -z "$wgold" ] && SEP="" || SEP="},"
            wgold="$wgint"
            flcl=0
            out="${out}$SEP\"${wgint}\": {"
        fi
        [ "$flcl" = "1" ] && out="${out}, "
        out="${out}\"${name}\": {\"minutes_since_last_handshake\": ${min}, \"bytes_rcvd\": ${rx}, \"bytes_sent\": ${tx}}"
        flcl=1
    fi
done 3< "/tmp/wg.txt"
[ -z "$wgint" ] && out="${out}}}" || out="${out}}}}"
echo "$out"
