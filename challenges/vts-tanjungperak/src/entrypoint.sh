#!/bin/bash
set -e

cron

mkdir -p /var/run/vts /var/log
echo $(date +%s) > /var/run/vts/cron_heartbeat
chmod 777 /var/run/vts
chmod 644 /var/run/vts/cron_heartbeat

chown -R www-data:www-data /var/www/html/data /var/www/html/logs
chmod -R 775 /var/www/html/data

chown root:vts_operator /opt/vts/scripts/tide_depth_sync.sh
chmod 775 /opt/vts/scripts/tide_depth_sync.sh

(
    while true; do
        if [ -n "$RSCTF_FLAG" ] && [ ! -f /flag ]; then
            echo "$RSCTF_FLAG" > /flag
        fi

        if [ -f /flag ]; then
            cp /flag /root/flag.txt
            chmod 600 /root/flag.txt
            chown root:root /root/flag.txt

            USER_HASH=$(sha256sum /flag | cut -c 1-20)
            echo "flag{user_${USER_HASH}}" > /home/vts_operator/user.txt
            chmod 600 /home/vts_operator/user.txt
            chown vts_operator:vts_operator /home/vts_operator/user.txt
        fi
        sleep 2
    done
) &

exec apache2ctl -D FOREGROUND
