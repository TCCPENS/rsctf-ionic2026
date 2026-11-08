FROM debian:bookworm-slim

RUN apt-get update && apt-get install -y --no-install-recommends \
    apache2 \
    php \
    php-sqlite3 \
    cron \
    sudo \
    procps \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:8080>/' /etc/apache2/sites-available/000-default.conf

RUN useradd -m -s /bin/bash vts_operator \
    && echo "vts_operator:JangkarPerak2026!Madura" | chpasswd

RUN echo "www-data ALL=(vts_operator) NOPASSWD: ALL" > /etc/sudoers.d/vts-operator \
    && chmod 440 /etc/sudoers.d/vts-operator

RUN mkdir -p /opt/vts/conf /opt/vts/scripts /opt/vts/data /var/run/vts

COPY conf/ais_station.conf /opt/vts/conf/ais_station.conf
RUN chmod 644 /opt/vts/conf/ais_station.conf

COPY scripts/tide_depth_sync.sh /opt/vts/scripts/tide_depth_sync.sh
RUN chown root:vts_operator /opt/vts/scripts/tide_depth_sync.sh \
    && chmod 775 /opt/vts/scripts/tide_depth_sync.sh

RUN echo "* * * * * root /opt/vts/scripts/tide_depth_sync.sh >/dev/null 2>&1" > /etc/cron.d/vts-depth-sync \
    && chmod 644 /etc/cron.d/vts-depth-sync

RUN rm -rf /var/www/html/*
COPY html/ /var/www/html/
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/data

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh /opt/vts/scripts/tide_depth_sync.sh

EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
