# Nextcloud

## clients

Access from Thunderbird, Davx5 over proxy with `https://office.kingma.ch/remote.php/dav`

## config.php

In `/pool0/main/compose/appdata/nextcloud/config/config.php`

## .htaccess

In `/var/www/html/.htaccess` (access with `docker exec -it nextcloud bash`)

## cron

Switch on `Cron (Recommended)` in nextcloud admin Basic settings. Create a cron job on `srv-nas-pi` (`crontab -e`) like this:

```sh
# m h  dom mon dow   command
*/10 * * * * sudo docker exec -u www-data nextcloud php /var/www/html/cron.php
   0 2 * * * sudo docker exec -it nextcloud-db /backup/backup-db
```

Check by navigating to `https://office.example.com/settings/admin` and check the `/backup` folder which is located in `/pool0/storage/nextcloud_data`.


## logs

Follow nextcloud logs with

```sh
ssh -t srv-nas-pi "sudo docker container logs nextcloud --follow"
```
## check status

To check the current status use `https://office.kingma.ch/status.php`

## get version info

Execute as su

```sh
sudo ./get_nextcloud_version
```

or remote

```
ssh -t srv-nas-pi "sudo ./get_nextcloud_version"
```

The current output is here; [current-config.txt](https://github.com/theking2/srv-nas-pi/blob/main/containers/nextcloud/current-config.txt)

## Run occ, Example 

```sh
ssh -t srv-nas-pi "sudo docker exec -it nextcloud /var/www/html/occ db:add-missing-indices"
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
