# Maintenance

## Database backup

https://mariadb.com/docs/server/server-usage/backup-and-restore/backup-and-restore-overview

**`backup-db`:**

```
#! /bin/bash

mariadb-dump --user=nextcloud --password=dbpassword nextcloud > /backup/nextclouddb-nextcloud_`date +"%Y%m%d"`.bak
```

schedule on srv-nas-pi with `crontab -e`

```
0 4 * * 5 sudo docker exec -it nextcloud-db /backup/backup-db
```

Restore with

```
mariadb --user=nextcloud --password=dbpassword nextcloud < /backup/nextclouddb-nextcloud_`date +"%Y%m%d"`.bak
```
