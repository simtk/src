
# We are inside <VirtualHost ...>

ServerName {core/web_host}
ServerAlias www.{core/web_host}
ServerAdmin webmaster@{core/web_host}
DocumentRoot /usr/share/gforge/www
DirectoryIndex index.html index.php

SetEnv FORGE_CONFIG_PATH "/etc/gforge"

<IfModule mod_userdir.c>
  UserDir disabled
</IfModule>

# Define configuration/env variables for passing passwords and other secrets to PHP
<Directory /usr/share/gforge/www>
  Include /etc/gforge/httpd.conf.d/secrets.inc
</Directory>

<Location /projects>
  ForceType application/x-httpd-php
</Location>
<Location /users>
  ForceType application/x-httpd-php
</Location>

<IfVersion >= 2.3>
  IncludeOptional /etc/gforge/httpd.conf.d/plugin-*.inc
</IfVersion>
<IfVersion < 2.3>
  Include /etc/gforge/httpd.conf.d/plugin-*.inc
</IfVersion>

Alias /images/ /usr/share/gforge/www/images/
Alias /scripts/coolfieldset/ /usr/share/gforge/vendor/coolfieldset/
# Alias /scripts/jquery/ /usr/share/gforge/vendor/jquery/
# Alias /scripts/jquery-ui/ /usr/share/gforge/vendor/jquery-ui/
# Alias /scripts/jquery-tipsy/ /usr/share/gforge/vendor/jquery-tipsy/
Alias /scripts/jquery-storage/ /usr/share/gforge/vendor/jquery-storage/
Alias /scripts/jquery-simpletreemenu/ /usr/share/gforge/vendor/jquery-simpletreemenu/
Alias /scripts/jquery-jqplot/ /usr/share/gforge/vendor/jquery-jqplot/
Alias /scripts/jquery-teamwork-gantt/ /usr/share/gforge/vendor/jquery-teamwork-gantt/
Alias /scripts/jquery-splitter/ /usr/share/gforge/vendor/jquery-splitter/
Alias /scripts/jquery-auto-height/ /usr/share/gforge/vendor/jquery-auto-height/

# For FusionForge without DNS delegation, uncomment this
# and remove 30-vhosts-projects.conf
# Include /etc/gforge/httpd.conf.d/projects-in-mainvhost.inc

