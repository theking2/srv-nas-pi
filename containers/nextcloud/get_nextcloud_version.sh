#!/bin/bash

reverse_proxy_version=$(docker container exec nginx-proxy nginx -v 2>&1 | head -n 1)

echo "* Nextcloud Server version:
  - \`$(docker container exec nextcloud php /var/www/html/occ --version)\`
* Operating system and version:
  - \`$(docker container exec nextcloud uname -v)\`
* Web server and version:
  - \`$(docker container exec nextcloud apachectl -v | head -n 1)\`
* Reverse proxy and version:
  - \`$reverse_proxy_version\`
* PHP version:
  - \`$(docker container exec nextcloud php --version | head -n 1)\`
* Is this the first time you've seen this error? (Yes / No):
  - \`Yes\`
* When did this problem seem to first start?
  - \`direct after installation or reboot\`
* Installation method _(e.g. AlO, NCP, Bare Metal/Archive, etc.)_
  - \`OMV Container\`
* Are you using CloudfIare, mod_security, or similar? _(Yes / No)_
  - \`No\`
"
