# srv-nas-pi

## Router setup

IP | What
-|-
192.168.1.1 | LAN
192.168.1.20 | srv-nas-pi, via Renkforce USB
188.155.240.245 | WAN, https://office.kingma.ch, https://speed.kingma.ch

Nameserver with metanet https://cicero.metanet.cu:8443
Port | PAT | What
-|-|-
80 | 192.168.1.20:9080 | open nextcloud (needed?)
443 | 192.168.1.20:9443 | nextcloud
81 | 192.168.20:81 | NPM admin, off
8080 | 192.168.1.20:8080 | open nextcloud (needed?)
3000 | 192.168.1.20:3000 | open speedtest
123 | 192.168.1.20:123 | time
