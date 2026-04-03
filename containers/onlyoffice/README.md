# ONLYOFFICE — Document Server

Linked with Nextcloud server on https://cloud.king.ma. 

## Config

### Folders

- /pool0/storage/compose/onlyoffice/logs:/var/log/onlyoffice
- /pool0/storage/compose/onlyoffice/data:/var/www/onlyoffice/Data
- /pool0/storage/compose/onlyoffice/lib:/var/lib/onlyoffice
- /pool0/storage/compose/onlyoffice/db:/var/lib/postgresql
- /pool0/storage/compose/onlyoffice/redis:/var/lib/redis
- /pool0/storage/compose/onlyoffice/rabbitmq:/var/lib/rabbitmq
- /pool0/storage/compose/onlyoffice/fonts:/usr/share/fonts/truetype/custom

### Adding fonts

- copy fonts to `/pool0/storage/compose/onlyoffice/fonts/`
- run:
- 
```
sudo docker exec onlyoffice-docs /usr/bin/documentserver-generate-allfonts.sh
```
