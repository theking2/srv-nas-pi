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

1. Look up the UUID of the certificate the OMV web UI uses (`sslcertificateref`). This must be the certificate that is actually assigned to the web UI, otherwise the renewed files are installed but never served:

   ```bash
   sudo omv-confdbadm read conf.webadmin | grep -i sslcertificateref
   ```

   All certificate entries, for reference:

   ```bash
   sudo omv-confdbadm read conf.system.certificate.ssl
   ```

2. Set `CERT_UUID` in `omv-install-step-cert.sh` to that value (and update the comment above it).

3. Restrict the step directory and the private key to root:

   ```bash
   sudo chown root:root /etc/step/certs/srv-nas-pi.key
   sudo chmod 600 /etc/step/certs/srv-nas-pi.key
   sudo chmod 700 /etc/step/certs
   ```

4. Install the files:

   ```bash
   sudo install -m 0755 omv-install-step-cert.sh /usr/local/sbin/omv-install-step-cert.sh
   sudo install -m 0644 omv-cert-renew.service omv-cert-renew.timer /etc/systemd/system/
   ```

5. Enable the timer:

   ```bash
   sudo systemctl daemon-reload
   sudo systemctl enable --now omv-cert-renew.timer
   ```

6. Test the full path once (see [Testing the renewal path](#testing-the-renewal-path)). Until then only the skip branch has been exercised.

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

If the certificate is not yet due, the service is skipped by `ExecCondition` and the journal shows `certificate does not need renewal`. This is the normal state most of the time.

Inspect the active certificate:

```bash
sudo step certificate inspect /etc/step/certs/srv-nas-pi.crt --short
```

## Testing the renewal path

Force a renewal and install without waiting for the threshold:

```bash
sudo STEPPATH=/etc/step step ca renew --force \
  --ca-url https://ca.kingma:9000 \
  --root /etc/step/root_ca.crt \
  /etc/step/certs/srv-nas-pi.crt /etc/step/certs/srv-nas-pi.key
sudo /usr/local/sbin/omv-install-step-cert.sh
```

Then check each stage. Use `sudo` for `ls`, and name the key file explicitly: `/etc/ssl/private` is not readable for normal users, so a `*` glob is expanded by your shell before `sudo` runs and finds nothing.

```bash
# 1. renewed source certificate (new validity dates)
sudo step certificate inspect /etc/step/certs/srv-nas-pi.crt --short

# 2. key permissions still 600
sudo ls -l /etc/step/certs/srv-nas-pi.key

# 3. installed copies (use your CERT_UUID)
sudo ls -l /etc/ssl/certs/openmediavault-<UUID>.crt /etc/ssl/private/openmediavault-<UUID>.key

# 4. what the web server actually serves
echo | openssl s_client -connect localhost:443 -servername <hostname> 2>/dev/null \
  | openssl x509 -noout -serial -dates
```

The serial and dates in step 4 must match step 1. If they do not, nginx is not serving the installed certificate. Likely reasons: `CERT_UUID` differs from `sslcertificateref` in `conf.webadmin`, another process (for example a container proxy) listens on the port, or the `ssl_certificate` path in the nginx configuration points elsewhere:

```bash
sudo ss -tlnp | grep -E ':(80|443)\b'
sudo grep -rn ssl_certificate /etc/nginx/openmediavault-webgui.d/ /etc/nginx/conf.d/ /etc/nginx/sites-enabled/
```

## Notes

- `step certificate needs-renewal` uses its default threshold (about two thirds of the certificate lifetime elapsed). Add `--expires-in <duration>` or a percentage to the `ExecCondition` to change it.
- `step ca renew` authenticates with the current, still valid certificate. If the certificate has already expired, it must be re-issued with `step ca certificate` instead.
- The private key is copied into OMV's key directory with mode `0600`. Keep `/etc/step/certs` (mode `0700`) and the source key (mode `0600`) restricted to root.
- The root CA certificate is read from `/etc/step/root_ca.crt` (the path in the service file). A second copy in `/etc/step/certs/` is not used.
