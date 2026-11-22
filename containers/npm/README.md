# NPM Nginx Proxy Manager

Name: npm
Description: nginx proxy manager

## logs

```sh
ssh -t srv-nas-pi "sudo docker container logs nginx-proxy --follow"
```

## config

- data is in `/pool0/storage/compose/npm/data`
- letsencrypt in `/pool0/storage/compose/npm/letsencrypt`

## Proxy Hosts

<img width="1297" height="702" alt="image" src="https://github.com/user-attachments/assets/3f33aa66-2e42-4a26-a8ae-317bf71d3e58" />


## Certificates

<img width="2624" height="980" alt="image" src="https://github.com/user-attachments/assets/80e97d06-a7ef-4486-99ce-6b0cbed739e1" />

### Internal certificates from step-ca

Hosts on the LAN (`npm.kingma`, `lyrion.kingma`, ...) use certificates issued by the local [step-ca](https://smallstep.com/docs/step-ca/) at `https://ca.kingma:9000`. NPM has no ACME client for a private CA, so these are **Custom Certificates**: issued with `step`, uploaded once, then renewed in place by a systemd service.

#### Issue (from a bootstrapped workstation)

The ACME provisioner needs port 80 and a name allowed by the CA policy, so the JWK provisioner `admin` is used instead (no challenge, password prompt):

```sh
step ca provisioner list    # note the JWK provisioner name
step ca certificate npm.kingma npm.crt npm.key \
  --provisioner admin \
  --not-after 2160h         # 90 days, capped by maxTLSCertDuration in ca.json
```

`npm.crt` contains leaf and intermediate. Verify with `step certificate inspect npm.crt --short`.

#### Add to NPM

1. SSL Certificates > Add SSL Certificate > Custom
2. Name `npm.kingma`, Certificate `npm.crt`, Certificate Key `npm.key`, Intermediate empty
3. Proxy Host for the admin UI: domain `npm.kingma`, scheme `http`, forward `192.168.111.11` port `81`, Websockets Support on
4. SSL tab: select the certificate, Force SSL

The TLS port is the published `9443` (see `compose.yaml`), so the admin UI is reached at `https://npm.kingma:9443`. Port 81 stays plain HTTP.

The root CA must be in the client trust store (`/etc/step/root_ca.crt` on the server; for Arch/Fedora based clients copy it to the p11-kit anchors and run `sudo update-ca-trust`).

#### Renewal

NPM only auto-renews Let's Encrypt certificates, so custom ones are renewed by `step ca renew --daemon`. NPM keeps uploaded certificates as `data/custom_ssl/npm-<id>/fullchain.pem` and `privkey.pem` (`<id>` is the certificate ID in NPM). The daemon renews these files in place and reloads nginx in the container.

`npm-cert-renew@.service` is a template, the instance name is the NPM certificate ID. Find the ID by subject:

```sh
for d in /pool0/storage/compose/npm/data/custom_ssl/npm-*; do
  echo "$d: $(step certificate inspect $d/fullchain.pem --short | grep Subject)"
done
```

Install and enable one instance per certificate:

```sh
sudo install -m 0644 npm-cert-renew@.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now npm-cert-renew@<id>
journalctl -u 'npm-cert-renew@*' -n 20
```

Test once by hand (renews immediately and reloads nginx):

```sh
sudo /usr/local/bin/step ca renew --force \
  --ca-url https://ca.kingma:9000 --root /etc/step/root_ca.crt \
  --exec "/usr/bin/docker exec nginx-proxy nginx -s reload" \
  /pool0/storage/compose/npm/data/custom_ssl/npm-<id>/fullchain.pem \
  /pool0/storage/compose/npm/data/custom_ssl/npm-<id>/privkey.pem
```

Notes:

- Without `--renew-period` the daemon renews at about two thirds of the certificate lifetime (about day 60 of 90).
- `step ca renew` authenticates with the current certificate. If it has expired, issue a new one with `step ca certificate` (JWK provisioner) and upload it again.
- The files are overwritten behind NPM's back: the expiry date in the UI stays at the original date, and the `<id>` changes if the certificate is deleted and re-added in the UI.

### Add another host using the local CA

Example: a new service `<name>.kingma` that NPM proxies over TLS with a certificate from step-ca. Replace `<name>` and `<id>` throughout.

#### 1. DNS entry

On the local nameserver (net-ns) add an A record that points the new name to srv-nas-pi, the host that runs NPM:

| Name | Type | Value |
|------|------|-------|
| `<name>.kingma` | A | `192.168.111.11` |

srv-nas-pi has two NICs in two networks with separate ISPs:

| Network | Range | srv-nas-pi | Used for |
|---------|-------|------------|----------|
| internal (home/LAN) | `192.168.111.0/27` | `192.168.111.11` | local clients, NPM admin |
| DMZ (office ISP) | `192.168.0.0/24` | `192.168.0.11` | public services, port forwards in the main README |

There is no route between the two networks, so local clients must resolve names to `192.168.111.11`, not `192.168.0.11`. The name points to NPM, not to the backend: NPM forwards to the real backend address in step 3.

Check from a client:

```sh
getent hosts <name>.kingma    # must print 192.168.111.11
```

#### 2. Certificate

On a workstation bootstrapped to the CA (`step ca bootstrap`), issue the certificate with the JWK provisioner `admin`:

```sh
step ca certificate <name>.kingma <name>.crt <name>.key \
  --provisioner admin \
  --not-after 2160h
step certificate inspect <name>.crt --short    # SAN and validity
```

Add `--san <other-name>` for further names. `<name>.crt` contains leaf and intermediate.

#### 3. NPM entry

1. SSL Certificates > Add SSL Certificate > Custom: name `<name>.kingma`, Certificate `<name>.crt`, Certificate Key `<name>.key`, Intermediate empty.
2. Proxy Hosts > Add: domain `<name>.kingma`, scheme and forward hostname/IP/port of the backend, Websockets Support if the app needs it.
3. SSL tab: select the new certificate, enable Force SSL and HTTP/2.

Delete the local copies of `<name>.key` after the upload, NPM holds the key now.

#### 4. Auto renew

Find the NPM certificate ID of the new certificate (the newest `npm-<id>` directory, confirmed by its subject):

```sh
for d in /pool0/storage/compose/npm/data/custom_ssl/npm-*; do
  echo "$d: $(sudo step certificate inspect $d/fullchain.pem --short | grep Subject)"
done
```

Enable one instance of the template unit (see [Renewal](#renewal)) for that ID:

```sh
sudo systemctl enable --now npm-cert-renew@<id>
systemctl status npm-cert-renew@<id>
```

The unit file is installed once, per-host work is only the `enable` line.

#### 5. Test

```sh
# name resolves to srv-nas-pi
getent hosts <name>.kingma

# TLS chain is trusted (root CA in the client trust store) and the backend answers
curl -I https://<name>.kingma:9443

# certificate served by NPM: subject, issuer, dates
echo | openssl s_client -connect <name>.kingma:9443 -servername <name>.kingma 2>/dev/null \
  | openssl x509 -noout -subject -issuer -dates

# renewal path: forces one renewal and reloads nginx
sudo /usr/local/bin/step ca renew --force \
  --ca-url https://ca.kingma:9000 --root /etc/step/root_ca.crt \
  --exec "/usr/bin/docker exec nginx-proxy nginx -s reload" \
  /pool0/storage/compose/npm/data/custom_ssl/npm-<id>/fullchain.pem \
  /pool0/storage/compose/npm/data/custom_ssl/npm-<id>/privkey.pem
```

After the forced renewal, "Valid from" in the `openssl s_client` output must show the current time. If `curl` reports a certificate problem, the root CA is missing in the client trust store. If it cannot connect, check the DNS record and that port `9443` is reachable.
