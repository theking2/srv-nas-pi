FROM ubuntu:jammy AS base

# --- Labels ---
LABEL maintainer="Theking2 <theking2@king.ma>" \
      org.opencontainers.image.source="https://github.com/wollomatic/torrelay" \
      org.opencontainers.image.description="Tor relay in a Ubuntu Docker image" \
      org.opencontainers.image.licenses="MIT" \
      securitytxt="https://king.ma/.well-known/security.txt"

ARG TARGETARCH
ARG TOR_VERSION=0.4.8.20-1~jammy+1


RUN apt-get update \
 && apt-get upgrade -y \
 && apt-get install -y --no-install-recommends \
    apt-transport-https \
    wget \
    gpg \
    gpg-agent \
    ca-certificates \
    tini \
 && wget -qO- https://deb.torproject.org/torproject.org/A3C4F0F979CAA22CDBA8F512EE8CBC9E886DDD89.asc \
    | gpg --dearmor \
    | tee /usr/share/keyrings/tor-archive-keyring.gpg > /dev/null \
 && printf "deb [arch=%s signed-by=/usr/share/keyrings/tor-archive-keyring.gpg] https://deb.torproject.org/torproject.org jammy main\n" \
    "${TARGETARCH}" > /etc/apt/sources.list.d/tor.list \
 && apt-get update \
 && apt-get install -y --no-install-recommends \
    "tor=${TOR_VERSION}" \
    "tor-geoipdb=${TOR_VERSION}" \
 && apt-get purge -y \
    wget \
    gpg \
    gpg-agent \
 && apt-get autoremove -y \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
 && chown debian-tor:debian-tor /etc/tor /var/lib/tor

VOLUME ["/etc/tor", "/var/lib/tor"]

USER debian-tor

ENTRYPOINT ["/usr/bin/tini", "--"]
CMD ["/usr/sbin/tor", "-f", "/etc/tor/torrc"]
