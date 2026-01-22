FROM npeca75/php:84

# ==> Download packages
ARG DEBIAN_FRONTEND=noninteractive
RUN apt-get -y update \
 && apt-get install --no-install-recommends -y acl curl fping graphviz imagemagick mariadb-client mtr-tiny nginx-full nmap sudo snmp snmpd snmptrapd rsyslog tini \
 rrdtool rrdcached memcached whois unzip dialog mc iputils-ping net-tools cron locales mailutils git ipmitool \
 python3-pip python3-memcache python3.11-venv \
 && curl -sSL https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer \
 && apt-get clean && apt-get autoclean && apt-get autoremove --purge && update-locale

COPY . /tmp/

# ===> SYSTEM
RUN mkdir -p /tmp/librenms \
 && mkdir -p /config \
 && mkdir -p /etc/bash_completion.d \
 && mkdir -p /root/.ssh \
 && cd /tmp/librenms \
 && git clone --depth=1 https://github.com/librenms/librenms.git . \
 && echo "----- patch 0 -----" \
 && git apply /tmp/0.patch \
 && echo "----- patch 1 -----" \
 && git apply /tmp/1.patch \
 && echo "----- patch end -----" \
 && mv -f /tmp/.docker /tmp/librenms/.docker \
 && rm -rf .git .github html/plugins/Test html/plugins/Weathermap doc/* tests/* /tmp/*.patch \
 && mv -f /tmp/librenms /opt/ \
 && mkdir -p /opt/librenms/.config/pip \
 && printf "[global]\nbreak-system-packages = true" > /opt/librenms/.config/pip/pip.conf \
 && printf "export PATH=\"$HOME/.local/bin:$PATH\"" > /opt/librenms/.bashrc \
 && printf "export PATH=\"$HOME/.local/bin:$PATH\"" > /opt/librenms/.bash_profile \
 && useradd librenms -d /opt/librenms -M -s /bin/bash \
 && usermod -a -G librenms www-data \
 && chown -R librenms:librenms /opt/librenms \
 && chmod 771 /opt/librenms \
 && sudo -u librenms ./scripts/composer_wrapper.php install --no-dev \
 && sudo -u librenms pip3 install --user -r requirements.txt --break-system-packages \
 && setfacl -d -m g::rwx /opt/librenms/rrd /opt/librenms/logs /opt/librenms/bootstrap/cache/ /opt/librenms/storage/ \
 && setfacl -R -m g::rwx /opt/librenms/rrd /opt/librenms/logs /opt/librenms/bootstrap/cache/ /opt/librenms/storage/

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
