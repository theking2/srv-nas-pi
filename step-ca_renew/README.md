# step-ca_renew

Automatic renewal of the OpenMediaVault (OMV) web UI TLS certificate issued by a private [step-ca](https://smallstep.com/docs/step-ca/). A systemd timer checks regularly whether the certificate is due; if so, it renews it with `step ca renew` and installs it into OMV's nginx.

## Files

| File | Install to | Purpose |
|------|-----------|---------|
| `omv-cert-renew.service` | `/etc/systemd/system/` | One-shot unit: renews the certificate if needed, then runs the install script |
| `omv-cert-renew.timer` | `/etc/systemd/system/` | Triggers the service every 15 minutes |
| `omv-install-step-cert.sh` | `/usr/local/sbin/` (executable) | Copies the renewed cert/key to OMV's paths and reloads nginx |

## How it works

1. `omv-cert-renew.timer` fires every 15 minutes (`OnCalendar=*-*-* *:00/15:00`) with up to 5 minutes random delay. `Persistent=true` catches up on a run missed while the machine was off.
2. `omv-cert-renew.service` first evaluates `ExecCondition`: `step certificate needs-renewal /etc/step/certs/srv-nas-pi.crt`. If the certificate is not yet due, the unit is skipped without error.
3. If due, `step ca renew --force` requests a new certificate from the CA (`https://ca.kingma:9000`, trust anchor `/etc/step/root_ca.crt`) and overwrites `/etc/step/certs/srv-nas-pi.crt` and `.key`.
4. `ExecStartPost` runs `omv-install-step-cert.sh`, which:
   - installs the cert to `/etc/ssl/certs/openmediavault-<UUID>.crt` (mode `0644`)
   - installs the key to `/etc/ssl/private/openmediavault-<UUID>.key` (mode `0600`)
   - runs `omv-salt deploy run nginx` and `systemctl reload nginx`

## Prerequisites

- `step` CLI installed at `/usr/local/bin/step`
- The CA root certificate at `/etc/step/root_ca.crt`
- An initial certificate and key already issued by the CA at `/etc/step/certs/srv-nas-pi.crt` / `srv-nas-pi.key` (`step ca renew` renews an existing certificate, it does not create the first one)
- The CA reachable at `https://ca.kingma:9000` from the host
- A certificate entry in OMV (System > Certificates > SSL) selected for the web UI

## Setup

1. Look up the UUID of the OMV certificate entry:

   ```bash
   omv-confdbadm read conf.system.certificate.ssl
   ```

2. Set `CERT_UUID` in `omv-install-step-cert.sh` to that value.

3. Install the files:

   ```bash
   sudo install -m 0755 omv-install-step-cert.sh /usr/local/sbin/omv-install-step-cert.sh
   sudo install -m 0644 omv-cert-renew.service omv-cert-renew.timer /etc/systemd/system/
   ```

4. Enable the timer:

   ```bash
   sudo systemctl daemon-reload
   sudo systemctl enable --now omv-cert-renew.timer
   ```

## Adapting to another host

Change these values:

| Where | Value |
|-------|-------|
| `omv-cert-renew.service` | `--ca-url`, certificate/key file names under `/etc/step/certs/` |
| `omv-install-step-cert.sh` | `CERT_SRC`, `KEY_SRC`, `CERT_UUID` |

## Operation

Check the timer and the last run:

```bash
systemctl list-timers omv-cert-renew.timer
journalctl -u omv-cert-renew.service
```

Run a renewal check manually:

```bash
sudo systemctl start omv-cert-renew.service
```

If the certificate is not yet due, the service is skipped by `ExecCondition`. To force the renewal and install path, run the commands from the service file by hand with the same `STEPPATH=/etc/step`.

Inspect the active certificate:

```bash
step certificate inspect /etc/step/certs/srv-nas-pi.crt --short
```

## Notes

- `step certificate needs-renewal` uses its default threshold (about two thirds of the certificate lifetime elapsed). Add `--expires-in <duration>` or a percentage to the `ExecCondition` to change it.
- `step ca renew` authenticates with the current, still valid certificate. If the certificate has already expired, it must be re-issued with `step ca certificate` instead.
- The private key is copied into OMV's key directory with mode `0600`. Keep `/etc/step/certs` restricted to root.
