# Settings

OMV Service/compose/settings section:

## Compose Files

- appdata [on pool0/main, compose/appdata/]

## Data

- compose [on pool0/storage, compose/]

## Backup

- compose_backup [on pool0/main, compose/backup/]

## Docker

- /pool0/main/compose/docker

## Overrides

none


## Networks

name | purpose
-|-
bridge|build-in default driver
host|unisolated host driver
none|isolated driver
proxy | connects `npm` to the proxied servers
my-strom_web | (shouldv't named that one myself)
nextcloud_backend | connects redis, nextcloud, nextclouddb
