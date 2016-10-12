Alias /scm/loggerhead/static /usr/share/loggerhead/static
RewriteEngine On
RewriteRule ^/scm/loggerhead$ /scm/loggerhead/ [R]

<IfModule wsgi_module>
    WSGIScriptAlias /scm/loggerhead /usr/bin/loggerhead.wsgi
    WSGIProcessGroup fusionforge-plugin-scmbzr
</IfModule>

<Location /scm/loggerhead>
  <IfVersion >= 2.3>
    Require all granted
  </IfVersion>
  <IfVersion < 2.3>
    order allow,deny
    allow from all
  </IfVersion>
</Location>
