# Bluetooth for Home Assistant

USB dongle: Realtek RTL8761BU (`0bda:a729`, "Bluetooth Radio"), BD address `00:E0:4C:56:AD:AD`.
The `00:E0:4C` Realtek default address is normal for this chip.

## 1. Disable onboard Bluetooth

So the dongle becomes `hci0` and every tool uses it by default.

`/boot/firmware/config.txt`, under `[all]`:

```ini
dtoverlay=disable-bt
```

```bash
sudo systemctl disable --now hciuart
sudo reboot
```

Check: `hciconfig -a` / `bluetoothctl list` show only the dongle as `hci0`.

## 2. Firmware

Loaded by the kernel (`btusb` + `btrtl`), needs `firmware-realtek`:

```bash
dmesg | grep -iE 'bluetooth|rtl'
# expect: loading rtl_bt/rtl8761bu_fw.bin ... fw version 0x...
```

## 3. Unblock and power on

The adapter came up soft-blocked (`Operation not possible due to RF-kill`).

```bash
rfkill list                    # find the index of the dongle
sudo rfkill unblock <index>    # or: sudo rfkill unblock bluetooth
sudo hciconfig hci0 up
```

`systemd-rfkill` restores the state per USB path at boot, so keep the dongle in the same port.
If it comes back blocked: `grep -H . /var/lib/systemd/rfkill/*` and set the dongle's entry to `0`.

Power on at boot, `/etc/bluetooth/main.conf`:

```ini
[Policy]
AutoEnable=true
```

```bash
sudo systemctl restart bluetooth
```

## 4. Container access

Use the host's BlueZ over D-Bus. Do not run `bluetoothd` in the container.

```yaml
services:
  homeassistant:
    network_mode: host
    volumes:
      - /run/dbus:/run/dbus:ro
```

Home Assistant picks up the adapter automatically under *Settings → Devices & services → Bluetooth*.
Live view of received advertisements: `http://<ha>:8123/config/bluetooth/advertisement-monitor`

## 5. Reception

USB 3 ports, cables and the SSDs emit noise in the 2.4 GHz band. With the dongle plugged in
next to them, every signal arrived at -78 dBm or worse, even a phone held next to the Pi.

- Use a USB 2.0 port (black), not USB 3 (blue).
- Move the dongle away from the Pi and the drives with a USB 2.0 extension cable (0.5–1 m).
- A long antenna does not help while the noise source sits right next to it.

Expect around -40 to -60 dBm for a device right next to the dongle.

## 6. Phone presence (Android)

Android rotates its BLE address, so the phone is not tracked as a plain Bluetooth device.

1. Companion App: *Settings → Companion App → Manage Sensors → BLE Transmitter* → on.
   Transmit power *High*, advertise mode *Low latency*, battery optimization for the app *Unrestricted*.
2. HA: add the **iBeacon Tracker** integration.
3. Get the UUID from `sensor.<phone>_ble_transmitter`, attribute `id` (`<uuid>-<major>-<minor>`).
4. iBeacon Tracker options (gear icon): add the UUID (without major/minor) to the allowed UUIDs.

The phone then appears as a device with a `device_tracker` and a distance estimate.
BLE presence is good for room level; for home/away, GPS/Wi-Fi tracking of the Companion App is more robust.
