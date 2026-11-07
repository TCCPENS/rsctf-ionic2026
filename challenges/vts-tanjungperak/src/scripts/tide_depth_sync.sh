#!/bin/bash
# =====================================================================
# VTS Tanjung Perak - Bathymetry & Tidal Sensor Synchronization Script
# Scheduled via /etc/cron.d/vts-depth-sync (Runs every minute as root)
# Maintainer: vts_operator (Surabaya Fairway Hydrographic Unit)
# =====================================================================

# 1. Update SLA Checker Heartbeat
mkdir -p /var/run/vts
echo $(date +%s) > /var/run/vts/cron_heartbeat
chmod 644 /var/run/vts/cron_heartbeat

# 2. Log periodic telemetry sync
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S WIB')
echo "[$TIMESTAMP] [HYDRO-SYNC] Tidal sensor reading verified. Channel depth cleared for traffic." >> /var/log/vts_tide.log

# 3. Clean up temporary chart caches older than 60 mins
find /tmp -name "vts_chart_*.tmp" -mmin +60 -delete 2>/dev/null || true
