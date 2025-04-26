#!/bin/bash

reverse_proxy_version=$(docker container exec nginx-proxy nginx -v 2>&1 | head -n 1)

echo "* Nextcloud Server version _(e.g., 29.x.x)_:  
  - \`$(docker container exec nextcloud php /var/www/html/occ --version)\`  
* Operating system and version _(e.g., Ubuntu 24.04)_:  
  - \`$(docker container exec nextcloud uname -v)\`  
* Web server and version _(e.g., Apache 2.4.25)_:  
  - \`$(docker container exec nextcloud apachectl -v | head -n 1)\`  
* Reverse proxy and version _(e.g., nginx 1.27.2)_:  
  - \`$reverse_proxy_version\`  
* PHP version _(e.g., 8.3)_:  
  - \`$(docker container exec nextcloud php --version | head -n 1)\`"