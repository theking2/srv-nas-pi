# Collabora

## Installation

 * Add Apps `Nextcloud Office` (richdocuments) to nextcloud.
 * Create a NS entry (different from nextcloud)
 * Setup the docker container
 * Add collab.kingma.ch to NPM forward collab.kingma.ch to http://192.168.1.20:9980 and install the SSL Let's encrypt certificate.
 * In Nextcloud Office settings set `https://collab.kingma.ch` as _URL (and Port) of Collabora Online-server_

## NS

Add a `collab.kingma.ch` name pointing to the external IP. All traffic will be 443 so no additional port forwarding on the router is needed (done for `nextcloud` already)

## Compose YAML

Contrary to certain sources a `\` to escape the dots `.` is not needed. It is not clear if this is just a documentation error or if OMV Compose is so nice to take care of that.
