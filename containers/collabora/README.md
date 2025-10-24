# Collabora

## Installation

 * Add Apps `Nextcloud Office` (richdocuments) to nextcloud.
 * Create a NS entry (different from nextcloud)
 * Setup the docker container
 * Add collab.kingma.ch to NPM forward collab.kingma.ch to http://192.168.0.11:9980 and install the SSL Let's encrypt certificate.
 * In Nextcloud Office settings set `https://collab.kingma.ch` as _URL (and Port) of Collabora Online-server_

## NS

Add a `collab.kingma.ch` name pointing to the external IP. All traffic will be 443 so no additional port forwarding on the router is needed (done for `nextcloud` already)

## Proxy

Make sure to add websocket support to the host!

## Compose YAML

Contrary to certain sources a `\` to escape the dots `.` is not needed. It is not clear if this is just a documentation error or if OMV Compose is so nice to take care of that.

## Nextcloud 

/var/www/html/occ config:list richdocuments should look like

```php
{
    "apps": {
        "richdocuments": {
            "canonical_webroot": "",
            "disable_certificate_verification": "",
            "doc_format": "ooxml",
            "edit_groups": "kingmas",
            "enabled": "yes",
            "external_apps": "",
            "installed_version": "8.7.2",
            "public_wopi_url": "https:\/\/collab.kingma.ch",
            "types": "filesystem,prevent_group_restriction",
            "wopi_callback_url": "",
            "wopi_url": "https:\/\/collab.kingma.ch"
        }
    }
}
```

After adding the collab url.
