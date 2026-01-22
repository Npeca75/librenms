FROM npeca75/www:latest

# ==> Download packages
ARG DEBIAN_FRONTEND=noninteractive
RUN apt-get -y update && apt-get install --no-install-recommends -y \
 acl cron fping git graphviz imagemagick ipmitool mtr-tiny mailutils memcached nmap snmp snmpd snmptrapd sudo rsyslog rrdtool rrdcached whois

# COPY . /tmp/

# ===> SYSTEM
RUN mkdir -p /config \
 && mkdir -p /etc/bash_completion.d \
 && mkdir -p /root/.ssh \
 && git clone --branch priv https://github.com/Npeca75/librenms.git /tmp/librenms \
 && cd /tmp/librenms \
 && rm -rf .git .github html/plugins/* doc/* tests/* \
 && mkdir -p /tmp/librenms/rrd \
 && mkdir -p /tmp/librenms/logs \
 && mkdir -p /tmp/librenms/.config/pip \
 && printf "[global]\nbreak-system-packages = true" > /tmp/librenms/.config/pip/pip.conf \
 && printf "export PATH=\"$HOME/.local/bin:$PATH\"" > /tmp/librenms/.bashrc \
 && printf "export PATH=\"$HOME/.local/bin:$PATH\"" > /tmp/librenms/.bash_profile \
 && useradd librenms -d /opt/librenms -M -s /bin/bash \
 && usermod -a -G librenms www-data \
 && chown -R librenms:librenms /tmp/librenms \
 && chmod 771 /tmp/librenms \
 && mv -f /tmp/librenms /opt/ \
 && cd /opt/librenms \
 && sudo -u librenms ./scripts/composer_wrapper.php install --no-dev \
 && sudo -u librenms pip3 install --user -r requirements.txt --break-system-packages \
 && setfacl -d -m g::rwx /opt/librenms/rrd /opt/librenms/logs /opt/librenms/bootstrap/cache/ /opt/librenms/storage/ \
 && setfacl -R -m g::rwx /opt/librenms/rrd /opt/librenms/logs /opt/librenms/bootstrap/cache/ /opt/librenms/storage/ \
 && apt-get -y purge git && apt-get clean && apt-get autoclean && apt-get -y autoremove --purge && update-locale && rm -rf /tmp/*

# ===> ENV Block
ENV APP_URL=/ \
 DB_HOST=0.0.2.18 \
 DB_PORT=3306 \
 DB_DATABASE=dockernms \
 DB_USERNAME=dockernms \
 DB_PASSWORD=dockernms \
 LIBRENMS_USER=librenms \
 APP_KEY=base64:6CaJpkZHwn6asygA4XYFUPVx2WceJilUErR+EnWCnnw= \
 NODE_ID=61ec37deb7b95 \
 DEF_IP=169.254.255.254 \
 HTPASSWD=Monitoring:{SHA}y9YdYTU/SdxKN53tgn311TxPJ28= \
 NMS_ROLE=poller

VOLUME /config
MAINTAINER Peca Nesovanovic <peca.nesovanovic@sattrakt.com>
 EXPOSE 162/tcp 162/udp 514/tcp 514/udp 18000/tcp
ENTRYPOINT ["tini", "--"]
CMD ["/opt/librenms/.docker/startup.sh"]
