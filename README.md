# srv-nas-pi

Is a home server project that was build with Pi5, Radxa Penta PCI board, 4 2TB SSDs, a USB-Ethernet adaptor. Software Open Media Vault with Plugins ZFS and Compose. As container Lyrion Music Server, OpenSpeedTest, Nextcloud and a small home webserver for the local IoT appliances. Backups are on the eSATAx connected HDD and a standby TrueNAS. A seconds Pi5 Based OMV with virtually the same setup is used for lab tests.

## PI5

Connected to LAN on the PI's ethernet port and to WAN on a Renkforce USB-Ethernet cable.

Add this to `/boot/firmware/config.txt`

```ini
dtparam=pciex1
dtparam=pciex1_gen=3
dtoverlay=dwc2,dr_mode=peripheral
```

Needed for:

- penta pcie board
- Renkforce USB-Ethernet adaptor (???)

The last is weird as it should be in host mode for a peripheral. 

Open Mediavault installed

## zpool list

      NAME    SIZE  ALLOC   FREE  CKPOINT  EXPANDSZ   FRAG    CAP  DEDUP    HEALTH  ALTROOT
      pool0  14.5T  3.45T  11.1T        -         -     1%    23%  1.00x    ONLINE  -

## zfs list

      NAME            USED  AVAIL  REFER  MOUNTPOINT
      pool0          2.58T  8.18T  35.9K  /pool0
      pool0/archive  2.17T  8.18T  2.07T  /pool0/archive
      pool0/main     4.77G  5.23G  3.51G  /pool0/main
      pool0/storage   415G   385G   407G  /pool0/storage

## Shares

CHANGE_TO_COMPOSE_DATA_PATH is set to point to `/pool0/storage/compose`

| Name                   | Device        | Relative Path                        | Absolute Path                                                  |
|------------------------|---------------|--------------------------------------|----------------------------------------------------------------|
| appdata               | poolO/main    | compose/appdata/                    | /poolO/main/compose/appdata                                    |
| backup                | poolO/storage | backup/                             | /poolO/storage/backup                                          |
| compose               | poolO/storage | compose/                            | /poolO/storage/compose                                         |
| compose_backup        | poolO/main    | compose/backup/                     | /poolO/main/compose/backup                                     |
| media                 | poolO/archive | media/                              | /poolO/archive/media                                           |
| transmission_downloads| poolO/storage | compose/downloads/complete/         | /poolO/storage/compose/downloads/complete                      |
| transmission_watch    | poolO/storage | compose/watch/                      | /poolO/storage/compose/watch                                   |


## Router setup

IP | what | notes
-|-|-
192.168.1.1 | LAN | ISP1 router
192.168.1.20 | srv-nas-pi | via Renkforce USB
188.155.240.245 | WAN | https://office.kingma.ch, http://speed.kingma.ch, https://office.king.ma

## Nameserver

All public names are in metanet NS

## Routing and PAT

In a dedicated office ISP with provided Sagecom router. (Home traffic and LAN over a secondary ISP, hence the two nic)

Port | forwared | What
-|-|-
80 | 192.168.1.20:9080 | open speedtest (?)
443 | 192.168.1.20:9443 | nextcloud
81 | 192.168.20:81 | NPM admin, off
8080 | 192.168.1.20:8080 | open nextcloud test without proxy
3000 | 192.168.1.20:3000 | open speedtest, off
