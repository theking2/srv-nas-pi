# srv-nas-pi

Is a home server project that was build with Pi5, Radxa Penta PCI board, 4 2TB SSDs, a USB-Ethernet adaptor. Software Open Media Vault with Plugins ZFS and Compose. As container Lyrion Music Server, OpenSpeedTest, Nextcloud, Collabora and a small home webserver for the local IoT appliances. Backups are on the eSATAx connected HDD and a standby TrueNAS. A seconds Pi5 Based OMV with virtually the same setup is used for lab tests.

## PI5

Connected to LAN on the PI's ethernet port and to WAN on a Renkforce USB-Ethernet cable.

Add this to `/boot/firmware/config.txt`

```ini
dtparam=pciex1
dtparam=pciex1_gen=3
dtoverlay=pcie-32bit-dma-pi5
dtoverlay=dwc2,dr_mode=host
```

Needed for:

- penta pcie board
- Renkforce USB-Ethernet adaptor (???)
- pcie-32bit-dma-pi5 is needed because of newer kernel: ([dtoverlay=pcie-32bit-dma-pi5](https://forum.openmediavault.org/index.php?thread/59049-psa-rpi5-users-of-the-radxa-sata-hat-now-need-to-use-dtoverlay-pcie-32bit-dma-pi/#post437357))



Open Mediavault installed

## zpool list

      NAME    SIZE  ALLOC   FREE  CKPOINT  EXPANDSZ   FRAG    CAP  DEDUP    HEALTH  ALTROOT
      pool0  14.5T  4.46T  10.1T        -         -    18%    30%  1.08x    ONLINE  -

## zfs list

      NAME            USED  AVAIL  REFER  MOUNTPOINT
      pool0          3.35T  7.42T  35.9K  /pool0
      pool0/archive  3.19T  1.44T  3.06T  /pool0/archive
      pool0/main     90.2G  59.8G  13.5G  /pool0/main
      pool0/storage  69.4G   553G  46.9G  /pool0/storage
      pool0/swap     4.18G  7.42T  2.55G  -


## Shares

 * `CHANGE_TO_COMPOSE_DATA_PATH` is set to point to `/pool0/storage/compose` and is used for large data
 * Compose `yaml` files that use `./` point to `/pool0/main/compose/appdata/<container-name>` and is used for configuration files.

| Name                  | Device        | Relative Path                       | Absolute Path                                                  |
|-----------------------|---------------|-------------------------------------|----------------------------------------------------------------|
| appdata<br>`./<container-name>`| pool0/main | compose/appdata/              | /pool0/main/compose/appdata                                    |
| backup                | pool0/storage | backup/                             | /pool0/storage/backup                                          |
| compose<br>`CHANGE_TO_COMPOSE_DATA_PATH`| pool0/storage | compose/          | /pool0/storage/compose                                         |
| compose_backup        | pool0/archive | long_archive/backup                 | /pool0/archive/long_archive/backup                             |
| media                 | pool0/archive | media/                              | /pool0/archive/media                                           |

## Router setup

IP | what | notes
-|-|-
192.168.0.1 | LAN | ISP1 router
192.168.0.11 | srv-nas-pi | via Renkforce USB
xxx.xxx.xxx.xxx | WAN | https://*.kingma.ch and https://*.king.ma all proxied through npm

## Nameserver

All public names are in metanet NS

## Routing and PAT

In a dedicated office ISP with provided Sagecom router. (Home traffic and LAN over a secondary ISP, hence the two nic)

Port | forwared | What
-|-|-
80 | 192.168.0.11:9080 | needed for speedtest (and let's encrypt renewals?)
443 | 192.168.0.11:9443 | nextcloud/onlyoffice-docserver
81 | 192.168.0.11:81 | NPM admin, off (we manage from LAN only)

## Ports on srv-nas-pi

NPM runs in Docker and owns the standard ports on the LAN. The OMV web UI was moved off 443 to 8443. On the DMZ side NPM keeps the non-standard ports, because the router forwards to them (see above).

| Network | Address | Port | Service |
|---------|---------|------|---------|
| LAN | `192.168.111.11` | 80 | NPM, http → https redirect |
| LAN | `192.168.111.11` | 443 | NPM, https (e.g. `https://npm.kingma`) |
| LAN | `192.168.111.11` | 81 | NPM admin (http, via proxy host `npm.kingma`) |
| LAN | `192.168.111.11` | 8443 | OMV web UI (`https://srv-nas-pi.kingma:8443`) |
| DMZ | `192.168.0.11` | 9080 | NPM, WAN 80 forward |
| DMZ | `192.168.0.11` | 9443 | NPM, WAN 443 forward |

The published ports are defined in `containers/npm/compose.yaml`. The OMV port is set in System > Workbench.

## pool0 – tree

```
/pool0 $ tree -L 2
.
├── archive
│   ├── archive
│   ├── long_archive
│   └── media
├── main
│   └── compose
│       ├── appdata
│       ├── docker
│       ├── global.env
│       └── rclone
└── storage
    ├── Dropbox
    ├── backup
    ├── compose
    └── nextcloud_data
```

## pool0/storage/compose – tree

```
storage/compose/
├── collabora_coolwsd
│   ├── coolkitconfig.xcu
│   └── coolwsd.xml
├── lms_config
│   ├── cache
│   ├── core
│   ├── logs
│   └── prefs
├── mariadb_data
...
├── my-strom_htdocs
...
├── nextcloud
│   ├── config
│   ├── custom_apps
│   ├── nextclouddb
│   └── redis
├── npm
│   ├── data
│   └── letsencrypt
├── onlyoffice
│   ├── data
│   ├── db
│   ├── fonts
│   ├── lib
│   ├── logs
│   ├── rabbitmq
│   └── redis
├── torrelay
│   └── torrc
└── transmission
    ├── config
    └── watch
``` 



## Swap

### create swap volume on zfs pool:

```
zfs create -V 4G -b $(getconf PAGESIZE) -o compression=zle \
      -o logbias=throughput -o sync=always \
      -o primarycache=metadata -o secondarycache=none \
      -o com.sun:auto-snapshot=false pool0/swap

mkswap /dev/zvol/pool0/swap
```

### add the swap to fstab

```
/dev/zvol/pool0/swap none swap discard 0 0
```

### disable swap creation

```
sudo systemctl disable dphys-swapfile
```

