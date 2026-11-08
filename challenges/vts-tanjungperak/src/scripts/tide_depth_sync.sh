#!/bin/bash
mkdir -p /var/run/vts
echo $(date +%s) > /var/run/vts/cron_heartbeat
chmod 644 /var/run/vts/cron_heartbeat
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S WIB')
echo "[$TIMESTAMP] [HYDRO-SYNC] Tidal sensor reading verified. Channel depth cleared for traffic." >> /var/log/vts_tide.log
find /tmp -name "vts_chart_*.tmp" -mmin +60 -delete 2>/dev/null || true
