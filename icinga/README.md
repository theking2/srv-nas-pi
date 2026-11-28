# Icinga agent

srv-nas-pi is monitored by the Icinga 2 master on [srv-monitor](https://github.com/theking2/srv-monitor) (`192.168.111.22`). The agent runs on the host, not in a container, so it sees the disks, ZFS and systemd. Set up on 2026-11-28.

| | |
|---|---|
| Agent | `icinga2` 2.16.6 from `packages.icinga.com` (`/etc/apt/sources.list.d/icinga.list`), plus `monitoring-plugins-basic` and `-standard` |
| Endpoint and zone | `srv-nas-pi.kingma`, parent zone `srv-monitor` |
| Connection | master connects to the agent on TCP 5665 (LAN only, not forwarded on the router) |
| Config | from the Director (`accept_config`, `accept_commands`, `conf.d` disabled) |
| Own plugins | [`plugins/`](plugins) in `/usr/local/lib/nagios/plugins/` |
| Root rights | [`sudoers/icinga`](sudoers/icinga) in `/etc/sudoers.d/icinga` (exact `smartctl -H -A` per disk, `vcgencmd get_throttled`) |

The Director objects (commands, templates, host and services) are created by [`icinga/director/srv-nas-pi.sh`](https://github.com/theking2/srv-monitor/blob/main/icinga/director/srv-nas-pi.sh) in the srv-monitor repo.

## Plugins

| Plugin | Checks | Root |
|---|---|---|
| `check_zpool` | pool health, `zpool status -x`, capacity (warn 80 %, crit 90 %) | no |
| `check_smart_disk` | SMART overall health, smartctl status bits, temperature (warn 55, crit 65 °C) | sudo `smartctl` |
| `check_pi_health` | SoC temperature (warn 70, crit 80 °C), under-voltage and throttling | sudo `vcgencmd` |
| `check_memavail` | `MemAvailable` (warn 10 %, crit 5 %), reports the ZFS ARC size | no |
| `check_units` | given systemd units are active (crit), any failed unit (warn) | no |
| `check_link` | interface up with carrier, speed, expected IPv4 address, gateway pinged through that interface (`ping -I`); warns once after a link flap or new rx/tx errors (state in `/var/lib/icinga2/check_link.<iface>`) | no |

`check_smart_disk` reports "attribute below threshold in the past" as OK: it is history, not a current fault.

## Services

| Service | Runs on | What |
|---|---|---|
| `disk`, `load`, `memory`, `swap`, `procs`, `apt` | agent | base checks for every `linux-agent` host; `disk` skips ZFS, CIFS and container file systems |
| `zpool pool0` | agent | `check_zpool` |
| `smart <serial>` (4×) | agent | WD Red SA500 4 TB SSDs in `pool0`, by `/dev/disk/by-id` |
| `smart backup-hdd` | agent | Seagate ST31500341AS 1.5 TB backup HDD on USB/eSATA (`-d usbsunplus`, warn 50, crit 55 °C) |
| `pi health` | agent | `check_pi_health` |
| `systemd units` | agent | `openmediavault-engined`, `docker`, `smartmontools`, `zfs-zed`, `nginx`, `php8.4-fpm`, `ssh`, `nut-monitor`, `collectd`, `monit` |
| `link eth0 LAN`, `link eth1 DMZ` | agent | `check_link` every minute: 1000 Mbit/s, `192.168.111.11` and `192.168.0.11`, gateways `192.168.111.30` and `192.168.0.1` |
| `ping`, `ssh` | master | reachability |
| `ping DMZ` | master | `192.168.0.11` from srv-monitor's own DMZ interface (`192.168.0.22`), so it tests the DMZ link without the router |
| `web NPM DMZ` | master | NPM on the DMZ port `192.168.0.11:9443` (target of the WAN 443 forward) |
| `wan office HTTPS` | master | `https://speed.kingma.ch` through its public address: srv-monitor routes out through the home ISP, so the request comes in through the office router's port forward, the DMZ link and NPM |
| `web OMV`, `web NPM admin`, `web Nextcloud`, `web OnlyOffice`, `web Lyrion`, `web HomeAssistant`, `web Speedtest` | master | HTTP(S) against `192.168.111.11` with the virtual host name; Nextcloud must report `"maintenance":false` |
| `cert <name>` (5×) | master | Let's Encrypt certificate of the public names, warn 21, crit 7 days before expiry |

Note: `office.kingma.ch` is Nextcloud and `cloud.kingma.ch` is OnlyOffice (see the NPM proxy hosts).

## Install on a new or rebuilt NAS

```bash
# repo and packages (keyring as on srv-monitor: /usr/share/keyrings/icinga-archive-keyring.gpg)
echo "deb [signed-by=/usr/share/keyrings/icinga-archive-keyring.gpg] https://packages.icinga.com/debian icinga-trixie main" \
  | sudo tee /etc/apt/sources.list.d/icinga.list
sudo apt update && sudo apt install --no-install-recommends icinga2 monitoring-plugins-basic monitoring-plugins-standard

# plugins and sudoers (adjust the disk IDs in sudoers/icinga if disks change: ls -l /dev/disk/by-id)
sudo install -d /usr/local/lib/nagios/plugins
sudo install -m 755 plugins/* /usr/local/lib/nagios/plugins/
sudo visudo -cf sudoers/icinga && sudo install -m 440 sudoers/icinga /etc/sudoers.d/icinga

# enroll; ticket from srv-monitor: sudo icinga2 pki ticket --cn srv-nas-pi.kingma
sudo icinga2 pki new-cert --cn srv-nas-pi.kingma \
  --key /var/lib/icinga2/certs/srv-nas-pi.kingma.key --cert /var/lib/icinga2/certs/srv-nas-pi.kingma.crt
sudo icinga2 pki save-cert --host 192.168.111.22 --port 5665 --trustedcert /var/lib/icinga2/certs/trusted-parent.crt
sudo openssl x509 -in /var/lib/icinga2/certs/trusted-parent.crt -noout -fingerprint -sha256   # compare with srv-monitor
sudo icinga2 node setup --ticket <ticket> --cn srv-nas-pi.kingma --zone srv-nas-pi.kingma \
  --endpoint srv-monitor,192.168.111.22,5665 --parent_host 192.168.111.22 --parent_zone srv-monitor \
  --trustedcert /var/lib/icinga2/certs/trusted-parent.crt --accept-config --accept-commands --disable-confd
sudo systemctl restart icinga2
```

When a disk is replaced, update its ID in `sudoers/icinga` and in the `smart` services (srv-monitor `icinga/director/srv-nas-pi.sh`).
