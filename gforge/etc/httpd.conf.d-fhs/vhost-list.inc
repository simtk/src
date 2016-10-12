ServerName {core/lists_host}

SetEnv FORGE_CONFIG_PATH "/etc/gforge"

<IfVersion >= 2.3>
  IncludeOptional /etc/gforge/httpd.conf.d/lists-vhost-plugin-*.inc
</IfVersion>
<IfVersion < 2.3>
  Include /etc/gforge/httpd.conf.d/lists-vhost-plugin-*.inc
</IfVersion>

RewriteEngine on
RewriteRule ^/$ /cgi-bin/mailman/listinfo [R=301]
