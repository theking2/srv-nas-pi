# Nextcloud

## clients

Access from Thunderbird, Davx5 over proxy with `https://office.kingma.ch/remote.php/dav`

## config.php

In `/pool0/main/compose/appdata/nextcloud/config/config.php`

## .htaccess

In `/var/www/html/.htaccess` (access with `docker exec -it nextcloud bash`)

## cron

~~Cron is set as Webcron (couldn't get the Cron version working). Installed OMV anacron and create a weekly job: `wget --spider https://office.kingma.ch/cron.php`
~~

> *75 7-20 * * * /user/bin/wget --spider https://office.kingma.ch-cron.phph ¦ /user/bin/mail -E -s "Nextcloud nc_cron" "From cronon office.kingma.ch" root > -dev-null 2>&1

No anacrom didn't cut it. Can only do dailies. So cron it is:
Switch on `Cron (Recommended)` in nextcloud admin Basic settings. Create a cron job on `srv-nas-pi` (`crontab -e`) like this:

```sh
*/10 * * * * sudo docker exec -u www-data nextcloud php /var/www/html/cron.php
```
which will start the cron process every 10min on the `nextcloud` container. Check by navigating to `https://office.example.com/settings/admin`.


## logs

Follow nextcloud logs with

```sh
ssh -t srv-nas-pi "sudo docker container logs nextcloud --follow"
```


## get version info

Execute as su

```sh
sudo ./get_nextcloud_version
```

Currently

* Nextcloud Server version:
  - `Nextcloud 31.0.4`  
* Operating system and version:
  - `#1 SMP PREEMPT Debian 1:6.12.20-1+rpt1~bpo12+1 (2025-03-19)`
* Web server and version:
  - `Server version: Apache/2.4.62 (Debian)`
* Reverse proxy and version:
  - `nginx version: openresty/1.27.1.1`
* PHP version:
  - `PHP 8.3.20 (cli) (built: Apr 11 2025 17:51:27) (NTS)`
