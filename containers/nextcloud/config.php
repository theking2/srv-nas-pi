<?php
// This file is part of Nextcloud.
$CONFIG = [ 
  'htaccess.RewriteBase'     => '/',
  'memcache.local'           => '\\OC\\Memcache\\APCu',
  'apps_paths'               =>
    [ 
      0 =>
        [ 
          'path'     => '/var/www/html/apps',
          'url'      => '/apps',
          'writable' => false,
        ],
      1 =>
        [ 
          'path'     => '/var/www/html/custom_apps',
          'url'      => '/custom_apps',
          'writable' => true,
        ],
    ],
  'memcache.distributed'     => '\\OC\\Memcache\\Redis',
  'memcache.locking'         => '\\OC\\Memcache\\Redis',
  'redis'                    =>
    [ 
      'host'     => 'redis',
      'password' => '',
      'port'     => 6379,
    ],
  'upgrade.disable-web'      => true,
  'instanceid'               => 'aaaaa',
  'passwordsalt'             => 'aaaaa',
  'secret'                   => 'aaaaa',
  'trusted_domains'          =>
    [ 
      0 => 'srv-nas-pi.kingma:8080',
      1 => 'office.kingma.ch',
      2 => 'office.king.ma',
    ],
  'overwrite.cli.url'        => 'http://srv-nas-pi.kingma:8080',
  'overwriteprotocol'        => 'https',
  'datadirectory'            => '/var/www/html/data',
  'dbtype'                   => 'mysql',
  'version'                  => '31.0.4.1',
  'dbname'                   => 'nextcloud',
  'dbhost'                   => 'nextclouddb',
  'dbport'                   => '',
  'dbtableprefix'            => 'oc_',
  'mysql.utf8mb4'            => true,
  'dbuser'                   => 'nextcloud',
  'dbpassword'               => 'dbpassword',
  'installed'                => true,
  'mail_smtpmode'            => 'smtp',
  'mail_sendmailmode'        => 'smtp',
  'mail_from_address'        => 'theking2',
  'mail_domain'              => 'king.ma',
  'mail_smtphost'            => 'cicero.metanet.ch',
  'mail_smtpport'            => '465',
  'mail_smpttimeout'         => 30,
  'mail_smtpsecure'          => 'ssl',
  'loglevel'                 => 2,
  'maintenance'              => false,
  'maintenance_window_start' => 3,
];
