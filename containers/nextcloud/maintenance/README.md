# Maintenance

## Database backup

https://mariadb.com/docs/server/server-usage/backup-and-restore/backup-and-restore-overview

```
mariadb-dump --user=nextcloud --password=dbpassword nextcloud > /backup/nextclouddb-nextcloud_`date +"%Y%m%d"`.bak
```

Restore with

```
mariadb --user=nextcloud --password=dbpassword nextcloud < /backup/nextclouddb-nextcloud_`date +"%Y%m%d"`.bak
```
