# NPM Nginx Proxy Manager

Docker name: nginx-proxy

## logs

```sh
ssh -t srv-nas-pi "sudo docker container logs nginx-proxy --follow"
```

## SSL Certificates

- office.kingma.ch, Let's Encrypt

## Proxy Hosts

- office.kingma.ch, http://nextcloud:80, Let's Encrypt
- office.king.ma, http://nextcloud:80, Let's Encrypt
- speed.kingma.ch, http://openspeedtest:3000, HTTP only

## config

- data is in `/pool0/main/compose/appdata/npm/data`
- letsencrypt in `/pool0/main/compose/appdata/npm/letsencrypt`
