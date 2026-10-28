#!/bin/bash
set -euo pipefail

CERT_SRC=/etc/step/certs/srv-nas-pi.crt
KEY_SRC=/etc/step/certs/srv-nas-pi.key

# Get this from:
# omv-confdbadm read conf.system.certificate.ssl
CERT_UUID="5c75829e-d5b5-416b-8298-b43cbd73b218"

CERT_DST="/etc/ssl/certs/openmediavault-${CERT_UUID}.crt"
KEY_DST="/etc/ssl/private/openmediavault-${CERT_UUID}.key"

install -m 0644 "$CERT_SRC" "$CERT_DST"
install -m 0600 "$KEY_SRC" "$KEY_DST"

omv-salt deploy run nginx
systemctl reload nginx
