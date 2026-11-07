#!/bin/bash
set -e

# 1. Start cron daemon
cron

# 2. Initialize VTS runtime directory & heartbeat
mkdir -p /var/run/vts /var/log
echo $(date +%s) > /var/run/vts/cron_heartbeat
chmod 777 /var/run/vts
chmod 644 /var/run/vts/cron_heartbeat

# 3. Ensure permissions
chown -R www-data:www-data /var/www/html/data /var/www/html/logs
chmod -R 775 /var/www/html/data

# Ensure script is group-writable by vts_operator (CRITICAL FOR PRIVESC)
chown root:vts_operator /opt/vts/scripts/tide_depth_sync.sh
chmod 775 /opt/vts/scripts/tide_depth_sync.sh

# 3. Background Flag Synchronization (RSCTF Dynamic Flag Injection)
(
    while true; do
        # Seed /flag from environment if passed
        if [ -n "$RSCTF_FLAG" ] && [ ! -f /flag ]; then
            echo "$RSCTF_FLAG" > /flag
        fi

        if [ -f /flag ]; then
            # Root Flag: Only readable by root
            cp /flag /root/flag.txt
            chmod 600 /root/flag.txt
            chown root:root /root/flag.txt

            # User Flag: Readable by vts_operator
            USER_HASH=$(sha256sum /flag | cut -c 1-20)
            echo "flag{user_${USER_HASH}}" > /home/vts_operator/user.txt
            chmod 600 /home/vts_operator/user.txt
            chown vts_operator:vts_operator /home/vts_operator/user.txt
        fi
        sleep 2
    done
) &

echo "[*] Surabaya VTS Tanjung Perak Node starting on port 8080..."

# 5. Start Apache2 in foreground
exec apache2ctl -D FOREGROUND
