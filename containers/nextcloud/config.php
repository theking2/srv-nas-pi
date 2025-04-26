# Nextcloud

Access from Thunderbird, Davx5 over proxy with `https://office.kingma.ch/remote.php/dav`

## config.php

In `/pool0/main/compose/appdata/nextcloud/config/config.php`

```php
<?php
$CONFIG = array (
  'htaccess.RewriteBase' => '/',
  'memcache.local' => '\\OC\\Memcache\\APCu',
  'apps_paths' =>
  array (
    0 =>
    array (
      'path' => '/var/www/html/apps',
      'url' => '/apps',
      'writable' => false,
    ),
    1 =>
    array (
      'path' => '/var/www/html/custom_apps',
      'url' => '/custom_apps',
      'writable' => true,
    ),
  ),
  'memcache.distributed' => '\\OC\\Memcache\\Redis',
  'memcache.locking' => '\\OC\\Memcache\\Redis',
  'redis' =>
  array (
    'host' => 'redis',
    'password' => '',
    'port' => 6379,
  ),
  'upgrade.disable-web' => true,
  'instanceid' => 'oc6oc6hi85a5',
  'passwordsalt' => 'rKZdXH0AX+eLcwQhJkDmFt/qJ8kTzq',
  'secret' => 'Mv6+FzMFe81mpLrDXhikIjkUZHSvcI2fUp/Qr63GtqnGeXUl',
  'trusted_domains' =>
  array (
    0 => 'srv-nas-pi.kingma:8080',
    1 => 'office.kingma.ch',
  ),
  'overwrite.cli.url' => 'http://srv-nas-pi.kingma:8080',
  'overwriteprotocol' => 'https',
  'datadirectory' => '/var/www/html/data',
  'dbtype' => 'mysql',
  'version' => '31.0.4.1',
  'dbname' => 'nextcloud',
  'dbhost' => 'nextclouddb',
  'dbport' => '',
  'dbtableprefix' => 'oc_',
  'mysql.utf8mb4' => true,
  'dbuser' => 'nextcloud',
  'dbpassword' => 'dbpassword',
  'installed' => true,
  'mail_smtpmode' => 'smtp',
  'mail_sendmailmode' => 'smtp',
  'mail_from_address' => 'theking2',
  'mail_domain' => 'king.ma',
  'mail_smtphost' => 'cicero.metanet.ch',
  'mail_smtpport' => '465',
  'mail_smpttimeout' => 30,
  'mail_smtpsecure' => 'ssl',
  'loglevel' => 2,
  'maintenance' => false,
);
?>
```

## .htaccess

In `/var/www/html/.htaccess` (access with `docker exec -it nextcloud bash`)

```apache
<IfModule mod_headers.c>
  <IfModule mod_setenvif.c>
    <IfModule mod_fcgid.c>
       SetEnvIfNoCase ^Authorization$ "(.+)" XAUTHORIZATION=$1
       RequestHeader set XAuthorization %{XAUTHORIZATION}e env=XAUTHORIZATION
    </IfModule>
    <IfModule mod_proxy_fcgi.c>
       SetEnvIfNoCase Authorization "(.+)" HTTP_AUTHORIZATION=$1
    </IfModule>
    <IfModule mod_lsapi.c>
      SetEnvIfNoCase ^Authorization$ "(.+)" XAUTHORIZATION=$1
      RequestHeader set XAuthorization %{XAUTHORIZATION}e env=XAUTHORIZATION
    </IfModule>
  </IfModule>

  <IfModule mod_env.c>
    # Add security and privacy related headers
    # Avoid doubled headers by unsetting headers in "onsuccess" table,
    # then add headers to "always" table: https://github.com/nextcloud/server/pull/19002

    <If "%{REQUEST_URI} =~ m#/login$#">
      # Only on the login page we need any Origin or Referer header set.
      Header onsuccess unset Referrer-Policy
      Header always set Referrer-Policy "same-origin"
    </If>
    <Else>
      Header onsuccess unset Referrer-Policy
      Header always set Referrer-Policy "no-referrer"
    </Else>

    Header onsuccess unset X-Content-Type-Options
    Header always set X-Content-Type-Options "nosniff"

    Header onsuccess unset X-Frame-Options
    Header always set X-Frame-Options "SAMEORIGIN"

    Header onsuccess unset X-Permitted-Cross-Domain-Policies
    Header always set X-Permitted-Cross-Domain-Policies "none"

    Header onsuccess unset X-Robots-Tag
    Header always set X-Robots-Tag "noindex, nofollow"

    Header onsuccess unset X-XSS-Protection
    Header always set X-XSS-Protection "1; mode=block"

    SetEnv modHeadersAvailable true
  </IfModule>

  # Add cache control for static resources
  <FilesMatch "\.(css|js|mjs|svg|gif|png|jpg|webp|ico|wasm|tflite)$">
    <If "%{QUERY_STRING} =~ /(^|&)v=/">
      Header set Cache-Control "max-age=15778463, immutable"
    </If>
    <Else>
      Header set Cache-Control "max-age=15778463"
    </Else>
  </FilesMatch>

  # Let browsers cache OTF and WOFF files for a week
  <FilesMatch "\.(otf|woff2?)$">
    Header set Cache-Control "max-age=604800"
  </FilesMatch>
</IfModule>

<IfModule mod_php.c>
  php_value mbstring.func_overload 0
  php_value default_charset 'UTF-8'
  php_value output_buffering 0
  <IfModule mod_env.c>
    SetEnv htaccessWorking true
  </IfModule>
</IfModule>

<IfModule mod_mime.c>
  AddType image/svg+xml svg svgz
  AddType application/wasm wasm
  AddEncoding gzip svgz
  # Serve ESM javascript files (.mjs) with correct mime type
  AddType text/javascript js mjs
</IfModule>

<IfModule mod_dir.c>
  DirectoryIndex index.php index.html
</IfModule>

<IfModule pagespeed_module>
  ModPagespeed Off
</IfModule>

<IfModule mod_rewrite.c>
  RewriteEngine on
  RewriteCond %{HTTP_USER_AGENT} DavClnt
  RewriteRule ^$ /remote.php/webdav/ [L,R=302]
  RewriteRule .* - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
  RewriteRule ^\.well-known/carddav /remote.php/dav/ [R=301,L]
  RewriteRule ^\.well-known/caldav /remote.php/dav/ [R=301,L]
  RewriteRule ^remote/(.*) remote.php [QSA,L]
  RewriteRule ^(?:build|tests|config|lib|3rdparty|templates)/.* - [R=404,L]
  RewriteRule ^\.well-known/(?!acme-challenge|pki-validation) /index.php [QSA,L]
  RewriteRule ^ocm-provider/?$ index.php [QSA,L]
  RewriteRule ^(?:\.(?!well-known)|autotest|occ|issue|indie|db_|console).* - [R=404,L]
</IfModule>

# Clients like xDavv5 on Android, or Cyberduck, use chunked requests.
# When FastCGI or FPM is used with apache, requests arrive to Nextcloud without any content.
# This leads to the creation of empty files.
# The following directive will force the problematic requests to be buffered before being forwarded to Nextcloud.
# This way, the "Transfer-Encoding" header is removed, the "Content-Length" header is set, and the request content is proxied to Nextcloud.
# Here are more information about the issue:
#  - https://docs.cyberduck.io/mountainduck/issues/fastcgi/
#  - https://docs.nextcloud.com/server/latest/admin_manual/issues/general_troubleshooting.html#troubleshooting-webdav
<IfModule mod_setenvif.c>
  SetEnvIfNoCase Transfer-Encoding "chunked" proxy-sendcl=1
</IfModule>

# Apache disabled the sending of the server-side content-length header
# in their 2.4.59 patch updated which breaks some use-cases in Nextcloud.
# Setting ap_trust_cgilike_cl allows to bring back the usual behaviour.
# See https://bz.apache.org/bugzilla/show_bug.cgi?id=68973
<IfModule mod_env.c>
  SetEnv ap_trust_cgilike_cl
</IfModule>

AddDefaultCharset utf-8
Options -Indexes
#### DO NOT CHANGE ANYTHING ABOVE THIS LINE ####

ErrorDocument 403 /index.php/error/403
ErrorDocument 404 /index.php/error/404
<IfModule mod_rewrite.c>
  Options -MultiViews
  RewriteRule ^core/js/oc.js$ index.php [PT,E=PATH_INFO:$1]
  RewriteRule ^core/preview.png$ index.php [PT,E=PATH_INFO:$1]
  RewriteCond %{REQUEST_FILENAME} !\.(css|js|mjs|svg|gif|ico|jpg|jpeg|png|webp|html|otf|ttf|woff2?|map|webm|mp4|mp3|ogg|wav|flac|wasm|tflite)$
  RewriteCond %{REQUEST_FILENAME} !/core/ajax/update\.php
  RewriteCond %{REQUEST_FILENAME} !/core/img/(favicon\.ico|manifest\.json)$
  RewriteCond %{REQUEST_FILENAME} !/(cron|public|remote|status)\.php
  RewriteCond %{REQUEST_FILENAME} !/ocs/v(1|2)\.php
  RewriteCond %{REQUEST_FILENAME} !/robots\.txt
  RewriteCond %{REQUEST_FILENAME} !/(ocs-provider|updater)/
  RewriteCond %{REQUEST_URI} !^/\.well-known/(acme-challenge|pki-validation)/.*
  RewriteCond %{REQUEST_FILENAME} !/richdocumentscode(_arm64)?/proxy.php$
  RewriteRule . index.php [PT,E=PATH_INFO:$1]
  RewriteBase /
  <IfModule mod_env.c>
    SetEnv front_controller_active true
    <IfModule mod_dir.c>
      DirectorySlash off
    </IfModule>
  </IfModule>
</IfModule>
``` 
