# Install LibreNMS

## Prepare Linux Server

You should have an installed Linux server running one of the supported OS.
Make sure you select your server's OS in the tabbed options below.
Choice of web server is your preference, NGINX is recommended.

Connect to the server command line and follow the instructions below.
!!! note

    These instructions assume you are the **root** user.  
    If you are not, prepend `sudo` to the shell commands (the ones that aren't
    at `mysql>` prompts) or temporarily become a user with root
    privileges with `sudo -s` or `sudo -i`.

**Please note the minimum supported PHP version is @= php.version_min =@**

## Install Required Packages

=== "Ubuntu 24.04"
    === "NGINX"
        ```
        apt install acl curl fping git graphviz imagemagick mariadb-client mariadb-server mtr-tiny nginx-full nmap php-cli php-curl php-fpm php-gd php-gmp php-json php-mbstring php-mysql php-snmp php-xml php-zip rrdtool snmp snmpd unzip python3-command-runner python3-pymysql python3-dotenv python3-redis python3-setuptools python3-psutil python3-systemd python3-pip whois traceroute
        ```

=== "Ubuntu 22.04"
    === "NGINX"
        ```
        apt install software-properties-common
        LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php -y
        apt update
        apt install acl curl fping git graphviz imagemagick mariadb-client mariadb-server mtr-tiny nginx-full nmap php8.3-cli php8.3-curl php8.3-fpm php8.3-gd php8.3-gmp php8.3-mbstring php8.3-mysql php8.3-snmp php8.3-xml php8.3-zip rrdtool snmp snmpd unzip python3-pymysql python3-dotenv python3-redis python3-setuptools python3-psutil python3-systemd python3-pip whois traceroute
        ```

    === "Apache"
        ```
        apt install software-properties-common
        add-apt-repository universe
        LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php
        apt update
        apt install acl curl apache2 fping git graphviz imagemagick libapache2-mod-fcgid mariadb-client mariadb-server mtr-tiny nmap php-cli php-curl php-fpm php-gd php-gmp php-json php-mbstring php-mysql php-snmp php-xml php-zip rrdtool snmp snmpd whois python3-pymysql python3-dotenv python3-redis python3-setuptools python3-systemd python3-pip unzip traceroute
        ```

=== "CentOS 8"
    === "NGINX"
        ```
        dnf -y install epel-release
        dnf -y install dnf-utils http://rpms.remirepo.net/enterprise/remi-release-8.rpm
        dnf module reset php
        dnf module enable php:8.2
        dnf install bash-completion cronie fping git ImageMagick mariadb-server mtr net-snmp net-snmp-utils nginx nmap php-fpm php-cli php-common php-curl php-gd php-gmp php-json php-mbstring php-process php-snmp php-xml php-zip php-mysqlnd python3 python3-PyMySQL python3-redis python3-memcached python3-pip python3-systemd rrdtool unzip
        ```

    === "Apache"
        ```
        dnf -y install epel-release
        dnf -y install dnf-utils http://rpms.remirepo.net/enterprise/remi-release-8.rpm
        dnf module reset php
        dnf module enable php:8.2
        dnf install bash-completion cronie fping gcc git httpd ImageMagick mariadb-server mtr net-snmp net-snmp-utils nmap php-fpm php-cli php-common php-curl php-gd php-gmp php-json php-mbstring php-process php-snmp php-xml php-zip php-mysqlnd python3 python3-devel python3-PyMySQL python3-redis python3-memcached python3-pip python3-systemd rrdtool unzip 
        ```

=== "Debian 12"
    === "NGINX"
        ```
        apt install lsb-release ca-certificates wget acl curl fping git graphviz imagemagick mariadb-client mariadb-server mtr-tiny nginx-full nmap php-cli php-curl php-fpm php-gd php-gmp php-mbstring php-mysql php-snmp php-xml php-zip python3-dotenv python3-pymysql python3-redis python3-setuptools python3-systemd python3-pip rrdtool snmp snmpd unzip whois
        ```

=== "Debian 13"
    === "NGINX"
        ```
        apt install lsb-release ca-certificates wget acl curl fping git graphviz imagemagick mariadb-client mariadb-server mtr-tiny nginx-full nmap php-cli php-curl php-fpm php-gd php-gmp php-mbstring php-mysql php-snmp php-xml php-zip python3-command-runner python3-dotenv python3-pymysql python3-redis python3-setuptools python3-systemd python3-pip rrdtool snmp snmpd unzip whois
        ```

## Add librenms user

```
useradd librenms -d /opt/librenms -M -r -s "$(which bash)"
```

## Download LibreNMS

```
cd /opt
git clone https://github.com/librenms/librenms.git
```

## Set permissions

```
chown -R librenms:librenms /opt/librenms
chmod 771 /opt/librenms
setfacl -d -m g::rwx /opt/librenms/rrd /opt/librenms/logs /opt/librenms/bootstrap/cache/ /opt/librenms/storage/
setfacl -R -m g::rwx /opt/librenms/rrd /opt/librenms/logs /opt/librenms/bootstrap/cache/ /opt/librenms/storage/
```

## Install PHP dependencies

Change to the LibreNMS user:
```
su - librenms
```

Then run the composer wrapper script and exit back to the root user:
```
./scripts/composer_wrapper.php install --no-dev
exit
```

!!! note
    Sometimes when there is a proxy used to gain internet access, the above script may fail.
    The workaround is to install the `composer` package manually. For a global installation:
    ```
    wget https://getcomposer.org/composer-stable.phar
    mv composer-stable.phar /usr/bin/composer
    chmod +x /usr/bin/composer
    ```

## Set timezone

See <https://php.net/manual/en/timezones.php> for a list of supported
timezones.  Valid examples are: "America/New_York", "Australia/Brisbane", "Etc/UTC".
Ensure date.timezone is set in php.ini to your preferred time zone.

=== "Ubuntu 24.04"
    ```bash
    vi /etc/php/8.3/fpm/php.ini
    vi /etc/php/8.3/cli/php.ini
    ```

=== "Ubuntu 22.04"
    ```bash
    vi /etc/php/8.3/fpm/php.ini
    vi /etc/php/8.3/cli/php.ini
    ```

=== "CentOS 8"
    ```
    vi /etc/php.ini
    ```

=== "Debian 12"
    ```bash
    vi /etc/php/8.2/fpm/php.ini
    vi /etc/php/8.2/cli/php.ini
    ```

=== "Debian 13"
    ```bash
    vi /etc/php/8.4/fpm/php.ini
    vi /etc/php/8.4/cli/php.ini
    ```

Remember to set the system timezone as well.

```
timedatectl set-timezone Etc/UTC
```


## Configure MariaDB

=== "Ubuntu 24.04"
    ```
    vi /etc/mysql/mariadb.conf.d/50-server.cnf
    ```

=== "Ubuntu 22.04"
    ```
    vi /etc/mysql/mariadb.conf.d/50-server.cnf
    ```

=== "CentOS 8"
    ```
    vi /etc/my.cnf.d/mariadb-server.cnf
    ```

=== "Debian 12"
    ```
    vi /etc/mysql/mariadb.conf.d/50-server.cnf
    ```

=== "Debian 13"
    ```
    vi /etc/mysql/mariadb.conf.d/50-server.cnf
    ```

Within the `[mysqld]` section add:

```
innodb_file_per_table=1
lower_case_table_names=0
```

Then restart MariaDB

```
systemctl enable mariadb
systemctl restart mariadb
```
Start MariaDB client

```
mysql -u root
```

!!! warning
    Change the 'password' below to something secure.

```sql
CREATE DATABASE librenms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'librenms'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON librenms.* TO 'librenms'@'localhost';
exit
```

## Configure PHP-FPM

=== "Ubuntu 24.04"
    ```bash
    cp /etc/php/8.3/fpm/pool.d/www.conf /etc/php/8.3/fpm/pool.d/librenms.conf
    vi /etc/php/8.3/fpm/pool.d/librenms.conf
    ```

=== "Ubuntu 22.04"
    ```bash
    cp /etc/php/8.3/fpm/pool.d/www.conf /etc/php/8.3/fpm/pool.d/librenms.conf
    vi /etc/php/8.3/fpm/pool.d/librenms.conf
    ```

=== "CentOS 8"
    ```bash
    cp /etc/php-fpm.d/www.conf /etc/php-fpm.d/librenms.conf
    vi /etc/php-fpm.d/librenms.conf
    ```

=== "Debian 12"
    ```bash
    cp /etc/php/8.2/fpm/pool.d/www.conf /etc/php/8.2/fpm/pool.d/librenms.conf
    vi /etc/php/8.2/fpm/pool.d/librenms.conf
    ```

=== "Debian 13"
    ```bash
    cp /etc/php/8.4/fpm/pool.d/www.conf /etc/php/8.4/fpm/pool.d/librenms.conf
    vi /etc/php/8.4/fpm/pool.d/librenms.conf
    ```

Change `[www]` to `[librenms]`:
```
[librenms]
```

Change `user` and `group` to "librenms":
```
user = librenms
group = librenms
```

Change `listen` to a unique path that must match your webserver's config (`fastcgi_pass` for NGINX and `SetHandler` for Apache) :
```
listen = /run/php-fpm-librenms.sock
```

If there are no other PHP web applications on this server, you may remove www.conf to save some resources.
Feel free to tune the performance settings in librenms.conf to meet your needs.

## Configure Web Server

=== "Ubuntu 24.04"
    === "NGINX"
        ```bash
        vi /etc/nginx/conf.d/librenms.conf
        ```

        Add the following config, edit `server_name` as required:

        ```nginx
        server {
         listen      80;
         server_name librenms.example.com;
         root        /opt/librenms/html;
         index       index.php;

         charset utf-8;
         gzip on;
         gzip_types text/css application/javascript text/javascript application/x-javascript image/svg+xml text/plain text/xsd text/xsl text/xml image/x-icon;
         location / {
          try_files $uri $uri/ /index.php?$query_string;
         }
         location ~ [^/]\.php(/|$) {
          fastcgi_pass unix:/run/php-fpm-librenms.sock;
          fastcgi_split_path_info ^(.+\.php)(/.+)$;
          include fastcgi.conf;
         }
         location ~ /\.(?!well-known).* {
          deny all;
         }
        }
        ```

        ```bash
        rm /etc/nginx/sites-enabled/default /etc/nginx/sites-available/default
        systemctl restart nginx
        systemctl restart php8.3-fpm
        ```

=== "Ubuntu 22.04"
    === "NGINX"
        ```bash
        vi /etc/nginx/conf.d/librenms.conf
        ```

        Add the following config, edit `server_name` as required:

        ```nginx
        server {
         listen      80;
         server_name librenms.example.com;
         root        /opt/librenms/html;
         index       index.php;

         charset utf-8;
         gzip on;
         gzip_types text/css application/javascript text/javascript application/x-javascript image/svg+xml text/plain text/xsd text/xsl text/xml image/x-icon;
         location / {
          try_files $uri $uri/ /index.php?$query_string;
         }
         location ~ [^/]\.php(/|$) {
          fastcgi_pass unix:/run/php-fpm-librenms.sock;
          fastcgi_split_path_info ^(.+\.php)(/.+)$;
          include fastcgi.conf;
         }
         location ~ /\.(?!well-known).* {
          deny all;
         }
        }
        ```

        ```bash
        rm /etc/nginx/sites-enabled/default
        systemctl restart nginx
        systemctl restart php8.3-fpm
        ```

=== "CentOS 8"
    === "NGINX"
        ```
        vi /etc/nginx/conf.d/librenms.conf
        ```

        Add the following config, edit `server_name` as required:

        ```nginx
        server {
         listen      80;
         server_name librenms.example.com;
         root        /opt/librenms/html;
         index       index.php;

         charset utf-8;
         gzip on;
         gzip_types text/css application/javascript text/javascript application/x-javascript image/svg+xml text/plain text/xsd text/xsl text/xml image/x-icon;
         location / {
          try_files $uri $uri/ /index.php?$query_string;
         }
         location ~ [^/]\.php(/|$) {
          fastcgi_pass unix:/run/php-fpm-librenms.sock;
          fastcgi_split_path_info ^(.+\.php)(/.+)$;
          include fastcgi.conf;
         }
         location ~ /\.(?!well-known).* {
          deny all;
         }
        }
        ```

        > NOTE: If this is the only site you are hosting on this server (it
        > should be :)) then you will need to disable the default site.

        Delete the `server` section from `/etc/nginx/nginx.conf`

        ```
        systemctl enable --now nginx
        systemctl enable --now php-fpm
        ```

    === "Apache"
        Create the librenms.conf:

        ```
        vi /etc/httpd/conf.d/librenms.conf
        ```

        Add the following config, edit `ServerName` as required:

        ```apache
        <VirtualHost *:80>
          DocumentRoot /opt/librenms/html/
          ServerName  librenms.example.com

          AllowEncodedSlashes NoDecode
          <Directory "/opt/librenms/html/">
            Require all granted
            AllowOverride All
            Options FollowSymLinks MultiViews
          </Directory>

          # Enable http authorization headers
          <IfModule setenvif_module>
            SetEnvIfNoCase ^Authorization$ "(.+)" HTTP_AUTHORIZATION=$1
          </IfModule>

          <FilesMatch ".+\.php$">
            SetHandler "proxy:unix:/run/php-fpm-librenms.sock|fcgi://localhost"
          </FilesMatch>
        </VirtualHost>
        ```

        > NOTE: If this is the only site you are hosting on this server (it
        > should be :)) then you will need to disable the default site. `rm -f /etc/httpd/conf.d/welcome.conf`

        ```
        systemctl enable --now httpd
        systemctl enable --now php-fpm
        ```

=== "Debian 12"
    === "NGINX"
        ```bash
        vi /etc/nginx/sites-enabled/librenms.vhost
        ```

        Add the following config, edit `server_name` as required:

        ```nginx
        server {
         listen      80;
         server_name librenms.example.com;
         root        /opt/librenms/html;
         index       index.php;

         charset utf-8;
         gzip on;
         gzip_types text/css application/javascript text/javascript application/x-javascript image/svg+xml text/plain text/xsd text/xsl text/xml image/x-icon;
         location / {
          try_files $uri $uri/ /index.php?$query_string;
         }
         location ~ [^/]\.php(/|$) {
          fastcgi_pass unix:/run/php-fpm-librenms.sock;
          fastcgi_split_path_info ^(.+\.php)(/.+)$;
          include fastcgi.conf;
         }
         location ~ /\.(?!well-known).* {
          deny all;
         }
        }
        ```

        ```bash
        rm /etc/nginx/sites-enabled/default
        systemctl reload nginx
        systemctl restart php8.2-fpm
        ```

=== "Debian 13"
    === "NGINX"
        ```bash
        vi /etc/nginx/sites-enabled/librenms.vhost
        ```

        Add the following config, edit `server_name` as required:

        ```nginx
        server {
         listen      80;
         server_name librenms.example.com;
         root        /opt/librenms/html;
         index       index.php;

         charset utf-8;
         gzip on;
         gzip_types text/css application/javascript text/javascript application/x-javascript image/svg+xml text/plain text/xsd text/xsl text/xml image/x-icon;
         location / {
          try_files $uri $uri/ /index.php?$query_string;
         }
         location ~ [^/]\.php(/|$) {
          fastcgi_pass unix:/run/php-fpm-librenms.sock;
          fastcgi_split_path_info ^(.+\.php)(/.+)$;
          include fastcgi.conf;
         }
         location ~ /\.(?!well-known).* {
          deny all;
         }
        }
        ```

        ```bash
        rm /etc/nginx/sites-enabled/default
        systemctl reload nginx
        systemctl restart php8.4-fpm
        ```

## SELinux

=== "Ubuntu 24.04"
    SELinux not enabled by default

=== "Ubuntu 22.04"
    SELinux not enabled by default

=== "CentOS 8"
    Install the policy tool for SELinux:

    ```
    dnf install policycoreutils-python-utils
    ```

    <h3>Configure the contexts needed by LibreNMS</h3>

    ```
    semanage fcontext -a -t httpd_sys_content_t '/opt/librenms/html(/.*)?'
    semanage fcontext -a -t httpd_sys_rw_content_t '/opt/librenms/(rrd|storage)(/.*)?'
    semanage fcontext -a -t httpd_log_t "/opt/librenms/logs(/.*)?"
    semanage fcontext -a -t httpd_cache_t '/opt/librenms/cache(/.*)?'
    semanage fcontext -a -t bin_t '/opt/librenms/librenms-service.py'
    semanage fcontext -a -t httpd_cache_t '/opt/librenms/cache(/.*)?'
    restorecon -RFvv /opt/librenms
    setsebool -P httpd_can_sendmail=1
    setsebool -P httpd_execmem 1
    chcon -t httpd_sys_rw_content_t /opt/librenms/.env
    ```

    <h3>Allow fping</h3>

    Create the file http_fping.tt with the following contents. You can
    create this file anywhere, as it is a throw-away file. The last step
    in this install procedure will install the module in the proper
    location.

    ```
    module http_fping 1.0;

    require {
    type httpd_t;
    class capability net_raw;
    class rawip_socket { getopt create setopt write read };
    }

    #============= httpd_t ==============
    allow httpd_t self:capability net_raw;
    allow httpd_t self:rawip_socket { getopt create setopt write read };
    ```

    Then run these commands

    ```
    checkmodule -M -m -o http_fping.mod http_fping.tt
    semodule_package -o http_fping.pp -m http_fping.mod
    semodule -i http_fping.pp
    ```

    Additional SELinux problems may be found by executing the following command

    ```
    audit2why < /var/log/audit/audit.log
    ```

=== "Debian 12"
    SELinux not enabled by default

## Allow access through firewall
=== "Ubuntu 24.04"
    Firewall not enabled by default

=== "Ubuntu 22.04"
    Firewall not enabled by default

=== "CentOS 8"

    ```
    firewall-cmd --zone public --add-service http --add-service https
    firewall-cmd --permanent --zone public --add-service http --add-service https
    ```

=== "Debian 12"
    Firewall not enabled by default

=== "Debian 13"
    Firewall not enabled by default

## Enable lnms command completion

This feature grants you the opportunity to use tab for completion on lnms commands as you would
for normal linux commands.

```
ln -s /opt/librenms/lnms /usr/bin/lnms
cp /opt/librenms/misc/lnms-completion.bash /etc/bash_completion.d/
```

`lnms config` is the preferred method for [Configuration](../Support/Configuration.md)


## Configure snmpd (v2c)

If you would like to use SNMPv3 then please [see here](../Support/SNMP-Configuration-Examples.md/#linux-snmpd-v3)

```
cp /opt/librenms/snmpd.conf.example /etc/snmp/snmpd.conf
```

```
vi /etc/snmp/snmpd.conf
```

Edit the text which says `RANDOMSTRINGGOESHERE` and set your own community string.

```
curl -o /usr/bin/distro https://raw.githubusercontent.com/librenms/librenms-agent/master/snmp/distro
chmod +x /usr/bin/distro
systemctl enable snmpd
systemctl restart snmpd
```

## Cron job

```
cp /opt/librenms/dist/librenms.cron /etc/cron.d/librenms
```

!!! note
    Keep in mind  that cron, by default, only uses a very limited
    set of environment variables. You may need to configure proxy
    variables for the cron invocation. Alternatively adding the proxy
    settings in config.php is possible too. The config.php file will be
    created in the upcoming steps. Review the following URL after you
    finished librenms install steps:
    <@= config.site_url =@/Support/Configuration/#proxy-support>

## Enable the scheduler

```
cp /opt/librenms/dist/librenms-scheduler.service /opt/librenms/dist/librenms-scheduler.timer /etc/systemd/system/

systemctl enable librenms-scheduler.timer
systemctl start librenms-scheduler.timer
```

## Enable logrotate

LibreNMS keeps logs in `/opt/librenms/logs`. Over time these can
become large and be rotated out.  To rotate out the old logs you can
use the provided logrotate config file:

```
cp /opt/librenms/misc/librenms.logrotate /etc/logrotate.d/librenms
```

## Web installer

Now head to the web installer and follow the on-screen instructions.

<http://librenms.example.com/install>

The web installer might prompt you to create a `config.php` file in
your librenms install location manually, copying the content displayed
on-screen to the file. If you have to do this, please remember to set
the permissions on config.php after you copied the on-screen contents
to the file. Run:

```
chown librenms:librenms /opt/librenms/config.php
```

## Final steps

That's it!  You now should be able to log in to
<http://librenms.example.com/>.

!!! danger
    Please note that we have not covered
    HTTPS setup in this example, so your LibreNMS install is not secure
    by default.  Please do not expose it to the public Internet unless
    you have configured HTTPS and taken appropriate web server hardening
    steps.

## Add the first device

We now suggest that you add localhost as your first device from within the WebUI.
<https://librenms.example.com/addhost>

## Troubleshooting

If you ever have issues with your install, you should run validate which will perform
some base checks and provide the recommended fixes:

```
sudo su - librenms
./validate.php
```

There are various options for getting help listed on the LibreNMS web
site: <https://www.librenms.org/#support>

## What next?

Now that you've installed LibreNMS, we'd suggest that you have a read
of a few other docs to get you going:

- [Performance tuning](../Support/Performance.md)
- [Alerting](../Alerting/index.md)
- [Device Groups](../Extensions/Device-Groups.md)
- [Auto discovery](../Extensions/Auto-Discovery.md)
- [High Availability](../Support/High-Availability.md)

## Closing

We hope you enjoy using LibreNMS. If you do, it would be great if you
would consider opting into the stats system we have, please see [this
page](../General/Callback-Stats-and-Privacy.md) on
what it is and how to enable it.

If you would like to help make LibreNMS better there are [many ways to
help](../Support/FAQ.md#faq9). You
can also [back LibreNMS on Open Collective](https://t.libren.ms/donations).
